<?php
declare(strict_types=1);
namespace HyperfAdmin\BaseUtils\Listener;

use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Contract\ListenerInterface;
use HyperfAdmin\BaseUtils\Log;

class DbQueryExecutedListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            QueryExecuted::class,
        ];
    }

    /**
     * @param object $event
     */
    public function process(object $event)
    {
        if($event instanceof QueryExecuted) {
            if(is_production()) {
                $sql = $event->sql;
            } else {
                $sql_with_placeholders = str_replace(['%', '?'], [
                    '%%',
                    '%s',
                ], $event->sql);
                $bindings = $event->connection->prepareBindings($event->bindings);
                $pdo = $event->connection->getPdo();
                $sql = vsprintf($sql_with_placeholders, array_map([
                    $pdo,
                    'quote',
                ], $bindings));
            }
            // 获取sql类型
            $sql_type = $this->getSqlType($sql);
            $name_map = [
                'select' => function ($sql_str) {
                    preg_match('/from\s+(\w+)\s?.*/', $sql_str, $match);

                    return $match[1] ?? '';
                },
                'update' => function ($sql_str) {
                    preg_match('/update\s+(\w+)\s.*/', $sql_str, $match);

                    return $match[1] ?? '';
                },
                'delete' => function ($sql_str) {
                    preg_match('/delete\s+(\w+)\s.*/', $sql_str, $match);

                    return $match[1] ?? '';
                },
                'insert' => function ($sql_str) {
                    preg_match('/insert into\s+(\w+)\s.*/', $sql_str, $match);

                    return $match[1] ?? '';
                },
            ];
            $table_name = isset($name_map[$sql_type]) ? $name_map[$sql_type](strtolower(str_replace('`', '', $sql))) : '';
            Log::get('sql')->info($event->connectionName, [
                'database' => $event->connection->getDatabaseName(),
                'type' => $sql_type,
                'table' => $table_name,
                'use_time' => $event->time,
                'sql' => $sql,
            ]);
        }
    }

    /**
     * 获取sql类型
     *
     * @param string $sql
     *
     * @return string
     */
    protected function getSqlType(string $sql): string
    {
        $type_map = [
            'select' => 'select',
            'update' => 'update',
            'delete' => 'delete',
            'insert' => 'insert into',
            // more...
        ];
        $sql_type = strtolower(explode(' ', trim($sql))[0] ?? '');
        if(isset($type_map[$sql_type])) {
            return (string)$sql_type;
        }
        // 下面原来逻辑不变
        foreach($type_map as $type => $ident) {
            if(stripos($sql, $ident) !== false) {
                $sql_type = $type;
                break;
            }
        }

        return (string)$sql_type;
    }
}
