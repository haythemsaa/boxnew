<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class FixModels extends Command
{
    protected $signature = 'fix:models {--dry-run : Show changes without applying}';
    protected $description = 'Automatically fix model fillable and casts to match database schema';

    protected $fixedCount = 0;
    protected $changes = [];

    public function handle()
    {
        $this->info('🔧 Correction automatique des modèles...');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        $modelPath = app_path('Models');
        $files = File::glob($modelPath . '/*.php');

        foreach ($files as $file) {
            $className = 'App\\Models\\' . pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($className)) {
                continue;
            }

            // Skip non-Eloquent models
            if (!is_subclass_of($className, \Illuminate\Database\Eloquent\Model::class)) {
                continue;
            }

            try {
                $model = new $className;
                $table = $model->getTable();

                if (!Schema::hasTable($table)) {
                    $this->warn("⚠️ Table '{$table}' n'existe pas pour " . basename($file));
                    continue;
                }

                $this->fixModel($file, $table, $dryRun);

            } catch (\Exception $e) {
                $this->error("❌ Erreur pour " . basename($file) . ": " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════════");

        if ($dryRun) {
            $this->info("🔍 Mode dry-run: {$this->fixedCount} fichiers seraient modifiés");
            $this->info("   Exécutez sans --dry-run pour appliquer les corrections");
        } else {
            $this->info("✅ {$this->fixedCount} modèles corrigés!");
        }

        return 0;
    }

    protected function fixModel(string $file, string $table, bool $dryRun)
    {
        $content = File::get($file);
        $originalContent = $content;
        $filename = basename($file);

        // Get actual columns from database
        $actualColumns = Schema::getColumnListing($table);

        // Fix fillable array
        $content = $this->fixFillable($content, $actualColumns, $filename);

        // Fix casts array
        $content = $this->fixCasts($content, $actualColumns, $table, $filename);

        // If content changed, save it
        if ($content !== $originalContent) {
            $this->fixedCount++;

            if ($dryRun) {
                $this->line("📝 " . $filename . " serait modifié");
            } else {
                File::put($file, $content);
                $this->info("✅ " . $filename . " corrigé");
            }
        }
    }

    protected function fixFillable(string $content, array $actualColumns, string $filename): string
    {
        // Match fillable array
        if (preg_match('/protected\s+\$fillable\s*=\s*\[([\s\S]*?)\];/', $content, $match)) {
            $fillableBlock = $match[1];

            // Extract column names
            preg_match_all("/['\"]([^'\"]+)['\"]/", $fillableBlock, $columns);
            $fillableColumns = $columns[1];

            // Filter to only existing columns
            $validColumns = array_filter($fillableColumns, function ($col) use ($actualColumns) {
                return in_array($col, $actualColumns);
            });

            // Rebuild fillable array
            if (count($validColumns) !== count($fillableColumns)) {
                $removed = array_diff($fillableColumns, $validColumns);
                if (!empty($removed)) {
                    $this->line("   - Supprimé de fillable: " . implode(', ', $removed));
                }

                $newFillable = $this->formatArrayForPHP($validColumns);
                $content = preg_replace(
                    '/protected\s+\$fillable\s*=\s*\[[\s\S]*?\];/',
                    "protected \$fillable = [\n" . $newFillable . "    ];",
                    $content
                );
            }
        }

        return $content;
    }

    protected function fixCasts(string $content, array $actualColumns, string $table, string $filename): string
    {
        // Match casts array
        if (preg_match('/protected\s+\$casts\s*=\s*\[([\s\S]*?)\];/', $content, $match)) {
            $castsBlock = $match[1];

            // Extract cast definitions
            preg_match_all("/['\"]([^'\"]+)['\"]\s*=>\s*['\"]?([^,'\"\]]+)['\"]?/", $castsBlock, $casts, PREG_SET_ORDER);

            $validCasts = [];
            foreach ($casts as $cast) {
                $column = $cast[1];
                $type = trim($cast[2]);

                // Keep 'id' cast and existing columns
                if ($column === 'id' || in_array($column, $actualColumns)) {
                    $validCasts[$column] = $type;
                } else {
                    $this->line("   - Supprimé de casts: {$column}");
                }
            }

            // Add appropriate casts based on column types
            $columnTypes = $this->getColumnTypes($table);
            foreach ($columnTypes as $column => $type) {
                if (!isset($validCasts[$column]) && in_array($column, $actualColumns)) {
                    if ($type === 'json' || $type === 'text') {
                        // Check if column name suggests it should be array
                        if (preg_match('/(config|settings|data|items|options|metadata|photos|documents|results)$/', $column)) {
                            $validCasts[$column] = 'array';
                        }
                    } elseif (in_array($type, ['datetime', 'timestamp'])) {
                        if ($column !== 'created_at' && $column !== 'updated_at') {
                            $validCasts[$column] = 'datetime';
                        }
                    } elseif ($type === 'date') {
                        $validCasts[$column] = 'date';
                    } elseif ($type === 'tinyint(1)' || $column === 'is_active' || preg_match('/^(is_|has_|can_|allow_)/', $column)) {
                        $validCasts[$column] = 'boolean';
                    } elseif (preg_match('/decimal|float|double/', $type)) {
                        $validCasts[$column] = 'decimal:2';
                    }
                }
            }

            if (!empty($validCasts)) {
                $newCasts = $this->formatCastsForPHP($validCasts);
                $content = preg_replace(
                    '/protected\s+\$casts\s*=\s*\[[\s\S]*?\];/',
                    "protected \$casts = [\n" . $newCasts . "    ];",
                    $content
                );
            }
        }

        return $content;
    }

    protected function getColumnTypes(string $table): array
    {
        $types = [];
        $columns = DB::select("DESCRIBE {$table}");

        foreach ($columns as $column) {
            $types[$column->Field] = $column->Type;
        }

        return $types;
    }

    protected function formatArrayForPHP(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $lines = [];
        foreach ($items as $item) {
            $lines[] = "        '{$item}',";
        }

        return implode("\n", $lines) . "\n";
    }

    protected function formatCastsForPHP(array $casts): string
    {
        if (empty($casts)) {
            return '';
        }

        $lines = [];
        foreach ($casts as $column => $type) {
            $lines[] = "        '{$column}' => '{$type}',";
        }

        return implode("\n", $lines) . "\n";
    }
}
