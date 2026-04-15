<?php

namespace Saola\Core\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

/**
 * DatabaseHelper - Helper để hỗ trợ đa database (MySQL, PostgreSQL)
 * 
 * Tự động chuyển đổi các hàm SQL-specific giữa MySQL và PostgreSQL
 */
trait DatabaseHelper
{
    /**
     * Lấy database driver name
     * 
     * @return string 'mysql' | 'pgsql' | 'sqlite' | 'sqlsrv'
     */
    protected function getDatabaseDriver(): string
    {
        if (!$this->_model) {
            return 'mysql'; // Default
        }
        
        $connection = $this->_model->getConnection();
        return $connection->getDriverName();
    }
    
    /**
     * Kiểm tra có phải PostgreSQL không
     * 
     * @return bool
     */
    protected function isPostgreSQL(): bool
    {
        return $this->getDatabaseDriver() === 'pgsql';
    }
    
    /**
     * Kiểm tra có phải MySQL không
     * 
     * @return bool
     */
    protected function isMySQL(): bool
    {
        return $this->getDatabaseDriver() === 'mysql';
    }
    
    /**
     * Chuyển đổi hàm random cho database
     * 
     * MySQL: RAND()
     * PostgreSQL: RANDOM()
     * 
     * @return string
     */
    protected function getRandomFunction(): string
    {
        return $this->isPostgreSQL() ? 'RANDOM()' : 'RAND()';
    }
    
    /**
     * Chuyển đổi hàm CONCAT cho database
     * 
     * MySQL: CONCAT(str1, str2, ...)
     * PostgreSQL: str1 || str2 || ...
     * 
     * @param array $strings
     * @return string
     */
    protected function getConcatFunction(...$strings): string
    {
        if ($this->isPostgreSQL()) {
            return implode(' || ', array_map(function($str) {
                return is_string($str) ? "'" . addslashes($str) . "'" : $str;
            }, $strings));
        }
        
        return 'CONCAT(' . implode(', ', $strings) . ')';
    }
    
    /**
     * Chuyển đổi hàm IFNULL cho database
     * 
     * MySQL: IFNULL(expr1, expr2)
     * PostgreSQL: COALESCE(expr1, expr2)
     * 
     * @param string $expr1
     * @param string $expr2
     * @return string
     */
    protected function getIfNullFunction(string $expr1, string $expr2): string
    {
        if ($this->isPostgreSQL()) {
            return "COALESCE({$expr1}, {$expr2})";
        }
        
        return "IFNULL({$expr1}, {$expr2})";
    }
    
    /**
     * Chuyển đổi hàm DATE_FORMAT cho database
     * 
     * MySQL: DATE_FORMAT(date, format)
     * PostgreSQL: TO_CHAR(date, format)
     * 
     * @param string $date
     * @param string $format
     * @return string
     */
    protected function getDateFormatFunction(string $date, string $format): string
    {
        if ($this->isPostgreSQL()) {
            // Chuyển đổi format từ MySQL sang PostgreSQL
            $formatMap = [
                '%Y' => 'YYYY',
                '%m' => 'MM',
                '%d' => 'DD',
                '%H' => 'HH24',
                '%i' => 'MI',
                '%s' => 'SS',
                '%W' => 'Day',
                '%M' => 'Month',
            ];
            
            $pgFormat = $format;
            foreach ($formatMap as $mysql => $pg) {
                $pgFormat = str_replace($mysql, $pg, $pgFormat);
            }
            
            return "TO_CHAR({$date}, '{$pgFormat}')";
        }
        
        return "DATE_FORMAT({$date}, '{$format}')";
    }
    
    /**
     * Chuyển đổi hàm LIKE cho database
     * 
     * MySQL: LIKE (case-sensitive)
     * PostgreSQL: ILIKE (case-insensitive) hoặc LIKE
     * 
     * @param Builder $query
     * @param string $column
     * @param string $value
     * @param bool $caseInsensitive
     * @return Builder
     */
    protected function applyLike(Builder $query, string $column, string $value, bool $caseInsensitive = false): Builder
    {
        if ($this->isPostgreSQL() && $caseInsensitive) {
            return $query->whereRaw("{$column} ILIKE ?", [$value]);
        }
        
        return $query->where($column, 'LIKE', $value);
    }
    
    /**
     * Chuyển đổi hàm LIMIT/OFFSET cho database
     * 
     * MySQL: LIMIT offset, count hoặc LIMIT count OFFSET offset
     * PostgreSQL: LIMIT count OFFSET offset
     * 
     * @param Builder $query
     * @param int $limit
     * @param int $offset
     * @return Builder
     */
    protected function applyLimit(Builder $query, int $limit, int $offset = 0): Builder
    {
        // Laravel's skip/take tự động xử lý cho cả 2 database
        return $query->skip($offset)->take($limit);
    }
    
    /**
     * Chuyển đổi hàm FULLTEXT search cho database
     * 
     * MySQL: MATCH(columns) AGAINST(search)
     * PostgreSQL: to_tsvector(columns) @@ to_tsquery(search)
     * 
     * @param string|array $columns Cột hoặc mảng cột
     * @param string $search Giá trị tìm kiếm
     * @return string SQL string với placeholder ?
     */
    protected function getFullTextSearch($columns, string $search): string
    {
        if ($this->isPostgreSQL()) {
            // PostgreSQL: to_tsvector
            if (is_array($columns)) {
                // Nếu nhiều cột, dùng || để nối
                $cols = implode(' || \' \' || ', $columns);
                return "to_tsvector('simple', {$cols}) @@ to_tsquery('simple', ?)";
            } else {
                return "to_tsvector('simple', {$columns}) @@ to_tsquery('simple', ?)";
            }
        }
        
        // MySQL: MATCH ... AGAINST
        $cols = is_array($columns) ? implode(',', $columns) : $columns;
        return "MATCH({$cols}) AGAINST(? IN BOOLEAN MODE)";
    }
    
    /**
     * Chuyển đổi hàm JSON functions cho database
     * 
     * MySQL: JSON_EXTRACT, JSON_UNQUOTE
     * PostgreSQL: ->, ->> operators
     * 
     * @param string $column
     * @param string $path
     * @param bool $unquote
     * @return string
     */
    protected function getJsonExtractFunction(string $column, string $path, bool $unquote = false): string
    {
        if ($this->isPostgreSQL()) {
            $operator = $unquote ? '->>' : '->';
            return "{$column}{$operator}'{$path}'";
        }
        
        $func = $unquote ? 'JSON_UNQUOTE(JSON_EXTRACT(' : 'JSON_EXTRACT(';
        return "{$func}{$column}, '{$path}')";
    }
    
    /**
     * Chuyển đổi hàm STRING_AGG (PostgreSQL) / GROUP_CONCAT (MySQL)
     * 
     * @param string $column
     * @param string $separator
     * @return string
     */
    protected function getStringAggFunction(string $column, string $separator = ','): string
    {
        if ($this->isPostgreSQL()) {
            return "STRING_AGG({$column}, '{$separator}')";
        }
        
        return "GROUP_CONCAT({$column} SEPARATOR '{$separator}')";
    }
    
    /**
     * Chuyển đổi hàm REGEXP cho database
     * 
     * MySQL: REGEXP
     * PostgreSQL: ~ (case-sensitive) hoặc ~* (case-insensitive)
     * 
     * @param string $pattern
     * @param bool $caseInsensitive
     * @return string
     */
    protected function getRegexpOperator(bool $caseInsensitive = false): string
    {
        if ($this->isPostgreSQL()) {
            return $caseInsensitive ? '~*' : '~';
        }
        
        return 'REGEXP';
    }
    
    /**
     * Chuyển đổi hàm DATE_ADD cho database
     * 
     * MySQL: DATE_ADD(date, INTERVAL value unit)
     * PostgreSQL: date + INTERVAL 'value unit'
     * 
     * @param string $date
     * @param int $value
     * @param string $unit
     * @return string
     */
    protected function getDateAddFunction(string $date, int $value, string $unit): string
    {
        if ($this->isPostgreSQL()) {
            return "{$date} + INTERVAL '{$value} {$unit}'";
        }
        
        return "DATE_ADD({$date}, INTERVAL {$value} {$unit})";
    }
    
    /**
     * Chuyển đổi hàm DATE_SUB cho database
     * 
     * MySQL: DATE_SUB(date, INTERVAL value unit)
     * PostgreSQL: date - INTERVAL 'value unit'
     * 
     * @param string $date
     * @param int $value
     * @param string $unit
     * @return string
     */
    protected function getDateSubFunction(string $date, int $value, string $unit): string
    {
        if ($this->isPostgreSQL()) {
            return "{$date} - INTERVAL '{$value} {$unit}'";
        }
        
        return "DATE_SUB({$date}, INTERVAL {$value} {$unit})";
    }
    
    /**
     * Chuyển đổi hàm DATEDIFF cho database
     * 
     * MySQL: DATEDIFF(date1, date2)
     * PostgreSQL: date1::date - date2::date
     * 
     * @param string $date1
     * @param string $date2
     * @return string
     */
    protected function getDateDiffFunction(string $date1, string $date2): string
    {
        if ($this->isPostgreSQL()) {
            return "({$date1}::date - {$date2}::date)";
        }
        
        return "DATEDIFF({$date1}, {$date2})";
    }
    
    /**
     * Chuyển đổi hàm TIMESTAMPDIFF cho database
     * 
     * MySQL: TIMESTAMPDIFF(unit, date1, date2)
     * PostgreSQL: EXTRACT(EPOCH FROM (date2 - date1)) / [unit_seconds]
     * 
     * @param string $unit
     * @param string $date1
     * @param string $date2
     * @return string
     */
    protected function getTimestampDiffFunction(string $unit, string $date1, string $date2): string
    {
        if ($this->isPostgreSQL()) {
            $unitMap = [
                'SECOND' => 1,
                'MINUTE' => 60,
                'HOUR' => 3600,
                'DAY' => 86400,
                'MONTH' => 2592000,
                'YEAR' => 31536000,
            ];
            
            $seconds = $unitMap[strtoupper($unit)] ?? 1;
            return "EXTRACT(EPOCH FROM ({$date2} - {$date1})) / {$seconds}";
        }
        
        return "TIMESTAMPDIFF({$unit}, {$date1}, {$date2})";
    }
    
    /**
     * Chuyển đổi hàm NOW() cho database
     * 
     * MySQL: NOW()
     * PostgreSQL: NOW() hoặc CURRENT_TIMESTAMP
     * 
     * @return string
     */
    protected function getNowFunction(): string
    {
        // Cả 2 đều hỗ trợ NOW()
        return 'NOW()';
    }
    
    /**
     * Chuyển đổi hàm CURDATE() cho database
     * 
     * MySQL: CURDATE()
     * PostgreSQL: CURRENT_DATE
     * 
     * @return string
     */
    protected function getCurrentDateFunction(): string
    {
        if ($this->isPostgreSQL()) {
            return 'CURRENT_DATE';
        }
        
        return 'CURDATE()';
    }
    
    /**
     * Chuyển đổi hàm CURTIME() cho database
     * 
     * MySQL: CURTIME()
     * PostgreSQL: CURRENT_TIME
     * 
     * @return string
     */
    protected function getCurrentTimeFunction(): string
    {
        if ($this->isPostgreSQL()) {
            return 'CURRENT_TIME';
        }
        
        return 'CURTIME()';
    }
    
    /**
     * Chuyển đổi hàm CAST cho database
     * 
     * MySQL: CAST(expr AS type)
     * PostgreSQL: expr::type hoặc CAST(expr AS type)
     * 
     * @param string $expr
     * @param string $type
     * @return string
     */
    protected function getCastFunction(string $expr, string $type): string
    {
        if ($this->isPostgreSQL()) {
            // PostgreSQL hỗ trợ cả 2 syntax
            return "{$expr}::{$type}";
        }
        
        return "CAST({$expr} AS {$type})";
    }
    
    /**
     * Chuyển đổi hàm LENGTH cho database
     * 
     * MySQL: LENGTH() (bytes) hoặc CHAR_LENGTH() (characters)
     * PostgreSQL: LENGTH() (characters) hoặc OCTET_LENGTH() (bytes)
     * 
     * @param string $expr
     * @param bool $bytes
     * @return string
     */
    protected function getLengthFunction(string $expr, bool $bytes = false): string
    {
        if ($this->isPostgreSQL()) {
            return $bytes ? "OCTET_LENGTH({$expr})" : "LENGTH({$expr})";
        }
        
        return $bytes ? "LENGTH({$expr})" : "CHAR_LENGTH({$expr})";
    }
}

