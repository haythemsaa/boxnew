<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function index()
    {
        $backups = SystemBackup::with('creator:id,name')
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => SystemBackup::count(),
            'completed' => SystemBackup::completed()->count(),
            'failed' => SystemBackup::failed()->count(),
            'total_size' => SystemBackup::completed()->sum('size'),
        ];

        // Disk info
        $diskInfo = [
            'total' => disk_total_space(storage_path()),
            'free' => disk_free_space(storage_path()),
            'used' => disk_total_space(storage_path()) - disk_free_space(storage_path()),
        ];

        return Inertia::render('SuperAdmin/Backups/Index', [
            'backups' => $backups,
            'stats' => $stats,
            'diskInfo' => $diskInfo,
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:full,database,files',
            'name' => 'nullable|string|max:255',
        ]);

        $name = $validated['name'] ?? 'backup-' . now()->format('Y-m-d-His');

        $backup = SystemBackup::create([
            'name' => $name,
            'disk' => 'local',
            'path' => '',
            'size' => 0,
            'type' => $validated['type'],
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        // In a real app, this would be dispatched to a job queue
        $this->runBackup($backup);

        return back()->with('success', 'Backup initié.');
    }

    public function show(SystemBackup $backup)
    {
        $backup->load('creator:id,name');

        return Inertia::render('SuperAdmin/Backups/Show', [
            'backup' => $backup,
        ]);
    }

    public function download(SystemBackup $backup)
    {
        if ($backup->status !== 'completed' || !Storage::disk($backup->disk)->exists($backup->path)) {
            return back()->with('error', 'Le fichier de backup n\'est pas disponible.');
        }

        return Storage::disk($backup->disk)->download($backup->path);
    }

    public function destroy(SystemBackup $backup)
    {
        // Delete the actual file if exists
        if ($backup->path && Storage::disk($backup->disk)->exists($backup->path)) {
            Storage::disk($backup->disk)->delete($backup->path);
        }

        $backup->delete();

        return back()->with('success', 'Backup supprimé.');
    }

    public function cleanOld(Request $request)
    {
        $days = $request->input('days', 30);

        $oldBackups = SystemBackup::where('created_at', '<', now()->subDays($days))->get();

        foreach ($oldBackups as $backup) {
            if ($backup->path && Storage::disk($backup->disk)->exists($backup->path)) {
                Storage::disk($backup->disk)->delete($backup->path);
            }
            $backup->delete();
        }

        return back()->with('success', count($oldBackups) . ' anciens backups supprimés.');
    }

    private function runBackup(SystemBackup $backup): void
    {
        $backup->markAsStarted();

        try {
            $filename = $backup->name . '.sql';
            $path = 'backups/' . $filename;

            // Simple database backup using mysqldump
            if (in_array($backup->type, ['full', 'database'])) {
                $dbName = config('database.connections.mysql.database');
                $dbUser = config('database.connections.mysql.username');
                $dbPass = config('database.connections.mysql.password');
                $dbHost = config('database.connections.mysql.host');

                $outputPath = storage_path('app/' . $path);

                // Make sure directory exists
                if (!is_dir(dirname($outputPath))) {
                    mkdir(dirname($outputPath), 0755, true);
                }

                // Note: In production, use a proper backup library or job queue
                $command = sprintf(
                    'mysqldump -h%s -u%s -p%s %s > %s 2>&1',
                    escapeshellarg($dbHost),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbPass),
                    escapeshellarg($dbName),
                    escapeshellarg($outputPath)
                );

                exec($command, $output, $returnCode);

                if ($returnCode === 0 && file_exists($outputPath)) {
                    $size = filesize($outputPath);
                    $backup->markAsCompleted($path, $size);
                } else {
                    $backup->markAsFailed('Backup command failed with code: ' . $returnCode);
                }
            } else {
                // Files backup - simplified
                $backup->markAsFailed('Files backup not implemented yet.');
            }
        } catch (\Exception $e) {
            $backup->markAsFailed($e->getMessage());
        }
    }
}
