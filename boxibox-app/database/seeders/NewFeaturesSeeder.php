<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NewFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        // Get the first site for this tenant
        $site = DB::table('sites')->where('tenant_id', $tenantId)->first();
        if (!$site) {
            $this->command->error('No site found for tenant ' . $tenantId);
            return;
        }
        $siteId = $site->id;

        $this->seedGoogleReserve($tenantId, $siteId);
        $this->seedMarketplaces($tenantId, $siteId);
        $this->seedCallTracking($tenantId, $siteId);
        $this->seedKiosks($tenantId, $siteId);
    }

    protected function seedGoogleReserve(int $tenantId, int $siteId): void
    {
        // Settings
        DB::table('google_reserve_settings')->insertOrIgnore([
            'tenant_id' => $tenantId,
            'site_id' => $siteId,
            'is_enabled' => true,
            'merchant_id' => 'demo-merchant-123',
            'place_id' => 'ChIJDemo123456789',
            'available_days' => json_encode([1, 2, 3, 4, 5, 6]),
            'opening_time' => '09:00',
            'closing_time' => '18:00',
            'slot_duration_minutes' => 30,
            'max_advance_days' => 30,
            'min_advance_hours' => 2,
            'auto_confirm' => true,
            'require_deposit' => false,
            'notify_on_booking' => true,
            'send_customer_confirmation' => true,
            'send_reminder' => true,
            'reminder_hours_before' => 24,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Bookings
        $statuses = ['pending', 'confirmed', 'completed', 'converted', 'cancelled_by_customer', 'no_show'];
        $serviceTypes = ['visit', 'move_in', 'consultation'];

        for ($i = 0; $i < 25; $i++) {
            $bookingDate = Carbon::now()->subDays(rand(-7, 30));
            $status = $statuses[array_rand($statuses)];
            $startHour = rand(9, 17);

            DB::table('google_reserve_bookings')->insert([
                'uuid' => Str::uuid(),
                'tenant_id' => $tenantId,
                'site_id' => $siteId,
                'google_booking_id' => 'GR-' . Str::random(10),
                'customer_name' => fake()->name(),
                'customer_email' => fake()->email(),
                'customer_phone' => fake()->phoneNumber(),
                'service_type' => $serviceTypes[array_rand($serviceTypes)],
                'booking_date' => $bookingDate->toDateString(),
                'start_time' => sprintf('%02d:00', $startHour),
                'end_time' => sprintf('%02d:30', $startHour),
                'status' => $status,
                'customer_notes' => rand(0, 1) ? fake()->sentence() : null,
                'converted_value' => $status === 'converted' ? rand(50, 200) : null,
                'confirmed_at' => in_array($status, ['confirmed', 'completed', 'converted']) ? $bookingDate->copy()->subDays(rand(1, 3)) : null,
                'completed_at' => in_array($status, ['completed', 'converted']) ? $bookingDate : null,
                'created_at' => $bookingDate->copy()->subDays(rand(1, 7)),
                'updated_at' => now(),
            ]);
        }

        // Slots for next 14 days
        for ($d = 0; $d < 14; $d++) {
            $date = Carbon::now()->addDays($d);
            if ($date->dayOfWeek == 0) continue; // Skip Sundays

            for ($h = 9; $h < 18; $h++) {
                DB::table('google_reserve_slots')->insert([
                    'tenant_id' => $tenantId,
                    'site_id' => $siteId,
                    'date' => $date->toDateString(),
                    'start_time' => sprintf('%02d:00', $h),
                    'end_time' => sprintf('%02d:30', $h),
                    'max_bookings' => 2,
                    'current_bookings' => rand(0, 2),
                    'is_available' => true,
                    'is_blocked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Sync logs
        for ($i = 0; $i < 10; $i++) {
            $startedAt = Carbon::now()->subDays(rand(0, 30));
            DB::table('google_reserve_sync_logs')->insert([
                'tenant_id' => $tenantId,
                'sync_type' => ['availability', 'bookings'][rand(0, 1)],
                'direction' => ['push', 'pull'][rand(0, 1)],
                'status' => ['success', 'success', 'success', 'partial', 'failed'][rand(0, 4)],
                'records_processed' => rand(10, 100),
                'records_created' => rand(0, 10),
                'records_updated' => rand(0, 20),
                'errors_count' => rand(0, 3),
                'started_at' => $startedAt,
                'completed_at' => $startedAt->copy()->addMinutes(rand(1, 5)),
                'created_at' => $startedAt,
                'updated_at' => now(),
            ]);
        }
    }

    protected function seedMarketplaces(int $tenantId, int $siteId): void
    {
        $platforms = ['sparefoot', 'selfstorage', 'jestocke', 'costockage', 'google_business'];

        foreach ($platforms as $platform) {
            $integrationId = DB::table('marketplace_integrations')->insertGetId([
                'tenant_id' => $tenantId,
                'platform' => $platform,
                'platform_account_id' => 'ACC-' . strtoupper($platform) . '-123',
                'api_key' => 'demo-api-key-' . $platform,
                'is_active' => true,
                'auto_sync_inventory' => true,
                'auto_sync_prices' => true,
                'auto_accept_leads' => false,
                'sync_interval_minutes' => 60,
                'commission_percent' => rand(5, 15),
                'lead_cost' => rand(10, 30),
                'commission_type' => 'percent',
                'last_sync_at' => now()->subHours(rand(1, 12)),
                'created_at' => now()->subMonths(rand(1, 6)),
                'updated_at' => now(),
            ]);

            // Leads for each platform
            $leadStatuses = ['new', 'contacted', 'qualified', 'converted', 'lost'];
            $leadsCount = rand(5, 15);

            for ($i = 0; $i < $leadsCount; $i++) {
                $createdAt = Carbon::now()->subDays(rand(0, 60));
                $status = $leadStatuses[array_rand($leadStatuses)];

                DB::table('marketplace_leads')->insert([
                    'uuid' => Str::uuid(),
                    'tenant_id' => $tenantId,
                    'integration_id' => $integrationId,
                    'site_id' => $siteId,
                    'external_lead_id' => 'LEAD-' . strtoupper($platform) . '-' . Str::random(8),
                    'platform' => $platform,
                    'customer_name' => fake()->name(),
                    'customer_email' => fake()->email(),
                    'customer_phone' => fake()->phoneNumber(),
                    'unit_size_requested' => ['small', 'medium', 'large'][rand(0, 2)],
                    'move_in_date' => Carbon::now()->addDays(rand(1, 30))->toDateString(),
                    'message' => fake()->paragraph(),
                    'status' => $status,
                    'source_url' => 'https://' . $platform . '.com/listing/' . rand(1000, 9999),
                    'lead_cost' => rand(5, 25),
                    'converted_value' => $status === 'converted' ? rand(100, 500) : null,
                    'first_contacted_at' => in_array($status, ['contacted', 'qualified', 'converted']) ? $createdAt->copy()->addHours(rand(1, 24)) : null,
                    'converted_at' => $status === 'converted' ? $createdAt->copy()->addDays(rand(1, 7)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => now(),
                ]);
            }

            // Analytics
            for ($d = 30; $d >= 0; $d--) {
                $impressions = rand(100, 500);
                $clicks = rand(5, 30);
                $leads = rand(1, 10);
                $conversions = rand(0, 2);
                $spend = rand(10, 50);
                $revenue = $conversions * rand(80, 150);

                DB::table('marketplace_analytics')->insert([
                    'tenant_id' => $tenantId,
                    'integration_id' => $integrationId,
                    'date' => Carbon::now()->subDays($d)->toDateString(),
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'leads' => $leads,
                    'conversions' => $conversions,
                    'spend' => $spend,
                    'revenue' => $revenue,
                    'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                    'conversion_rate' => $leads > 0 ? round(($conversions / $leads) * 100, 2) : 0,
                    'cost_per_lead' => $leads > 0 ? round($spend / $leads, 2) : null,
                    'cost_per_conversion' => $conversions > 0 ? round($spend / $conversions, 2) : null,
                    'roas' => $spend > 0 ? round($revenue / $spend, 2) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    protected function seedCallTracking(int $tenantId, int $siteId): void
    {
        // Settings
        DB::table('call_tracking_settings')->insertOrIgnore([
            'tenant_id' => $tenantId,
            'is_enabled' => true,
            'provider' => 'twilio',
            'record_calls' => true,
            'recording_retention_days' => 90,
            'transcribe_calls' => true,
            'notify_missed_calls' => true,
            'notify_after_hours' => true,
            'notification_email' => 'contact@demo-storage.fr',
            'business_hours' => json_encode([
                'monday' => ['09:00', '18:00'],
                'tuesday' => ['09:00', '18:00'],
                'wednesday' => ['09:00', '18:00'],
                'thursday' => ['09:00', '18:00'],
                'friday' => ['09:00', '18:00'],
                'saturday' => ['10:00', '16:00'],
            ]),
            'timezone' => 'Europe/Paris',
            'enable_voicemail' => true,
            'enable_sms_autoresponse' => true,
            'sms_autoresponse_message' => 'Merci pour votre appel. Nous vous rappellerons rapidement.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tracking numbers
        $sources = ['google_ads', 'facebook', 'website', 'flyer', 'billboard'];

        foreach ($sources as $source) {
            $numberId = DB::table('tracking_numbers')->insertGetId([
                'tenant_id' => $tenantId,
                'site_id' => $siteId,
                'phone_number' => '+33' . rand(6, 7) . rand(10000000, 99999999),
                'friendly_name' => ucfirst(str_replace('_', ' ', $source)),
                'forward_to' => '+33123456789',
                'source' => $source,
                'medium' => $source === 'google_ads' ? 'cpc' : ($source === 'facebook' ? 'social' : 'organic'),
                'campaign' => $source === 'google_ads' ? 'storage-2024' : null,
                'number_type' => 'local',
                'is_active' => true,
                'sms_enabled' => true,
                'mms_enabled' => false,
                'total_calls' => rand(50, 200),
                'total_sms' => rand(10, 50),
                'last_call_at' => now()->subHours(rand(1, 48)),
                'created_at' => now()->subMonths(rand(1, 6)),
                'updated_at' => now(),
            ]);

            // Call records
            $callStatuses = ['completed', 'completed', 'completed', 'no_answer', 'voicemail', 'busy'];

            for ($i = 0; $i < rand(10, 30); $i++) {
                $startedAt = Carbon::now()->subDays(rand(0, 30))->setTime(rand(8, 19), rand(0, 59));
                $status = $callStatuses[array_rand($callStatuses)];
                $talkDuration = $status === 'completed' ? rand(30, 600) : 0;
                $ringDuration = rand(5, 20);

                DB::table('call_records')->insert([
                    'uuid' => Str::uuid(),
                    'tenant_id' => $tenantId,
                    'tracking_number_id' => $numberId,
                    'site_id' => $siteId,
                    'call_sid' => 'CALL-' . Str::random(12),
                    'from_number' => '+33' . rand(6, 7) . rand(10000000, 99999999),
                    'to_number' => '+33123456789',
                    'direction' => 'inbound',
                    'status' => $status,
                    'started_at' => $startedAt,
                    'answered_at' => $status === 'completed' ? $startedAt->copy()->addSeconds($ringDuration) : null,
                    'ended_at' => $startedAt->copy()->addSeconds($ringDuration + $talkDuration),
                    'ring_duration_seconds' => $ringDuration,
                    'talk_duration_seconds' => $talkDuration,
                    'total_duration_seconds' => $ringDuration + $talkDuration,
                    'was_recorded' => $status === 'completed' && rand(0, 1),
                    'source' => $source,
                    'medium' => $source === 'google_ads' ? 'cpc' : 'organic',
                    'requires_callback' => $status === 'no_answer' && rand(0, 1),
                    'converted' => $status === 'completed' && rand(0, 100) < 20,
                    'converted_value' => ($status === 'completed' && rand(0, 100) < 20) ? rand(80, 200) : null,
                    'notes' => rand(0, 1) ? fake()->sentence() : null,
                    'created_at' => $startedAt,
                    'updated_at' => now(),
                ]);
            }

            // Call Analytics
            for ($d = 30; $d >= 0; $d--) {
                $totalCalls = rand(5, 25);
                $answeredCalls = rand(3, $totalCalls);
                $missedCalls = $totalCalls - $answeredCalls;

                DB::table('call_analytics')->insertOrIgnore([
                    'tenant_id' => $tenantId,
                    'site_id' => $siteId,
                    'date' => Carbon::now()->subDays($d)->toDateString(),
                    'source' => $source,
                    'total_calls' => $totalCalls,
                    'answered_calls' => $answeredCalls,
                    'missed_calls' => $missedCalls,
                    'voicemail_calls' => rand(0, $missedCalls),
                    'unique_callers' => rand(3, $totalCalls),
                    'total_talk_seconds' => $answeredCalls * rand(60, 300),
                    'avg_talk_seconds' => rand(60, 300),
                    'answer_rate' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 2) : 0,
                    'converted_calls' => rand(0, min(3, $answeredCalls)),
                    'total_revenue' => rand(0, 3) * rand(80, 150),
                    'total_sms_in' => rand(0, 5),
                    'total_sms_out' => rand(0, 10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    protected function seedKiosks(int $tenantId, int $siteId): void
    {
        $kioskNames = ['Kiosque Entrée', 'Kiosque Hall Principal', 'Kiosque Parking'];

        foreach ($kioskNames as $index => $name) {
            $kioskId = DB::table('kiosk_devices')->insertGetId([
                'uuid' => Str::uuid(),
                'tenant_id' => $tenantId,
                'site_id' => $siteId,
                'device_code' => 'KIOSK-' . strtoupper(Str::random(6)),
                'name' => $name,
                'location_description' => 'Niveau ' . ($index === 2 ? '-1' : '0'),
                'device_type' => 'tablet',
                'is_active' => true,
                'is_online' => rand(0, 1),
                'language' => 'fr',
                'allow_new_rentals' => true,
                'allow_payments' => true,
                'allow_access_code_generation' => true,
                'show_prices' => true,
                'require_id_verification' => $index === 0,
                'primary_color' => '#4F46E5',
                'secondary_color' => '#10B981',
                'welcome_message' => 'Bienvenue ! Comment puis-je vous aider ?',
                'idle_timeout_seconds' => 120,
                'enable_screensaver' => true,
                'last_heartbeat_at' => now()->subMinutes(rand(1, 10)),
                'created_at' => now()->subMonths(rand(1, 3)),
                'updated_at' => now(),
            ]);

            // Sessions
            $purposes = ['browse', 'new_rental', 'payment', 'access_code', 'account'];
            $outcomes = ['completed', 'completed', 'abandoned', 'timeout'];

            for ($i = 0; $i < rand(30, 80); $i++) {
                $startedAt = Carbon::now()->subDays(rand(0, 30))->setTime(rand(6, 22), rand(0, 59));
                $purpose = $purposes[array_rand($purposes)];
                $outcome = $outcomes[array_rand($outcomes)];
                $duration = $outcome === 'completed' ? rand(60, 600) : rand(10, 120);

                DB::table('kiosk_sessions')->insert([
                    'uuid' => Str::uuid(),
                    'kiosk_id' => $kioskId,
                    'tenant_id' => $tenantId,
                    'site_id' => $siteId,
                    'session_token' => Str::random(64),
                    'purpose' => $purpose,
                    'outcome' => $outcome,
                    'started_at' => $startedAt,
                    'ended_at' => $startedAt->copy()->addSeconds($duration),
                    'duration_seconds' => $duration,
                    'viewed_boxes' => rand(0, 1),
                    'started_rental' => $purpose === 'new_rental' && rand(0, 1),
                    'completed_rental' => $purpose === 'new_rental' && $outcome === 'completed' && rand(0, 1),
                    'made_payment' => $purpose === 'payment' && $outcome === 'completed',
                    'generated_access_code' => $purpose === 'access_code' && $outcome === 'completed',
                    'transaction_amount' => ($purpose === 'payment' && $outcome === 'completed') ? rand(50, 200) : null,
                    'created_at' => $startedAt,
                    'updated_at' => now(),
                ]);
            }

            // Issues (quelques-uns seulement)
            if ($index === 0) {
                DB::table('kiosk_issues')->insert([
                    'tenant_id' => $tenantId,
                    'kiosk_id' => $kioskId,
                    'type' => 'printer_issue',
                    'title' => 'Imprimante bloquée',
                    'description' => 'Le papier est coincé dans l\'imprimante de reçus',
                    'severity' => 'medium',
                    'status' => 'open',
                    'reported_by' => 1,
                    'created_at' => now()->subDays(2),
                    'updated_at' => now(),
                ]);
            }

            // Analytics quotidiennes
            for ($d = 30; $d >= 0; $d--) {
                $totalSessions = rand(5, 25);
                $completedSessions = rand(3, $totalSessions);
                $abandonedSessions = $totalSessions - $completedSessions;

                DB::table('kiosk_analytics')->insert([
                    'tenant_id' => $tenantId,
                    'kiosk_id' => $kioskId,
                    'date' => Carbon::now()->subDays($d)->toDateString(),
                    'total_sessions' => $totalSessions,
                    'unique_visitors' => (int)($totalSessions * 0.8),
                    'avg_session_duration_seconds' => rand(120, 300),
                    'browse_sessions' => rand(1, 5),
                    'rental_sessions' => rand(0, 3),
                    'payment_sessions' => rand(1, 5),
                    'access_code_sessions' => rand(2, 8),
                    'completed_sessions' => $completedSessions,
                    'abandoned_sessions' => $abandonedSessions,
                    'timeout_sessions' => rand(0, 2),
                    'rentals_started' => rand(0, 3),
                    'rentals_completed' => rand(0, 2),
                    'rental_conversion_rate' => rand(40, 80),
                    'payments_completed' => rand(1, 5),
                    'access_codes_generated' => rand(2, 10),
                    'total_revenue' => rand(0, 5) * rand(50, 150),
                    'rental_revenue' => rand(0, 3) * rand(80, 150),
                    'payment_revenue' => rand(1, 5) * rand(50, 100),
                    'hourly_sessions' => json_encode(array_map(fn() => rand(0, 3), range(0, 23))),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
