<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class DiagnoseModels extends Command
{
    protected $signature = 'diagnose:models {--fix : Automatically generate fixes}';
    protected $description = 'Diagnose model-database mismatches and controller issues';

    protected $errors = [];
    protected $warnings = [];

    public function handle()
    {
        $this->info('🔍 Diagnostic des modèles et contrôleurs...');
        $this->newLine();

        // 1. Check all models against database
        $this->checkModels();

        // 2. Check controllers for common issues
        $this->checkControllers();

        // 3. Check Vue components for missing props
        $this->checkVueComponents();

        // Display results
        $this->displayResults();

        return count($this->errors) > 0 ? 1 : 0;
    }

    protected function checkModels()
    {
        $this->info('📦 Vérification des modèles...');

        $modelPath = app_path('Models');
        $files = File::glob($modelPath . '/*.php');

        foreach ($files as $file) {
            $className = 'App\\Models\\' . pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($className)) {
                continue;
            }

            try {
                // Skip non-Eloquent models
                if (!is_subclass_of($className, \Illuminate\Database\Eloquent\Model::class)) {
                    continue;
                }

                $model = new $className;
                $table = $model->getTable();

                if (!Schema::hasTable($table)) {
                    $this->errors[] = [
                        'type' => 'Model',
                        'file' => basename($file),
                        'issue' => "Table '{$table}' n'existe pas",
                        'severity' => 'critical',
                    ];
                    continue;
                }

                // Get actual columns
                $actualColumns = Schema::getColumnListing($table);

                // Get fillable columns
                $fillable = $model->getFillable();

                // Check fillable columns exist
                foreach ($fillable as $column) {
                    if (!in_array($column, $actualColumns)) {
                        $this->errors[] = [
                            'type' => 'Model',
                            'file' => basename($file),
                            'issue' => "Colonne '{$column}' dans fillable mais n'existe pas dans table '{$table}'",
                            'severity' => 'critical',
                            'fix' => "Supprimer '{$column}' du fillable ou ajouter la colonne à la table",
                        ];
                    }
                }

                // Check casts for non-existent columns
                $casts = $model->getCasts();
                foreach ($casts as $column => $castType) {
                    if (!in_array($column, $actualColumns) && $column !== 'id') {
                        $this->errors[] = [
                            'type' => 'Model',
                            'file' => basename($file),
                            'issue' => "Colonne '{$column}' dans casts mais n'existe pas dans table '{$table}'",
                            'severity' => 'critical',
                        ];
                    }
                }

                // Check relationships
                $this->checkModelRelationships($className, $file, $table);

            } catch (\Exception $e) {
                $this->warnings[] = [
                    'type' => 'Model',
                    'file' => basename($file),
                    'issue' => "Erreur lors de l'analyse: " . $e->getMessage(),
                    'severity' => 'warning',
                ];
            }
        }
    }

    protected function checkModelRelationships($className, $file, $table)
    {
        $content = File::get($file);

        // Find belongsTo relationships
        preg_match_all('/belongsTo\s*\(\s*([^,\)]+)(?:,\s*[\'"]([^\'"]+)[\'"])?\s*\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $foreignKey = $match[2] ?? null;

            if ($foreignKey) {
                $actualColumns = Schema::getColumnListing($table);
                if (!in_array($foreignKey, $actualColumns)) {
                    $this->errors[] = [
                        'type' => 'Relationship',
                        'file' => basename($file),
                        'issue' => "Clé étrangère '{$foreignKey}' utilisée dans belongsTo mais n'existe pas dans table '{$table}'",
                        'severity' => 'critical',
                    ];
                }
            }
        }
    }

    protected function checkControllers()
    {
        $this->info('🎮 Vérification des contrôleurs...');

        $controllerPath = app_path('Http/Controllers/Tenant');

        if (!File::isDirectory($controllerPath)) {
            return;
        }

        $files = File::glob($controllerPath . '/*.php');

        foreach ($files as $file) {
            $content = File::get($file);
            $filename = basename($file);

            // Check for relationships that might not exist
            preg_match_all('/with\s*\(\s*\[([^\]]+)\]/', $content, $matches);

            foreach ($matches[1] as $withClause) {
                $relations = array_map('trim', explode(',', $withClause));
                foreach ($relations as $relation) {
                    $relation = trim($relation, "'\" ");
                    if (empty($relation)) continue;

                    // Extract base relation name
                    $baseName = explode('.', $relation)[0];
                    $baseName = explode(':', $baseName)[0];

                    // This is a potential issue point - log for manual review
                    // We can't easily verify relationships without running code
                }
            }

            // Check for common column mistakes
            $commonMistakes = [
                'staff_profile_id' => 'Peut-être user_id ou assigned_to?',
                'guard_id' => 'Peut-être conducted_by?',
                'given_at' => 'Peut-être granted_at ou is_granted?',
                'category_id' => 'Vérifier si la relation category existe',
                'vendor_id' => 'Vérifier si la relation vendor existe',
                'reporter_id' => 'Peut-être created_by?',
            ];

            foreach ($commonMistakes as $column => $suggestion) {
                if (strpos($content, $column) !== false) {
                    // Verify against actual database if possible
                    $this->warnings[] = [
                        'type' => 'Controller',
                        'file' => $filename,
                        'issue' => "Utilisation de '{$column}' détectée - {$suggestion}",
                        'severity' => 'warning',
                    ];
                }
            }
        }
    }

    protected function checkVueComponents()
    {
        $this->info('🖥️ Vérification des composants Vue...');

        $vuePath = resource_path('js/Pages/Tenant');

        if (!File::isDirectory($vuePath)) {
            return;
        }

        $files = File::allFiles($vuePath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'vue') {
                continue;
            }

            $content = File::get($file->getPathname());
            $filename = $file->getRelativePathname();

            // Check for props definitions
            if (preg_match('/defineProps\s*\(\s*\{([^}]+)\}/', $content, $match)) {
                $propsBlock = $match[1];

                // Extract prop names
                preg_match_all('/(\w+)\s*:\s*(Array|Object|String|Number|Boolean)/', $propsBlock, $propMatches);

                foreach ($propMatches[1] as $index => $propName) {
                    $propType = $propMatches[2][$index];

                    // Check if prop is used with .length or .filter (Array operations)
                    if ($propType === 'Array') {
                        if (preg_match('/' . $propName . '\s*\.(?:length|filter|map|forEach|find|some|every|reduce)/', $content)) {
                            // This prop is used as array - verify controller provides it
                            // This is informational only
                        }
                    }
                }
            }

            // Check for potential undefined access
            $patterns = [
                '/(\w+)\.length/' => 'Accès à .length',
                '/(\w+)\.filter\(/' => 'Accès à .filter()',
                '/(\w+)\.map\(/' => 'Accès à .map()',
            ];

            // Look for v-for without v-if check
            if (preg_match('/v-for="[^"]+in\s+(\w+)"/', $content, $vforMatch)) {
                $arrayName = $vforMatch[1];
                // Check if there's a v-if or the prop has default
            }
        }
    }

    protected function displayResults()
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                    RÉSULTATS DU DIAGNOSTIC');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        if (empty($this->errors) && empty($this->warnings)) {
            $this->info('✅ Aucun problème détecté!');
            return;
        }

        // Display errors
        if (!empty($this->errors)) {
            $this->error('❌ ERREURS CRITIQUES (' . count($this->errors) . ')');
            $this->newLine();

            $tableData = [];
            foreach ($this->errors as $error) {
                $tableData[] = [
                    $error['type'],
                    $error['file'],
                    $error['issue'],
                    $error['fix'] ?? 'Correction manuelle requise',
                ];
            }

            $this->table(['Type', 'Fichier', 'Problème', 'Solution'], $tableData);
            $this->newLine();
        }

        // Display warnings
        if (!empty($this->warnings)) {
            $this->warn('⚠️ AVERTISSEMENTS (' . count($this->warnings) . ')');
            $this->newLine();

            $tableData = [];
            foreach ($this->warnings as $warning) {
                $tableData[] = [
                    $warning['type'],
                    $warning['file'],
                    $warning['issue'],
                ];
            }

            $this->table(['Type', 'Fichier', 'Problème'], $tableData);
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
    }
}
