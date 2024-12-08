<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Method to get the model name based on the table name.
 * Initially checks the default namespace App.
 *
 * @param string $tableName
 * @return string|false
 */
function getModelName($tableName) {
    $className = 'App\\' . Str::studly(Str::singular($tableName));

    if (class_exists($className)) {
        $model = new $className;
    }

    if (isset($model) && is_subclass_of($model, 'Illuminate\Database\Eloquent\Model')) {
        if ($model->getTable() === $tableName) {
            return $className;
        }
    }

    return false;
}

/*
 * Add this route to your desired routes/*.php file if you are working with Laravel >= 5.2.
 * Example: Add to web.php.
 * Visit /model-relations-check on your host to view model relationship status.
 */

Route::get('model-relations-check', function () {
    $databaseName = 'yourdatabase'; // Replace with your database name.
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

    /**
     * Check and get all relational tables from the database.
     */
    foreach ($results as $relation) {
        $relation = (array) $relation;

        $mainModel = getModelName($relation['TABLE_NAME']);
        $relatedModel = getModelName($relation['REFERENCED_TABLE_NAME']);

        if ($mainModel && $relatedModel) {
            $mainModelId = $relation['COLUMN_NAME'];
            $relatedModelId = $relation['REFERENCED_COLUMN_NAME'];

            // Generate belongsTo relationship
            if (!method_exists($mainModel, Str::singular($relation['REFERENCED_TABLE_NAME']))) {
                echo '<pre>In ' . $mainModel . '.php add the following function:<br>'
                    . '/**<br>'
                    . ' * Relationship with model ' . $relatedModel . '<br>'
                    . ' */<br>'
                    . 'public function ' . Str::singular($relation['REFERENCED_TABLE_NAME']) . '() {<br>'
                    . '    return $this->belongsTo(\'' . $relatedModel . '\', \'' . $mainModelId . '\', \'' . $relatedModelId . '\');<br>'
                    . '}</pre>';
            } else {
                echo '<pre>' . $mainModel . ' already has ' . Str::singular($relation['REFERENCED_TABLE_NAME']) . '() method.</pre>';
            }

            // Generate hasMany relationship
            if (!method_exists($relatedModel, Str::singular($relation['TABLE_NAME']))) {
                echo '<pre>In ' . $relatedModel . '.php add the following function:<br>'
                    . '/**<br>'
                    . ' * Relationship with model ' . $mainModel . '<br>'
                    . ' */<br>'
                    . 'public function ' . Str::singular($relation['TABLE_NAME']) . '() {<br>'
                    . '    return $this->hasMany(\'' . $mainModel . '\', \'' . $mainModelId . '\');<br>'
                    . '}</pre>';
            } else {
                echo '<pre>' . $relatedModel . ' already has ' . Str::singular($relation['TABLE_NAME']) . '() method.</pre>';
            }
        } else {
            echo '<pre>Nothing to show for ' . $relation['TABLE_NAME'] . ' and ' . $relation['REFERENCED_TABLE_NAME'] . '.</pre>';
        }
    }
});
