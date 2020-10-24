<?php
namespace HyperfAdmin\DevTools;

use Hyperf\Utils\Str;
use Nette\PhpGenerator\ClassType;
use HyperfAdmin\BaseUtils\Model\BaseModel;

class ModelMaker extends AbstractMaker
{
    /** @var mixed|\HyperfAdmin\DevTools\TableSchema */
    public $table_schema;

    public function __construct()
    {
        $this->table_schema = make(TableSchema::class);
    }

    public function make($pool, $database, $table, $path)
    {
        $schema = $this->table_schema->tableSchema($pool, $database, $table);
        if(!$schema) {
            return false;
        }
        $columns = array_values(array_filter(array_map(function ($item) {
            if(in_array($item['COLUMN_NAME'], [
                'created_at',
                'updated_at',
                'is_deleted',
            ])) {
                return null;
            }

            return $item['COLUMN_NAME'];
        }, $schema)));
        $class_namespace = $this->pathToNamespace($path) . '\\' . Str::studly($database);
        $class_name = Str::studly($table);
        $save_path = BASE_PATH . '/' . $path . '/' . Str::studly($database) . '/' . $class_name . '.php';
        /** @var ClassType $class */
        [
            $namespace,
            $class,
        ] = $this->getBaseClass($save_path, $class_namespace, $class_name, BaseModel::class);
        $params_doc = '';
        $casts = [];
        foreach($schema as $item) {
            [$name, $type, $comment] = $this->getProperty($item);
            $casts[$name] = $type;
            $params_doc .= sprintf('@property %s $%s %s', $type, $name, $comment) . PHP_EOL;
        }
        unset($casts['id'], $casts['updated_at'], $casts['created_at']);
        $class->setComment($params_doc);
        $class->addProperty('connection', $pool)->setProtected();
        $class->addProperty('table', $table)->setProtected();
        $class->addProperty('database', $database)->setProtected();
        $class->addProperty('fillable', $columns)->setProtected();
        $class->addProperty('casts', $casts)->setProtected();
        $class->removeConstant('CREATED_AT');
        $class->removeConstant('UPDATED_AT');
        $class->removeConstant('STATUS_YES');
        $class->removeConstant('STATUS_NOT');
        $code = $this->getNamespaceCode($namespace);
        if(file_put_contents($save_path, $code) === false) {
            return false;
        }

        return $class_namespace . '\\' . $class_name;
    }

    protected function getProperty($column): array
    {
        $name = $column['COLUMN_NAME'];
        $type = $this->formatPropertyType($column['DATA_TYPE'], $column['cast'] ?? null);

        return [$name, $type, $column['COLUMN_COMMENT']];
    }

    protected function formatDatabaseType(string $type): ?string
    {
        switch($type) {
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'bigint':
                return 'integer';
            case 'decimal':
            case 'float':
            case 'double':
            case 'real':
                return 'float';
            case 'bool':
            case 'boolean':
                return 'boolean';
            case 'varchar':
            case 'text':
            case 'char':
            case 'tinytext':
            case 'longtext':
            case 'enum':
                return 'string';
            default:
                return $type;
        }
    }

    protected function formatPropertyType(string $type, ?string $cast): ?string
    {
        if(!isset($cast)) {
            $cast = $this->formatDatabaseType($type) ?? 'string';
        }
        switch($cast) {
            case 'integer':
                return 'int';
            case 'date':
            case 'datetime':
                return '\Carbon\Carbon';
            case 'json':
                return 'array';
        }

        return $cast;
    }

    protected function formatColumns(array $columns): array
    {
        return array_map(function ($item) {
            return array_change_key_case($item, CASE_LOWER);
        }, $columns);
    }
}
