<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentRelationBuilder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:model-relations {database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and generate model relationships based on database foreign key constraints.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $databaseName = $this->argument('database');

        $query = "
            SELECT 
                TABLE_NAME, 
                COLUMN_NAME, 
                CONSTRAINT_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_SCHEMA = :database;
        ";

        $results = DB::select(DB::raw($query), ['database' => $databaseName]);

        if (empty($results)) {
            $this->info("No foreign key constraints found in the database: {$databaseName}");
            return Command::SUCCESS;
        }

        foreach ($results as $relation) {
            $relation = (array) $relation;

            $mainModel = $this->getModelName($relation['TABLE_NAME']);
            $relatedModel = $this->getModelName($relation['REFERENCED_TABLE_NAME']);

            if ($mainModel && $relatedModel) {
                $this->checkAndGenerateRelation($mainModel, $relatedModel, $relation);
            } else {
                $this->warn("Models for tables {$relation['TABLE_NAME']} or {$relation['REFERENCED_TABLE_NAME']} not found.");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Get the fully qualified model name based on the table name.
     *
     * @param string $tableName
     * @return string|false
     */
    private function getModelName($tableName)
    {
        $className = 'App\\' . Str::studly(Str::singular($tableName));

        if (class_exists($className)) {
            $model = new $className;

            if ($model instanceof \Illuminate\Database\Eloquent\Model && $model->getTable() === $tableName) {
                return $className;
            }
        }

        return false;
    }

    /**
     * Check and generate the relationships.
     *
     * @param string $mainModel
     * @param string $relatedModel
     * @param array $relation
     * @return void
     */
    private function checkAndGenerateRelation($mainModel, $relatedModel, $relation)
    {
        $mainModelId = $relation['COLUMN_NAME'];
        $relatedModelId = $relation['REFERENCED_COLUMN_NAME'];

        // BelongsTo relationship
        if (!method_exists($mainModel, Str::singular($relation['REFERENCED_TABLE_NAME']))) {
            $this->line(
                "In {$mainModel}.php add the following function:\n" .
                "/**\n * Relationship with model {$relatedModel}.\n */\n" .
                "public function " . Str::singular($relation['REFERENCED_TABLE_NAME']) . "() {\n" .
                "    return \$this->belongsTo('{$relatedModel}', '{$mainModelId}', '{$relatedModelId}');\n" .
                "}\n"
            );
        } else {
            $this->info("{$mainModel} already has " . Str::singular($relation['REFERENCED_TABLE_NAME']) . "() method.");
        }

        // HasMany relationship
        if (!method_exists($relatedModel, Str::singular($relation['TABLE_NAME']))) {
            $this->line(
                "In {$relatedModel}.php add the following function:\n" .
                "/**\n * Relationship with model {$mainModel}.\n */\n" .
                "public function " . Str::singular($relation['TABLE_NAME']) . "() {\n" .
                "    return \$this->hasMany('{$mainModel}', '{$mainModelId}');\n" .
                "}\n"
            );
        } else {
            $this->info("{$relatedModel} already has " . Str::singular($relation['TABLE_NAME']) . "() method.");
        }
    }
}
