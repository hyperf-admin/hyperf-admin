<?php
namespace HyperfAdmin\DevTools;

use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\Schema\MySqlBuilder;

class TableSchema
{
    public $resolver;

    public function __construct()
    {
        $this->resolver = container(ConnectionResolverInterface::class);
    }

    protected function getSchemaBuilder(string $poolName): MySqlBuilder
    {
        $connection = $this->resolver->connection($poolName);

        return $connection->getSchemaBuilder();
    }

    public function tableSchema($pool, $database, $table_name)
    {
        $builder = $this->getSchemaBuilder($pool);

        return $builder->getConnection()
            ->select('select `COLUMN_NAME`, `DATA_TYPE`, `COLUMN_COMMENT` from information_schema.COLUMNS where `TABLE_SCHEMA` = ? and `TABLE_NAME` = ? order by ORDINAL_POSITION', [
                $database,
                $table_name,
            ]);
    }

    public function pools()
    {
        return array_keys(config('databases'));
    }

    public function getDbs($pool)
    {
        $builder = $this->getSchemaBuilder($pool);
        $ret = $builder->getConnection()->select("show databases;");

        return array_column($ret ?? [], 'Database');
    }

    public function databasesTables($pool, $database)
    {
        $builder = $this->getSchemaBuilder($pool);
        $ret = $builder->getConnection()
            ->select("select TABLE_NAME from information_schema.tables where table_schema=? and table_type='BASE TABLE';", [
                $database,
            ]);

        return array_column($ret ?? [], 'TABLE_NAME');
    }

    public function tableDesign($pool, $database, $table_name)
    {
        $builder = $this->getSchemaBuilder($pool);

        return $builder->getConnection()
            ->select('SELECT ACTION_ORDER, EVENT_OBJECT_TABLE, TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, DEFINER, ACTION_STATEMENT, ACTION_TIMING FROM information_schema.triggers WHERE BINARY event_object_schema = ? AND BINARY event_object_table = ? ORDER BY event_object_table', [
                $database,
                $table_name,
            ]);
    }
}
