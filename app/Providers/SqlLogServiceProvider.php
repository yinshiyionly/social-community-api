<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SqlLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // 只在启用SQL日志时记录
        if (!config('database.sql_log.enabled', false)) {
            return;
        }

        DB::listen(function ($query) {
            try {
                $this->logQuery($query);
            } catch (\Exception $e) {
                // 静默处理日志错误，避免影响主业务
                Log::error('SQL日志记录失败: ' . $e->getMessage());
            }
        });
    }

    /**
     * 记录SQL查询
     *
     * @param \Illuminate\Database\Events\QueryExecuted $query
     * @return void
     */
    protected function logQuery($query)
    {
        $sql = $query->sql;
        $bindings = $query->bindings;
        $time = $query->time;
        $connection = $query->connectionName;

        // 如果只记录慢查询，检查执行时间
        if (config('database.sql_log.log_slow_queries_only', false)) {
            $threshold = config('database.sql_log.slow_query_threshold', 1000);
            if ($time < $threshold) {
                return;
            }
        }

        // 获取数据库名称
        $database = config("database.connections.{$connection}.database", 'unknown');

        // 格式化绑定参数
        $formattedBindings = $this->formatBindings($bindings);

        // 构建日志数据
        $logData = [
            'con' => $connection,
            'db' => $database,
            'time' => $time,
            'sql' => $sql,
            'bindings' => $formattedBindings,
        ];

        // 使用 SQL 日志通道记录
        Log::channel('sql')->debug('SQL Query', $logData);
    }

    /**
     * 格式化绑定参数
     *
     * @param array $bindings
     * @return string
     */
    protected function formatBindings(array $bindings): string
    {
        if (empty($bindings)) {
            return '[]';
        }

        $formatted = array_map(function ($binding) {
            if (is_null($binding)) {
                return 'NULL';
            }
            if (is_bool($binding)) {
                return $binding ? 'true' : 'false';
            }
            if (is_string($binding)) {
                return "'" . addslashes($binding) . "'";
            }
            if ($binding instanceof \DateTime) {
                return "'" . $binding->format('Y-m-d H:i:s') . "'";
            }
            return $binding;
        }, $bindings);

        return '[' . implode(', ', $formatted) . ']';
    }


}
