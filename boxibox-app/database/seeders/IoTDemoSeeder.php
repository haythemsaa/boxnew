<?php

namespace Database\Seeders;

use App\Models\IotSensorType;
use App\Models\IotHub;
use App\Models\IotSensor;
use App\Models\IotReading;
use App\Models\IotAlert;
use App\Models\IotAlertRule;
use App\Models\IotReadingAggregate;
use App\Models\Site;
use App\Models\Box;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IoTDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $this->command->info('Creating IoT demo data...');

        // 1. Create sensor types
        $sensorTypes = $this->createSensorTypes();
        $this->command->info('Created ' . count($sensorTypes) . ' sensor types');

        // 2. Create IoT hubs
        $hubs = $this->createHubs($tenantId);
        $this->command->info('Created ' . count($hubs) . ' IoT hubs');

        // 3. Create sensors
        $sensors = $this->createSensors($tenantId, $sensorTypes, $hubs);
        $this->command->info('Created ' . count($sensors) . ' sensors');

        // 4. Create readings for sensors
        $readingsCount = $this->createReadings($sensors);
        $this->command->info('Created ' . $readingsCount . ' sensor readings');

        // 5. Create aggregated readings
        $aggregatesCount = $this->createAggregates($sensors);
        $this->command->info('Created ' . $aggregatesCount . ' aggregated readings');

        // 6. Create alerts
        $alertsCount = $this->createAlerts($tenantId, $sensors);
        $this->command->info('Created ' . $alertsCount . ' alerts');

        // 7. Create alert rules
        $rulesCount = $this->createAlertRules($tenantId, $sensorTypes);
        $this->command->info('Created ' . $rulesCount . ' alert rules');

        $this->command->info('IoT demo data created successfully!');
    }

    private function createSensorTypes(): array
    {
        $types = [
            [
                'name' => 'Temperature',
                'slug' => 'temperature',
                'unit' => '°C',
                'icon' => 'thermometer',
                'min_value' => -20,
                'max_value' => 50,
                'default_alert_min' => 5,
                'default_alert_max' => 35,
            ],
            [
                'name' => 'Humidity',
                'slug' => 'humidity',
                'unit' => '%',
                'icon' => 'droplet',
                'min_value' => 0,
                'max_value' => 100,
                'default_alert_min' => 20,
                'default_alert_max' => 80,
            ],
            [
                'name' => 'Door',
                'slug' => 'door',
                'unit' => '',
                'icon' => 'door-open',
                'min_value' => 0,
                'max_value' => 1,
                'default_alert_min' => null,
                'default_alert_max' => null,
            ],
            [
                'name' => 'Motion',
                'slug' => 'motion',
                'unit' => '',
                'icon' => 'person-walking',
                'min_value' => 0,
                'max_value' => 1,
                'default_alert_min' => null,
                'default_alert_max' => null,
            ],
            [
                'name' => 'Light',
                'slug' => 'light',
                'unit' => 'lux',
                'icon' => 'sun',
                'min_value' => 0,
                'max_value' => 10000,
                'default_alert_min' => null,
                'default_alert_max' => 8000,
            ],
            [
                'name' => 'CO2',
                'slug' => 'co2',
                'unit' => 'ppm',
                'icon' => 'cloud',
                'min_value' => 0,
                'max_value' => 5000,
                'default_alert_min' => null,
                'default_alert_max' => 1000,
            ],
            [
                'name' => 'Smart Lock',
                'slug' => 'smart_lock',
                'unit' => '',
                'icon' => 'lock',
                'min_value' => 0,
                'max_value' => 1,
                'default_alert_min' => null,
                'default_alert_max' => null,
            ],
            [
                'name' => 'Battery',
                'slug' => 'battery',
                'unit' => '%',
                'icon' => 'battery-half',
                'min_value' => 0,
                'max_value' => 100,
                'default_alert_min' => 20,
                'default_alert_max' => null,
            ],
        ];

        $created = [];
        foreach ($types as $type) {
            $created[] = IotSensorType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }

        return $created;
    }

    private function createHubs(int $tenantId): array
    {
        $sites = Site::where('tenant_id', $tenantId)->take(3)->get();
        $hubs = [];

        $hubData = [
            [
                'name' => 'Hub Principal - Entree',
                'model' => 'BoxiHub Pro',
                'connection_type' => 'ethernet',
                'firmware_version' => '2.4.1',
                'status' => 'online',
            ],
            [
                'name' => 'Hub Secondaire - Zone A',
                'model' => 'BoxiHub Lite',
                'connection_type' => 'wifi',
                'firmware_version' => '2.3.8',
                'status' => 'online',
            ],
            [
                'name' => 'Hub LoRa - Exterieur',
                'model' => 'BoxiHub LoRa',
                'connection_type' => 'lora',
                'firmware_version' => '1.9.2',
                'status' => 'online',
            ],
        ];

        foreach ($sites as $index => $site) {
            if (!isset($hubData[$index])) continue;

            $data = $hubData[$index];
            $hubs[] = IotHub::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'site_id' => $site->id,
                    'serial_number' => 'HUB-' . strtoupper(Str::random(8)),
                ],
                [
                    'name' => $data['name'],
                    'model' => $data['model'],
                    'connection_type' => $data['connection_type'],
                    'firmware_version' => $data['firmware_version'],
                    'ip_address' => '192.168.1.' . rand(10, 250),
                    'mac_address' => implode(':', array_map(fn() => strtoupper(dechex(rand(0, 255))), range(1, 6))),
                    'status' => $data['status'],
                    'last_seen_at' => now()->subMinutes(rand(1, 30)),
                ]
            );
        }

        return $hubs;
    }

    private function createSensors(int $tenantId, array $sensorTypes, array $hubs): array
    {
        $sites = Site::where('tenant_id', $tenantId)->take(3)->get();
        $sensors = [];

        // Get type IDs by slug
        $typeMap = collect($sensorTypes)->keyBy('slug');

        foreach ($sites as $siteIndex => $site) {
            $hub = $hubs[$siteIndex] ?? $hubs[0];
            $boxes = Box::where('site_id', $site->id)->take(10)->get();

            // Temperature sensors - one per 5 boxes
            foreach ($boxes->chunk(5) as $chunkIndex => $boxChunk) {
                $sensors[] = IotSensor::updateOrCreate(
                    [
                        'hub_id' => $hub->id,
                        'serial_number' => 'TEMP-' . $site->id . '-' . ($chunkIndex + 1),
                    ],
                    [
                        'site_id' => $site->id,
                        'sensor_type_id' => $typeMap['temperature']->id,
                        'name' => 'Temperature Zone ' . chr(65 + $chunkIndex),
                        'location_description' => 'Couloir ' . chr(65 + $chunkIndex),
                        'status' => 'active',
                        'battery_level' => rand(60, 100),
                        'last_reading_at' => now()->subMinutes(rand(1, 15)),
                        'last_value' => round(18 + rand(-30, 30) / 10, 1),
                        'alert_min' => 5,
                        'alert_max' => 35,
                        'alerts_enabled' => true,
                    ]
                );

                // Humidity sensor alongside temperature
                $sensors[] = IotSensor::updateOrCreate(
                    [
                        'hub_id' => $hub->id,
                        'serial_number' => 'HUM-' . $site->id . '-' . ($chunkIndex + 1),
                    ],
                    [
                        'site_id' => $site->id,
                        'sensor_type_id' => $typeMap['humidity']->id,
                        'name' => 'Humidite Zone ' . chr(65 + $chunkIndex),
                        'location_description' => 'Couloir ' . chr(65 + $chunkIndex),
                        'status' => 'active',
                        'battery_level' => rand(60, 100),
                        'last_reading_at' => now()->subMinutes(rand(1, 15)),
                        'last_value' => round(50 + rand(-100, 100) / 10, 1),
                        'alert_min' => 20,
                        'alert_max' => 80,
                        'alerts_enabled' => true,
                    ]
                );
            }

            // Smart locks - one per box (first 5 boxes)
            foreach ($boxes->take(5) as $box) {
                $isLocked = rand(0, 1);
                $sensors[] = IotSensor::updateOrCreate(
                    [
                        'hub_id' => $hub->id,
                        'serial_number' => 'LOCK-' . $site->id . '-' . $box->id,
                    ],
                    [
                        'site_id' => $site->id,
                        'box_id' => $box->id,
                        'sensor_type_id' => $typeMap['smart_lock']->id,
                        'name' => 'Serrure ' . $box->box_number,
                        'location_description' => 'Box ' . $box->box_number,
                        'status' => 'active',
                        'battery_level' => rand(40, 100),
                        'last_reading_at' => now()->subMinutes(rand(1, 60)),
                        'last_value' => $isLocked,
                        'metadata' => [
                            'is_locked' => $isLocked,
                            'lock_type' => ['noke', 'salto', 'kisi'][rand(0, 2)],
                            'last_access' => now()->subHours(rand(1, 48))->toIso8601String(),
                        ],
                    ]
                );
            }

            // Door sensors at entrance
            $sensors[] = IotSensor::updateOrCreate(
                [
                    'hub_id' => $hub->id,
                    'serial_number' => 'DOOR-' . $site->id . '-MAIN',
                ],
                [
                    'site_id' => $site->id,
                    'sensor_type_id' => $typeMap['door']->id,
                    'name' => 'Porte Principale',
                    'location_description' => 'Entree',
                    'status' => 'active',
                    'battery_level' => rand(70, 100),
                    'last_reading_at' => now()->subMinutes(rand(1, 30)),
                    'last_value' => 0,
                    'metadata' => ['is_open' => false],
                ]
            );

            // Motion sensor
            $sensors[] = IotSensor::updateOrCreate(
                [
                    'hub_id' => $hub->id,
                    'serial_number' => 'MOT-' . $site->id . '-1',
                ],
                [
                    'site_id' => $site->id,
                    'sensor_type_id' => $typeMap['motion']->id,
                    'name' => 'Detecteur Mouvement Hall',
                    'location_description' => 'Hall d\'entree',
                    'status' => 'active',
                    'battery_level' => rand(50, 100),
                    'last_reading_at' => now()->subMinutes(rand(1, 10)),
                    'last_value' => 0,
                ]
            );

            // One offline sensor for demo
            if ($siteIndex === 0) {
                $sensors[] = IotSensor::updateOrCreate(
                    [
                        'hub_id' => $hub->id,
                        'serial_number' => 'TEMP-' . $site->id . '-OFFLINE',
                    ],
                    [
                        'site_id' => $site->id,
                        'sensor_type_id' => $typeMap['temperature']->id,
                        'name' => 'Temperature Cave (Hors ligne)',
                        'location_description' => 'Sous-sol',
                        'status' => 'offline',
                        'battery_level' => 5,
                        'last_reading_at' => now()->subHours(3),
                        'last_value' => 12.5,
                    ]
                );
            }
        }

        return $sensors;
    }

    private function createReadings(array $sensors): int
    {
        $count = 0;

        foreach ($sensors as $sensor) {
            $sensorType = $sensor->sensorType;
            if (!$sensorType) continue;

            // Generate readings for the last 24 hours (every 15 minutes)
            $readings = [];
            $baseTime = now()->subHours(24);

            for ($i = 0; $i < 96; $i++) { // 96 readings = 24h at 15min intervals
                $timestamp = $baseTime->copy()->addMinutes($i * 15);
                $value = $this->generateRealisticValue($sensorType->slug, $timestamp);

                $readings[] = [
                    'sensor_id' => $sensor->id,
                    'value' => $value,
                    'recorded_at' => $timestamp,
                    'is_anomaly' => $value > ($sensor->alert_threshold_high ?? 999) || $value < ($sensor->alert_threshold_low ?? -999),
                    'triggered_alert' => false,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                $count++;
            }

            // Batch insert
            IotReading::insert($readings);
        }

        return $count;
    }

    private function generateRealisticValue(string $sensorType, Carbon $timestamp): float
    {
        $hour = $timestamp->hour;

        return match ($sensorType) {
            'temperature' => round(18 + sin($hour * 0.26) * 4 + rand(-10, 10) / 10, 1), // 14-22°C with variation
            'humidity' => round(45 + cos($hour * 0.26) * 15 + rand(-50, 50) / 10, 1), // 30-60% with variation
            'door' => rand(0, 100) < 5 ? 1 : 0, // 5% chance of being open
            'motion' => rand(0, 100) < ($hour >= 8 && $hour <= 20 ? 30 : 5) ? 1 : 0, // More activity during day
            'light' => $hour >= 7 && $hour <= 19 ? rand(200, 800) : rand(0, 50), // Day/night cycle
            'co2' => round(400 + ($hour >= 9 && $hour <= 17 ? rand(100, 300) : rand(0, 100)), 0), // Higher during work hours
            'smart_lock' => rand(0, 1), // Locked or unlocked
            'battery' => rand(20, 100),
            default => rand(0, 100),
        };
    }

    private function createAggregates(array $sensors): int
    {
        $count = 0;

        foreach ($sensors as $sensor) {
            if (!in_array($sensor->sensorType?->slug, ['temperature', 'humidity', 'co2', 'light'])) {
                continue;
            }

            // Create daily aggregates for the last 30 days
            for ($day = 30; $day >= 1; $day--) {
                $date = now()->subDays($day)->startOfDay();

                $baseValue = match ($sensor->sensorType->slug) {
                    'temperature' => 18 + rand(-30, 30) / 10,
                    'humidity' => 50 + rand(-100, 100) / 10,
                    'co2' => 500 + rand(-100, 200),
                    'light' => 400 + rand(-200, 200),
                    default => 50,
                };

                IotReadingAggregate::updateOrCreate(
                    [
                        'sensor_id' => $sensor->id,
                        'period' => 'daily',
                        'period_start' => $date,
                    ],
                    [
                        'period_end' => $date->copy()->endOfDay(),
                        'min_value' => round($baseValue - rand(20, 50) / 10, 1),
                        'max_value' => round($baseValue + rand(20, 50) / 10, 1),
                        'avg_value' => round($baseValue, 1),
                        'reading_count' => rand(90, 96),
                        'anomaly_count' => rand(0, 3),
                        'alert_count' => rand(0, 1),
                    ]
                );
                $count++;
            }
        }

        return $count;
    }

    private function createAlerts(int $tenantId, array $sensors): int
    {
        $alerts = [];

        // Find temperature sensors
        $tempSensors = collect($sensors)->filter(fn($s) => $s->sensorType?->slug === 'temperature');
        $humiditySensors = collect($sensors)->filter(fn($s) => $s->sensorType?->slug === 'humidity');
        $lockSensors = collect($sensors)->filter(fn($s) => $s->sensorType?->slug === 'smart_lock');

        // Critical: High temperature alert (active)
        if ($tempSensor = $tempSensors->first()) {
            $alerts[] = [
                'tenant_id' => $tenantId,
                'site_id' => $tempSensor->site_id,
                'sensor_id' => $tempSensor->id,
                'alert_type' => 'threshold_exceeded',
                'severity' => 'critical',
                'title' => 'Temperature elevee detectee',
                'message' => 'La temperature dans ' . $tempSensor->location_description . ' a atteint 38°C, depassant le seuil de 35°C.',
                'trigger_value' => 38.2,
                'threshold_value' => 35,
                'status' => 'active',
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ];
        }

        // Warning: Humidity alert (acknowledged)
        if ($humiditySensor = $humiditySensors->first()) {
            $alerts[] = [
                'tenant_id' => $tenantId,
                'site_id' => $humiditySensor->site_id,
                'sensor_id' => $humiditySensor->id,
                'alert_type' => 'threshold_exceeded',
                'severity' => 'warning',
                'title' => 'Humidite elevee',
                'message' => 'L\'humidite dans ' . $humiditySensor->location_description . ' est a 82%, depassant le seuil de 80%.',
                'trigger_value' => 82,
                'threshold_value' => 80,
                'status' => 'acknowledged',
                'acknowledged_at' => now()->subHour(),
                'acknowledged_by' => 1,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHour(),
            ];
        }

        // Info: Low battery (resolved)
        if ($lockSensor = $lockSensors->first()) {
            $alerts[] = [
                'tenant_id' => $tenantId,
                'site_id' => $lockSensor->site_id,
                'sensor_id' => $lockSensor->id,
                'alert_type' => 'battery_low',
                'severity' => 'warning',
                'title' => 'Batterie faible - Serrure',
                'message' => 'La batterie de la serrure ' . $lockSensor->name . ' est a 15%.',
                'trigger_value' => 15,
                'threshold_value' => 20,
                'status' => 'resolved',
                'acknowledged_at' => now()->subDays(2),
                'acknowledged_by' => 1,
                'resolved_at' => now()->subDay(),
                'resolved_by' => 1,
                'resolution_notes' => 'Batterie remplacee',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDay(),
            ];
        }

        // Sensor offline alert
        $offlineSensor = collect($sensors)->firstWhere('status', 'offline');
        if ($offlineSensor) {
            $alerts[] = [
                'tenant_id' => $tenantId,
                'site_id' => $offlineSensor->site_id,
                'sensor_id' => $offlineSensor->id,
                'alert_type' => 'sensor_offline',
                'severity' => 'critical',
                'title' => 'Capteur hors ligne',
                'message' => 'Le capteur ' . $offlineSensor->name . ' ne repond plus depuis 3 heures.',
                'trigger_value' => null,
                'threshold_value' => null,
                'status' => 'active',
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ];
        }

        // Rapid change alert
        if ($tempSensor2 = $tempSensors->skip(1)->first()) {
            $alerts[] = [
                'tenant_id' => $tenantId,
                'site_id' => $tempSensor2->site_id,
                'sensor_id' => $tempSensor2->id,
                'alert_type' => 'rapid_change',
                'severity' => 'warning',
                'title' => 'Variation rapide de temperature',
                'message' => 'La temperature a varie de 5°C en moins de 30 minutes.',
                'trigger_value' => 5,
                'threshold_value' => 3,
                'status' => 'acknowledged',
                'acknowledged_at' => now()->subHours(12),
                'acknowledged_by' => 1,
                'created_at' => now()->subHours(18),
                'updated_at' => now()->subHours(12),
            ];
        }

        foreach ($alerts as $alert) {
            IotAlert::create($alert);
        }

        return count($alerts);
    }

    private function createAlertRules(int $tenantId, array $sensorTypes): int
    {
        $typeMap = collect($sensorTypes)->keyBy('slug');
        $rules = [];

        // Temperature rules
        if (isset($typeMap['temperature'])) {
            $rules[] = [
                'tenant_id' => $tenantId,
                'name' => 'Temperature critique haute',
                'sensor_type_id' => $typeMap['temperature']->id,
                'condition' => 'above',
                'threshold_value' => 35,
                'severity' => 'critical',
                'notification_channels' => ['email', 'sms', 'push'],
                'cooldown_minutes' => 30,
                'is_active' => true,
            ];

            $rules[] = [
                'tenant_id' => $tenantId,
                'name' => 'Temperature basse',
                'sensor_type_id' => $typeMap['temperature']->id,
                'condition' => 'below',
                'threshold_value' => 5,
                'severity' => 'warning',
                'notification_channels' => ['email', 'push'],
                'cooldown_minutes' => 60,
                'is_active' => true,
            ];
        }

        // Humidity rules
        if (isset($typeMap['humidity'])) {
            $rules[] = [
                'tenant_id' => $tenantId,
                'name' => 'Humidite excessive',
                'sensor_type_id' => $typeMap['humidity']->id,
                'condition' => 'above',
                'threshold_value' => 80,
                'severity' => 'warning',
                'notification_channels' => ['email'],
                'cooldown_minutes' => 120,
                'is_active' => true,
            ];
        }

        // Battery rule (applies to all sensors via service)
        $rules[] = [
            'tenant_id' => $tenantId,
            'name' => 'Batterie faible',
            'sensor_type_id' => null,
            'condition' => 'below',
            'threshold_value' => 20,
            'severity' => 'warning',
            'notification_channels' => ['email'],
            'cooldown_minutes' => 1440, // Once per day
            'is_active' => true,
        ];

        foreach ($rules as $rule) {
            IotAlertRule::updateOrCreate(
                [
                    'tenant_id' => $rule['tenant_id'],
                    'name' => $rule['name'],
                ],
                $rule
            );
        }

        return count($rules);
    }
}
