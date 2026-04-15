<?php

namespace Saola\Core\Database\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;

class DatabaseService
{
    /**
     * Get database connection
     */
    public function getConnection(string $connection = ''): Connection
    {
        return DB::connection($connection);
    }

    /**
     * Check if table exists
     */
    public function tableExists(string $table, string $connection = ''): bool
    {
        return Schema::connection($connection)->hasTable($table);
    }

    /**
     * Get table columns
     */
    public function getTableColumns(string $table, string $connection = ''): array
    {
        if (!$this->tableExists($table, $connection)) {
            return [];
        }

        return Schema::connection($connection)->getColumnListing($table);
    }

    /**
     * Get table structure
     */
    public function getTableStructure(string $table, string $connection = ''): array
    {
        if (!$this->tableExists($table, $connection)) {
            return [];
        }

        $columns = [];
        $schema = Schema::connection($connection)->getConnection();
        
        $tableColumns = $schema->select("SHOW COLUMNS FROM {$table}");
        
        foreach ($tableColumns as $column) {
            $columns[] = [
                'field' => $column->Field,
                'type' => $column->Type,
                'null' => $column->Null,
                'key' => $column->Key,
                'default' => $column->Default,
                'extra' => $column->Extra,
            ];
        }
        
        return $columns;
    }

    /**
     * Get table indexes
     */
    public function getTableIndexes(string $table, string $connection = ''): array
    {
        if (!$this->tableExists($table, $connection)) {
            return [];
        }

        $schema = Schema::connection($connection)->getConnection();
        $indexes = $schema->select("SHOW INDEX FROM {$table}");
        
        $result = [];
        foreach ($indexes as $index) {
            $result[] = [
                'table' => $index->Table,
                'non_unique' => $index->Non_unique,
                'key_name' => $index->Key_name,
                'seq_in_index' => $index->Seq_in_index,
                'column_name' => $index->Column_name,
                'collation' => $index->Collation,
                'cardinality' => $index->Cardinality,
                'sub_part' => $index->Sub_part,
                'packed' => $index->Packed,
                'null' => $index->Null,
                'index_type' => $index->Index_type,
                'comment' => $index->Comment,
            ];
        }
        
        return $result;
    }

    /**
     * Get database size
     */
    public function getDatabaseSize(string $connection = ''): array
    {
        $db = $this->getConnection($connection);
        $databaseName = $db->getDatabaseName();
        
        $result = $db->select("
            SELECT 
                table_schema AS 'Database',
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
            FROM information_schema.tables 
            WHERE table_schema = ?
            GROUP BY table_schema
        ", [$databaseName]);
        
        return $result[0] ?? ['Database' => $databaseName, 'Size (MB)' => 0];
    }

    /**
     * Get table sizes
     */
    public function getTableSizes(string $connection = ''): array
    {
        $db = $this->getConnection($connection);
        $databaseName = $db->getDatabaseName();
        
        return $db->select("
            SELECT 
                table_name AS 'Table',
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
                table_rows AS 'Rows'
            FROM information_schema.tables 
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
        ", [$databaseName]);
    }

    /**
     * Optimize table
     */
    public function optimizeTable(string $table, string $connection = ''): bool
    {
        try {
            $db = $this->getConnection($connection);
            $db->statement("OPTIMIZE TABLE {$table}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to optimize table {$table}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Repair table
     */
    public function repairTable(string $table, string $connection = ''): bool
    {
        try {
            $db = $this->getConnection($connection);
            $db->statement("REPAIR TABLE {$table}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to repair table {$table}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check table
     */
    public function checkTable(string $table, string $connection = ''): array
    {
        try {
            $db = $this->getConnection($connection);
            $result = $db->select("CHECK TABLE {$table}");
            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to check table {$table}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Analyze table
     */
    public function analyzeTable(string $table, string $connection = ''): bool
    {
        try {
            $db = $this->getConnection($connection);
            $db->statement("ANALYZE TABLE {$table}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to analyze table {$table}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get slow queries
     */
    public function getSlowQueries(string $connection = '', int $limit = 100): array
    {
        try {
            $db = $this->getConnection($connection);
            
            // Check if slow query log is enabled
            $slowLogEnabled = $db->select("SHOW VARIABLES LIKE 'slow_query_log'");
            
            if (empty($slowLogEnabled) || $slowLogEnabled[0]->Value !== 'ON') {
                return [];
            }
            
            $slowLogFile = $db->select("SHOW VARIABLES LIKE 'slow_query_log_file'");
            
            if (empty($slowLogFile)) {
                return [];
            }
            
            $logFile = $slowLogFile[0]->Value;
            
            if (!file_exists($logFile)) {
                return [];
            }
            
            // Parse slow query log (simplified)
            $content = file_get_contents($logFile);
            $lines = explode("\n", $content);
            
            $queries = [];
            $currentQuery = '';
            $currentTime = '';
            
            foreach ($lines as $line) {
                if (preg_match('/^# Time: (.+)$/', $line, $matches)) {
                    $currentTime = $matches[1];
                } elseif (preg_match('/^# Query_time: (.+) Lock_time: (.+) Rows_sent: (.+) Rows_examined: (.+)$/', $line, $matches)) {
                    if ($currentQuery && $currentTime) {
                        $queries[] = [
                            'time' => $currentTime,
                            'query_time' => $matches[1],
                            'lock_time' => $matches[2],
                            'rows_sent' => $matches[3],
                            'rows_examined' => $matches[4],
                            'query' => trim($currentQuery)
                        ];
                    }
                    $currentQuery = '';
                    $currentTime = '';
                } elseif (!empty(trim($line)) && !str_starts_with($line, '#')) {
                    $currentQuery .= $line . ' ';
                }
            }
            
            // Sort by query time and limit results
            usort($queries, function($a, $b) {
                return floatval($b['query_time']) <=> floatval($a['query_time']);
            });
            
            return array_slice($queries, 0, $limit);
            
        } catch (\Exception $e) {
            Log::error("Failed to get slow queries: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get database status
     */
    public function getDatabaseStatus(string $connection = ''): array
    {
        try {
            $db = $this->getConnection($connection);
            
            $variables = $db->select("SHOW VARIABLES");
            $status = $db->select("SHOW STATUS");
            
            $result = [];
            
            foreach ($variables as $var) {
                $result['variables'][$var->Variable_name] = $var->Value;
            }
            
            foreach ($status as $stat) {
                $result['status'][$stat->Variable_name] = $stat->Value;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error("Failed to get database status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Execute raw SQL
     */
    public function executeRaw(string $sql, array $bindings = [], string $connection = ''): mixed
    {
        try {
            $db = $this->getConnection($connection);
            return $db->statement($sql, $bindings);
        } catch (\Exception $e) {
            Log::error("Failed to execute raw SQL: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get query log
     */
    public function getQueryLog(string $connection = ''): array
    {
        $db = $this->getConnection($connection);
        return $db->getQueryLog();
    }

    /**
     * Enable query log
     */
    public function enableQueryLog(string $connection = ''): void
    {
        $db = $this->getConnection($connection);
        $db->enableQueryLog();
    }

    /**
     * Disable query log
     */
    public function disableQueryLog(string $connection = ''): void
    {
        $db = $this->getConnection($connection);
        $db->disableQueryLog();
    }

    /**
     * Clear query log
     */
    public function clearQueryLog(string $connection = ''): void
    {
        $db = $this->getConnection($connection);
        $db->flushQueryLog();
    }
}
