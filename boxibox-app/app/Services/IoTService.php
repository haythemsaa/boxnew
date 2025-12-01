<?php

namespace App\Services;

use App\Models\IotHub;
use App\Models\IotSensor;
use App\Models\IotReading;
use App\Models\IotReadingAggregate;
use App\Models\IotAlert;
use App\Models\IotAlertRule;
use App\Models\IotInsuranceReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IoTService
{
    /**
     * Enregistrer une lecture de capteur
     */
    public function recordReading(IotSensor $sensor, float $value, ?Carbon $timestamp = null): IotReading
    {
        $timestamp = $timestamp ?? now();

        $reading = IotReading::create([
            'sensor_id' => $sensor->id,
            'value' => $value,
            'recorded_at' => $timestamp,
        ]);

        // Mettre à jour le capteur
        $sensor->update([
            'last_reading_at' => $timestamp,
            'last_value' => $value,
            'status' => 'active',
        ]);

        // Vérifier les seuils d'alerte
        $this->checkAlertThresholds($sensor, $reading);

        return $reading;
    }

    /**
     * Vérifier les seuils d'alerte
     */
    protected function checkAlertThresholds(IotSensor $sensor, IotReading $reading): void
    {
        if (!$sensor->alerts_enabled) {
            return;
        }

        $value = $reading->value;
        $alertType = null;
        $severity = 'warning';

        // Vérifier les seuils du capteur
        if ($sensor->alert_max !== null && $value > $sensor->alert_max) {
            $alertType = 'threshold_exceeded';
            $severity = $value > ($sensor->alert_max * 1.2) ? 'critical' : 'warning';
        } elseif ($sensor->alert_min !== null && $value < $sensor->alert_min) {
            $alertType = 'threshold_below';
            $severity = $value < ($sensor->alert_min * 0.8) ? 'critical' : 'warning';
        }

        // Vérifier les changements rapides
        $previousReading = IotReading::where('sensor_id', $sensor->id)
            ->where('recorded_at', '<', $reading->recorded_at)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if ($previousReading) {
            $changeRate = abs($value - $previousReading->value) / max(abs($previousReading->value), 1) * 100;
            if ($changeRate > 20) { // Changement de plus de 20%
                $alertType = 'rapid_change';
                $severity = 'warning';
            }
        }

        if ($alertType) {
            $this->createAlert($sensor, $reading, $alertType, $severity);
        }

        // Marquer la lecture comme anomalie si nécessaire
        if ($alertType) {
            $reading->update([
                'is_anomaly' => true,
                'triggered_alert' => true,
            ]);
        }
    }

    /**
     * Créer une alerte
     */
    protected function createAlert(IotSensor $sensor, IotReading $reading, string $alertType, string $severity): IotAlert
    {
        $sensorType = $sensor->sensorType;

        $messages = [
            'threshold_exceeded' => "Valeur {$reading->value}{$sensorType->unit} dépasse le seuil maximum de {$sensor->alert_max}{$sensorType->unit}",
            'threshold_below' => "Valeur {$reading->value}{$sensorType->unit} en dessous du seuil minimum de {$sensor->alert_min}{$sensorType->unit}",
            'rapid_change' => "Changement rapide détecté sur le capteur {$sensor->name}",
            'sensor_offline' => "Capteur {$sensor->name} hors ligne",
            'battery_low' => "Batterie faible sur le capteur {$sensor->name}",
        ];

        return IotAlert::create([
            'tenant_id' => $sensor->site->tenant_id,
            'sensor_id' => $sensor->id,
            'reading_id' => $reading->id,
            'box_id' => $sensor->box_id,
            'site_id' => $sensor->site_id,
            'alert_type' => $alertType,
            'severity' => $severity,
            'title' => "Alerte {$sensorType->name} - {$sensor->name}",
            'message' => $messages[$alertType] ?? "Alerte sur le capteur {$sensor->name}",
            'trigger_value' => $reading->value,
            'threshold_value' => $alertType === 'threshold_exceeded' ? $sensor->alert_max : $sensor->alert_min,
            'status' => 'active',
        ]);
    }

    /**
     * Vérifier les capteurs hors ligne
     */
    public function checkOfflineSensors(int $siteId): Collection
    {
        $offlineThreshold = now()->subMinutes(30);

        $offlineSensors = IotSensor::where('site_id', $siteId)
            ->where('status', '!=', 'inactive')
            ->where(function ($q) use ($offlineThreshold) {
                $q->whereNull('last_reading_at')
                    ->orWhere('last_reading_at', '<', $offlineThreshold);
            })
            ->get();

        foreach ($offlineSensors as $sensor) {
            $sensor->update(['status' => 'offline']);

            // Créer une alerte si pas déjà existante
            $existingAlert = IotAlert::where('sensor_id', $sensor->id)
                ->where('alert_type', 'sensor_offline')
                ->where('status', 'active')
                ->exists();

            if (!$existingAlert) {
                IotAlert::create([
                    'tenant_id' => $sensor->site->tenant_id,
                    'sensor_id' => $sensor->id,
                    'box_id' => $sensor->box_id,
                    'site_id' => $sensor->site_id,
                    'alert_type' => 'sensor_offline',
                    'severity' => 'warning',
                    'title' => "Capteur hors ligne - {$sensor->name}",
                    'message' => "Le capteur {$sensor->name} n'a pas transmis de données depuis plus de 30 minutes",
                    'status' => 'active',
                ]);
            }
        }

        return $offlineSensors;
    }

    /**
     * Agréger les lectures par période
     */
    public function aggregateReadings(string $period = 'hourly'): int
    {
        $aggregated = 0;

        $periodConfig = match ($period) {
            'hourly' => ['interval' => 'hour', 'since' => now()->subHours(2)],
            'daily' => ['interval' => 'day', 'since' => now()->subDays(2)],
            'weekly' => ['interval' => 'week', 'since' => now()->subWeeks(2)],
            'monthly' => ['interval' => 'month', 'since' => now()->subMonths(2)],
        };

        $sensors = IotSensor::where('status', '!=', 'inactive')->get();

        foreach ($sensors as $sensor) {
            $readings = IotReading::where('sensor_id', $sensor->id)
                ->where('recorded_at', '>=', $periodConfig['since'])
                ->get()
                ->groupBy(fn($r) => $r->recorded_at->startOf($periodConfig['interval'])->toDateTimeString());

            foreach ($readings as $periodStart => $periodReadings) {
                $periodEnd = Carbon::parse($periodStart)->endOf($periodConfig['interval']);

                IotReadingAggregate::updateOrCreate(
                    [
                        'sensor_id' => $sensor->id,
                        'period' => $period,
                        'period_start' => $periodStart,
                    ],
                    [
                        'period_end' => $periodEnd,
                        'min_value' => $periodReadings->min('value'),
                        'max_value' => $periodReadings->max('value'),
                        'avg_value' => $periodReadings->avg('value'),
                        'reading_count' => $periodReadings->count(),
                        'anomaly_count' => $periodReadings->where('is_anomaly', true)->count(),
                        'alert_count' => $periodReadings->where('triggered_alert', true)->count(),
                    ]
                );

                $aggregated++;
            }
        }

        return $aggregated;
    }

    /**
     * Obtenir les données du dashboard IoT
     */
    public function getDashboardData(int $siteId): array
    {
        $sensors = IotSensor::where('site_id', $siteId)->with('sensorType')->get();

        $activeAlerts = IotAlert::where('site_id', $siteId)
            ->where('status', 'active')
            ->orderBy('severity')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Statistiques par type de capteur
        $stats = [];
        foreach ($sensors->groupBy('sensor_type_id') as $typeId => $typeSensors) {
            $sensorType = $typeSensors->first()->sensorType;
            $currentValues = $typeSensors->pluck('last_value')->filter();

            $stats[$sensorType->slug] = [
                'name' => $sensorType->name,
                'unit' => $sensorType->unit,
                'sensor_count' => $typeSensors->count(),
                'online_count' => $typeSensors->where('status', 'active')->count(),
                'current_avg' => $currentValues->avg(),
                'current_min' => $currentValues->min(),
                'current_max' => $currentValues->max(),
            ];
        }

        // Historique des dernières 24h
        $history = IotReading::whereIn('sensor_id', $sensors->pluck('id'))
            ->where('recorded_at', '>=', now()->subDay())
            ->orderBy('recorded_at')
            ->get()
            ->groupBy(fn($r) => $r->recorded_at->format('H:00'));

        return [
            'sensors' => [
                'total' => $sensors->count(),
                'online' => $sensors->where('status', 'active')->count(),
                'offline' => $sensors->where('status', 'offline')->count(),
                'error' => $sensors->where('status', 'error')->count(),
            ],
            'alerts' => [
                'active' => $activeAlerts,
                'critical_count' => $activeAlerts->where('severity', 'critical')->count(),
                'warning_count' => $activeAlerts->where('severity', 'warning')->count(),
            ],
            'stats_by_type' => $stats,
            'history_24h' => $history->map(fn($readings) => [
                'avg' => $readings->avg('value'),
                'min' => $readings->min('value'),
                'max' => $readings->max('value'),
            ]),
        ];
    }

    /**
     * Générer un rapport pour l'assurance
     */
    public function generateInsuranceReport(int $siteId, Carbon $periodStart, Carbon $periodEnd): IotInsuranceReport
    {
        $site = \App\Models\Site::findOrFail($siteId);
        $sensors = IotSensor::where('site_id', $siteId)->get();

        // Statistiques température
        $tempSensors = $sensors->where('sensor_type_id', 1); // Assuming 1 = temperature
        $tempReadings = IotReading::whereIn('sensor_id', $tempSensors->pluck('id'))
            ->whereBetween('recorded_at', [$periodStart, $periodEnd])
            ->get();

        $temperatureSummary = [
            'avg' => round($tempReadings->avg('value'), 1),
            'min' => $tempReadings->min('value'),
            'max' => $tempReadings->max('value'),
            'readings_count' => $tempReadings->count(),
            'out_of_range_count' => $tempReadings->where('is_anomaly', true)->count(),
        ];

        // Statistiques humidité
        $humSensors = $sensors->where('sensor_type_id', 2); // Assuming 2 = humidity
        $humReadings = IotReading::whereIn('sensor_id', $humSensors->pluck('id'))
            ->whereBetween('recorded_at', [$periodStart, $periodEnd])
            ->get();

        $humiditySummary = [
            'avg' => round($humReadings->avg('value'), 1),
            'min' => $humReadings->min('value'),
            'max' => $humReadings->max('value'),
            'readings_count' => $humReadings->count(),
            'out_of_range_count' => $humReadings->where('is_anomaly', true)->count(),
        ];

        // Incidents
        $alerts = IotAlert::where('site_id', $siteId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->get();

        $incidentSummary = [
            'total' => $alerts->count(),
            'critical' => $alerts->where('severity', 'critical')->count(),
            'warning' => $alerts->where('severity', 'warning')->count(),
            'resolved' => $alerts->where('status', 'resolved')->count(),
        ];

        // Calcul uptime
        $totalHours = $periodStart->diffInHours($periodEnd);
        $offlineHours = 0; // TODO: Calculer basé sur les alertes offline

        $uptimePercentage = $totalHours > 0
            ? round((($totalHours - $offlineHours) / $totalHours) * 100, 2)
            : 100;

        return IotInsuranceReport::create([
            'tenant_id' => $site->tenant_id,
            'site_id' => $siteId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'temperature_summary' => $temperatureSummary,
            'humidity_summary' => $humiditySummary,
            'incident_summary' => $incidentSummary,
            'total_alerts' => $alerts->count(),
            'critical_alerts' => $alerts->where('severity', 'critical')->count(),
            'uptime_percentage' => $uptimePercentage,
            'status' => 'ready',
        ]);
    }

    /**
     * Obtenir l'historique d'un capteur
     */
    public function getSensorHistory(IotSensor $sensor, Carbon $from, Carbon $to, string $aggregation = 'raw'): Collection
    {
        if ($aggregation === 'raw') {
            return IotReading::where('sensor_id', $sensor->id)
                ->whereBetween('recorded_at', [$from, $to])
                ->orderBy('recorded_at')
                ->get();
        }

        return IotReadingAggregate::where('sensor_id', $sensor->id)
            ->where('period', $aggregation)
            ->whereBetween('period_start', [$from, $to])
            ->orderBy('period_start')
            ->get();
    }

    /**
     * Acquitter une alerte
     */
    public function acknowledgeAlert(IotAlert $alert, int $userId): IotAlert
    {
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);

        return $alert;
    }

    /**
     * Résoudre une alerte
     */
    public function resolveAlert(IotAlert $alert, int $userId, ?string $notes = null): IotAlert
    {
        $alert->update([
            'status' => 'resolved',
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        return $alert;
    }
}
