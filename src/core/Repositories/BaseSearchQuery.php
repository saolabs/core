<?php

namespace Saola\Core\Repositories;

use Saola\Core\Languages\Locale;

/**
 * Trait BaseSearchQuery
 * 
 * Cung cấp chức năng xây dựng query tìm kiếm với nhiều tính năng:
 * - Hỗ trợ tìm kiếm theo nhiều biến thể từ khóa (original, clean, slug, clean_slug, ucwords)
 * - Hỗ trợ search mode: 'all' (tìm tất cả) hoặc 'raw' (tìm chính xác)
 * - Hỗ trợ search type: 'start' (bắt đầu), 'end' (kết thúc), 'match' (chính xác), 'word' (mặc định - chứa)
 * - Hỗ trợ search rules với placeholders: {query}, {clean}, {slug}, {clean_slug}, {lower}
 * - Hỗ trợ tìm kiếm một cột hoặc nhiều cột
 * - Hỗ trợ anchor @ ở đầu/cuối để chỉ định search type
 * - Hỗ trợ advanceSearch method nếu có
 * - Hỗ trợ multi-language content search
 */
trait BaseSearchQuery
{

    /**
     * build search query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $keywords
     * @return static
     */
    protected function buildMlcSearch($query, $keywords)
    {
        if($t = count($keywords)){
            
            $current = Locale::current();
            if (Locale::default() == $current || !($mlc = $this->_model->getMLCConfig())) {
                
                // return $this->where($this->getTable() . '.slug', $slug);
                return ;
            }            
            $query->whereIn($this->getTable() . '.' . $mlc['main_key'], function($subQuery) use($mlc, $current, $keywords){
                $subQuery->select($this->mlcTable . '.' . $mlc['ref_key'])
                    ->from($this->mlcTable)
                    ->whereColumn($this->getTable() . '.' . $mlc['main_key'],  '=', $this->mlcTable . '.' . $mlc['ref_key'])
                    ->where($this->mlcTable . '.locale', $current)
                    ->where(function($subQuery) use($keywords){

                        $i = 0;
                        foreach ($keywords as $keyword) {
                            if($i == 0){
                                $subQuery->where($this->mlcTable . '.title', 'like', "$keyword%");
                                $subQuery->orWhere($this->mlcTable . '.keywords', 'like', "$keyword%");
                            }else{
                                $subQuery->orWhere($this->mlcTable . '.title', 'like', "% $keyword%");
                                $subQuery->orWhere($this->mlcTable . '.keywords', 'like', "% $keyword%");
                                $subQuery->orWhere($this->mlcTable . '.title', 'like', "$keyword%");
                                $subQuery->orWhere($this->mlcTable . '.keywords', 'like', "$keyword%");
                            }
                            if ($i == 2) {
                                $subQuery->orWhere($this->mlcTable . '.slug', 'like', "$keyword%");
                            }
                            $i++;
                        }
                    });
            });
            
            
            
        }
    }

    /**
     * Xây dựng query tìm kiếm với nhiều tính năng
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder instance
     * @param string $keywords Từ khóa tìm kiếm (có thể có @ ở đầu/cuối để chỉ định search type)
     * @param string|array|null $searchBy Tên cột hoặc mảng cột để tìm kiếm
     * @param string|null $prefix Tiền tố cho tên cột (thường là tên bảng)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    final protected function buildSearchQuery($query, $keywords, $searchBy = null, $prefix = null)
    {
        // Chỉ xử lý nếu keywords là string không rỗng
        if (!is_string($keywords) || strlen($keywords) === 0) {
            return $query;
        }

        // Chỉ xử lý nếu có cột để tìm kiếm
        if (!$searchBy) {
            return $query;
        }

        // Xác định xem có nên sử dụng mode 'all' hay 'raw'
        $useAllMode = $this->shouldUseAllSearchMode($keywords);

        // Xây dựng query tìm kiếm
        $query->where(function ($query) use ($keywords, $searchBy, $prefix, $useAllMode) {
            if ($useAllMode) {
                $this->buildAllModeSearch($query, $keywords, $searchBy, $prefix);
            } else {
                $this->buildRawModeSearch($query, $keywords, $searchBy, $prefix);
            }
        });

        return $query;
    }

    /**
     * Kiểm tra xem có nên sử dụng search mode 'all' hay không
     * 
     * Mode 'all': Tìm kiếm với nhiều biến thể từ khóa (clean, slug, etc.)
     * Mode 'raw': Tìm kiếm chính xác với từ khóa gốc
     *
     * @param string $keywords Từ khóa tìm kiếm
     * @return bool True nếu nên dùng mode 'all', false nếu dùng mode 'raw'
     */
    private function shouldUseAllSearchMode(string $keywords): bool
    {
        // Nếu search mode không phải 'raw', luôn dùng mode 'all'
        if ($this->__searchMode__ != 'raw') {
            return true;
        }

        // Nếu là mode 'raw', chỉ dùng mode 'all' nếu có nhiều hơn 1 từ
        $words = array_filter(
            array_map('trim', explode(' ', $keywords)),
            fn($v) => strlen($v) > 0
        );

        return count($words) > 1;
    }

    /**
     * Xây dựng query tìm kiếm ở mode 'all' (tìm với nhiều biến thể từ khóa)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $keywords
     * @param string|array $searchBy
     * @param string|null $prefix
     * @return void
     */
    private function buildAllModeSearch($query, string $keywords, $searchBy, ?string $prefix): void
    {
        // Parse keywords để lấy search type và loại bỏ anchor @
        $parsed = $this->parseSearchKeywords($keywords);
        $keywords = $parsed['keywords'];
        $searchType = $parsed['searchType'];

        // Tạo các biến thể từ khóa để tìm kiếm
        $keywordVariants = $this->generateAllModeKeywordVariants($keywords);

        // Xử lý tìm kiếm theo cột
        if (is_string($searchBy)) {
            $this->buildSingleColumnAllModeSearch($query, $searchBy, $prefix, $keywordVariants, $searchType);
        } elseif (is_array($searchBy)) {
            $this->buildMultipleColumnsAllModeSearch($query, $searchBy, $prefix, $keywordVariants, $searchType);
        }
    }

    /**
     * Xây dựng query tìm kiếm ở mode 'raw' (tìm chính xác)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $keywords
     * @param string|array $searchBy
     * @param string|null $prefix
     * @return void
     */
    private function buildRawModeSearch($query, string $keywords, $searchBy, ?string $prefix): void
    {
        // Parse keywords để lấy search type và loại bỏ anchor @
        $parsed = $this->parseSearchKeywords($keywords);
        $keywords = $parsed['keywords'];
        $searchType = $parsed['searchType'];

        // Tạo các biến thể từ khóa cho mode raw (ít biến thể hơn)
        $keywordVariants = $this->generateRawModeKeywordVariants($keywords);

        // Xử lý tìm kiếm theo cột
        if (is_string($searchBy)) {
            $this->buildSingleColumnRawModeSearch($query, $searchBy, $prefix, $keywordVariants, $searchType);
        } elseif (is_array($searchBy)) {
            $this->buildMultipleColumnsRawModeSearch($query, $searchBy, $prefix, $keywordVariants, $searchType);
        }
    }

    /**
     * Parse keywords để xác định search type và loại bỏ anchor @
     *
     * @param string $keywords Từ khóa có thể chứa @ ở đầu/cuối
     * @return array ['keywords' => string, 'searchType' => string]
     */
    private function parseSearchKeywords(string $keywords): array
    {
        $hasStartAnchor = substr($keywords, 0, 1) === '@';
        $hasEndAnchor = substr($keywords, -1) === '@';

        // Loại bỏ anchor @
        if ($hasStartAnchor) {
            $keywords = substr($keywords, 1);
        }
        if ($hasEndAnchor) {
            $keywords = substr($keywords, 0, -1);
        }

        // Xác định search type dựa trên anchor
        $searchType = $this->__searchType__;
        if ($hasStartAnchor && $hasEndAnchor) {
            $searchType = 'match';
        } elseif ($hasStartAnchor) {
            $searchType = 'start';
        } elseif ($hasEndAnchor) {
            $searchType = 'end';
        }

        return [
            'keywords' => $keywords,
            'searchType' => $searchType
        ];
    }

    /**
     * Tạo các biến thể từ khóa cho mode 'all'
     * Bao gồm: original, clean, slug, clean_slug, ucwords
     *
     * @param string $keywords
     * @return array Mảng các biến thể từ khóa
     */
    private function generateAllModeKeywordVariants(string $keywords): array
    {
        $keywordClean = vnclean($keywords);
        $slug = str_slug($keywordClean);
        $ucWord = vnucwords($keywords);

        $variants = [
            $keywords,              // Original
            $keywordClean,           // Clean
            $slug,                   // Slug
            str_replace('-', '', $slug) // Clean slug (không có dấu gạch ngang)
        ];

        // Thêm ucwords nếu khác với original
        if ($ucWord != $keywords) {
            $variants[] = $ucWord;
        }

        return $variants;
    }

    /**
     * Tạo các biến thể từ khóa cho mode 'raw'
     * Bao gồm: original, lowercase, slug, ucwords
     *
     * @param string $keywords
     * @return array Mảng các biến thể từ khóa
     */
    private function generateRawModeKeywordVariants(string $keywords): array
    {
        $keywordClean = vnclean($keywords);
        $slug = str_slug($keywordClean);
        $ucWord = vnucwords($keywords);

        $variants = [
            $keywords,        // Original
            vntolower($keywords), // Lowercase
            $slug             // Slug
        ];

        // Thêm ucwords nếu khác với original
        if ($ucWord != $keywords) {
            $variants[] = $ucWord;
        }

        return $variants;
    }

    /**
     * Xây dựng query tìm kiếm cho một cột ở mode 'all'
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column Tên cột
     * @param string|null $prefix Tiền tố
     * @param array $keywordVariants Các biến thể từ khóa
     * @param string $searchType Loại tìm kiếm (start, end, match, word)
     * @return void
     */
    private function buildSingleColumnAllModeSearch($query, string $column, ?string $prefix, array $keywordVariants, string $searchType): void
    {
        $fullColumnName = $this->getFullColumnName($column, $prefix);

        // Kiểm tra xem cột có bị disable không
        if ($this->isColumnSearchDisabled($fullColumnName)) {
            return;
        }

        // Lấy search rules cho cột này
        $rules = $this->getSearchRulesForColumn($column, $fullColumnName);

        if ($rules) {
            $this->applySearchRules($query, $fullColumnName, $rules, $keywordVariants, $searchType, 'all');
        } else {
            $this->applyDefaultSearch($query, $fullColumnName, $keywordVariants, $searchType);
        }

        // Thêm advance search nếu có
        $this->applyAdvanceSearch($query, $keywordVariants, [$column], true);

        // Thêm multi-language search nếu có
        $this->applyMultiLanguageSearch($query, $keywordVariants, true);
    }

    /**
     * Xây dựng query tìm kiếm cho nhiều cột ở mode 'all'
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $columns Mảng tên cột
     * @param string|null $prefix Tiền tố
     * @param array $keywordVariants Các biến thể từ khóa
     * @param string $searchType Loại tìm kiếm
     * @return void
     */
    private function buildMultipleColumnsAllModeSearch($query, array $columns, ?string $prefix, array $keywordVariants, string $searchType): void
    {
        $hasConditions = false;

        foreach ($columns as $column) {
            $fullColumnName = $this->getFullColumnName($column, $prefix);

            // Kiểm tra xem cột có bị disable không
            if ($this->isColumnSearchDisabled($fullColumnName)) {
                continue;
            }

            // Lấy search rules cho cột này
            $rules = $this->getSearchRulesForColumn($column, $fullColumnName);

            if ($rules) {
                $this->applySearchRules($query, $fullColumnName, $rules, $keywordVariants, $searchType, 'all', $hasConditions);
            } else {
                $this->applyDefaultSearch($query, $fullColumnName, $keywordVariants, $searchType, $hasConditions);
            }

            $hasConditions = true;
        }

        // Thêm advance search nếu có
        $this->applyAdvanceSearch($query, $keywordVariants, $columns, $hasConditions);

        // Thêm multi-language search nếu có
        $this->applyMultiLanguageSearch($query, $keywordVariants, $hasConditions);
    }

    /**
     * Xây dựng query tìm kiếm cho một cột ở mode 'raw'
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column Tên cột
     * @param string|null $prefix Tiền tố
     * @param array $keywordVariants Các biến thể từ khóa
     * @param string $searchType Loại tìm kiếm
     * @return void
     */
    private function buildSingleColumnRawModeSearch($query, string $column, ?string $prefix, array $keywordVariants, string $searchType): void
    {
        $fullColumnName = $this->getFullColumnName($column, $prefix);

        // Kiểm tra xem cột có bị disable không
        if ($this->isColumnSearchDisabled($fullColumnName)) {
            return;
        }

        // Lấy search rules cho cột này
        $rules = $this->getSearchRulesForColumn($column, $fullColumnName);

        if ($rules) {
            $this->applySearchRules($query, $fullColumnName, $rules, $keywordVariants, $searchType, 'raw');
        } else {
            $this->applyDefaultRawSearch($query, $fullColumnName, $keywordVariants, $searchType);
        }

        // Thêm advance search nếu có
        $this->applyAdvanceSearch($query, $keywordVariants, [$fullColumnName], true);

        // Thêm multi-language search nếu có
        $this->applyMultiLanguageSearch($query, $keywordVariants, true);
    }

    /**
     * Xây dựng query tìm kiếm cho nhiều cột ở mode 'raw'
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $columns Mảng tên cột
     * @param string|null $prefix Tiền tố
     * @param array $keywordVariants Các biến thể từ khóa
     * @param string $searchType Loại tìm kiếm
     * @return void
     */
    private function buildMultipleColumnsRawModeSearch($query, array $columns, ?string $prefix, array $keywordVariants, string $searchType): void
    {
        $hasConditions = false;

        foreach ($columns as $column) {
            $fullColumnName = $this->getFullColumnName($column, $prefix);

            // Kiểm tra xem cột có bị disable không
            if ($this->isColumnSearchDisabled($fullColumnName)) {
                continue;
            }

            // Lấy search rules cho cột này
            $rules = $this->getSearchRulesForColumn($column, $fullColumnName);

            if ($rules) {
                $this->applySearchRules($query, $fullColumnName, $rules, $keywordVariants, $searchType, 'raw', $hasConditions);
            } else {
                $this->applyDefaultRawSearch($query, $fullColumnName, $keywordVariants, $searchType, $hasConditions);
            }

            $hasConditions = true;
        }

        // Thêm advance search nếu có
        $this->applyAdvanceSearch($query, $keywordVariants, $columns, $hasConditions);

        // Thêm multi-language search nếu có
        $this->applyMultiLanguageSearch($query, $keywordVariants, $hasConditions);
    }

    /**
     * Lấy tên cột đầy đủ (có prefix nếu cần)
     *
     * @param string $column Tên cột
     * @param string|null $prefix Tiền tố
     * @return string Tên cột đầy đủ
     */
    private function getFullColumnName(string $column, ?string $prefix): string
    {
        // Nếu cột đã có dấu chấm (table.column), không thêm prefix
        if (strpos($column, '.') !== false) {
            return $column;
        }

        return $prefix ? $prefix . $column : $column;
    }

    /**
     * Kiểm tra xem cột có bị disable tìm kiếm không
     *
     * @param string $fullColumnName Tên cột đầy đủ
     * @return bool True nếu bị disable, false nếu không
     */
    private function isColumnSearchDisabled(string $fullColumnName): bool
    {
        if (!$this->searchDisable || !is_array($this->searchDisable)) {
            return false;
        }

        return array_key_exists($fullColumnName, $this->searchDisable) 
            || in_array($fullColumnName, $this->searchDisable);
    }

    /**
     * Lấy search rules cho một cột
     *
     * @param string $column Tên cột gốc
     * @param string $fullColumnName Tên cột đầy đủ
     * @return array|null Mảng rules hoặc null nếu không có
     */
    private function getSearchRulesForColumn(string $column, string $fullColumnName): ?array
    {
        $rules = $this->__searchRules__ ?? [];

        // Tìm rule theo tên cột gốc hoặc tên cột đầy đủ
        $rule = $rules[$column] ?? $rules[$fullColumnName] ?? null;

        if (!$rule) {
            return null;
        }

        // Đảm bảo trả về mảng
        return is_array($rule) ? $rule : [$rule];
    }

    /**
     * Áp dụng search rules với placeholders
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column Tên cột
     * @param array $rules Mảng các rules
     * @param array $keywordVariants Các biến thể từ khóa
     * @param string $searchType Loại tìm kiếm
     * @param string $mode 'all' hoặc 'raw'
     * @param bool $hasConditions Đã có điều kiện trước đó chưa
     * @return void
     */
    private function applySearchRules($query, string $column, array $rules, array $keywordVariants, string $searchType, string $mode, bool &$hasConditions = false): void
    {
        foreach ($rules as $rule) {
            // Thử thay thế các placeholders đặc biệt
            $replacedRule = $this->replaceRulePlaceholders($rule, $keywordVariants, $mode);

            if ($replacedRule !== null) {
                // Rule đã được thay thế thành công
                $this->addWhereCondition($query, $column, $replacedRule, $hasConditions);
                $hasConditions = true;
            } else {
                // Thay thế {query} và áp dụng search type
                foreach ($keywordVariants as $variant) {
                    $searchPattern = $this->replaceQueryPlaceholder($rule, $variant, $searchType);
                    $this->addWhereCondition($query, $column, $searchPattern, $hasConditions);
                    $hasConditions = true;
                }
            }
        }
    }

    /**
     * Thay thế các placeholders đặc biệt trong rule ({clean}, {slug}, {clean_slug}, {lower})
     *
     * @param string $rule Rule pattern
     * @param array $keywordVariants Các biến thể từ khóa
     * @param string $mode 'all' hoặc 'raw'
     * @return string|null Rule đã thay thế hoặc null nếu không có placeholder đặc biệt
     */
    private function replaceRulePlaceholders(string $rule, array $keywordVariants, string $mode): ?string
    {
        // Mode 'all': hỗ trợ {clean}, {slug}, {clean_slug}
        if ($mode === 'all') {
            if (strpos($rule, '{clean}') !== false && isset($keywordVariants[1])) {
                return str_replace('{clean}', $keywordVariants[1], $rule);
            }
            if (strpos($rule, '{slug}') !== false && isset($keywordVariants[2])) {
                return str_replace('{slug}', $keywordVariants[2], $rule);
            }
            if (strpos($rule, '{clean_slug}') !== false && isset($keywordVariants[3])) {
                return str_replace('{clean_slug}', $keywordVariants[3], $rule);
            }
        }

        // Mode 'raw': hỗ trợ {lower}, {slug}
        if ($mode === 'raw') {
            if (strpos($rule, '{lower}') !== false && isset($keywordVariants[1])) {
                return str_replace('{lower}', $keywordVariants[1], $rule);
            }
            if (strpos($rule, '{slug}') !== false && isset($keywordVariants[2])) {
                return str_replace('{slug}', $keywordVariants[2], $rule);
            }
        }

        return null;
    }

    /**
     * Thay thế {query} placeholder và áp dụng search type
     *
     * @param string $rule Rule pattern
     * @param string $keyword Từ khóa
     * @param string $searchType Loại tìm kiếm
     * @return string Pattern đã được xử lý
     */
    private function replaceQueryPlaceholder(string $rule, string $keyword, string $searchType): string
    {
        // Nếu rule có {query}, thay thế nó
        if (strpos($rule, '{query}') !== false) {
            return str_replace('{query}', $keyword, $rule);
        }

        // Nếu không có {query}, áp dụng search type pattern
        return $this->applySearchTypePattern($keyword, $searchType);
    }

    /**
     * Áp dụng pattern tìm kiếm dựa trên search type
     *
     * @param string $keyword Từ khóa
     * @param string $searchType Loại tìm kiếm (start, end, match, word)
     * @return string Pattern đã được format
     */
    private function applySearchTypePattern(string $keyword, string $searchType): string
    {
        switch ($searchType) {
            case 'start':
                return "$keyword%";
            case 'end':
                return "%$keyword";
            case 'match':
            case 'all':
                return $keyword;
            default: // 'word' hoặc mặc định
                return "%$keyword%";
        }
    }

    /**
     * Áp dụng tìm kiếm mặc định (không có rules)
     * 
     * Lưu ý: Ở mode 'all', chỉ dùng 4 phần tử đầu (original, clean, slug, clean_slug)
     * không dùng ucwords khi không có rule
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column Tên cột
     * @param array $keywordVariants Các biến thể từ khóa
     * @param string $searchType Loại tìm kiếm
     * @param bool $hasConditions Đã có điều kiện trước đó chưa
     * @return void
     */
    private function applyDefaultSearch($query, string $column, array $keywordVariants, string $searchType, bool &$hasConditions = false): void
    {
        // Ở mode 'all', chỉ dùng 4 phần tử đầu (0, 1, 2, 3)
        $variantsToUse = array_slice($keywordVariants, 0, 4);
        
        foreach ($variantsToUse as $variant) {
            $pattern = $this->applySearchTypePattern($variant, $searchType);
            $this->addWhereCondition($query, $column, $pattern, $hasConditions);
            $hasConditions = true;
        }
    }

    /**
     * Áp dụng tìm kiếm mặc định cho mode 'raw'
     * 
     * Ở mode 'raw', dùng tất cả các biến thể từ khóa
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column Tên cột
     * @param array $keywordVariants Các biến thể từ khóa
     * @param string $searchType Loại tìm kiếm
     * @param bool $hasConditions Đã có điều kiện trước đó chưa
     * @return void
     */
    private function applyDefaultRawSearch($query, string $column, array $keywordVariants, string $searchType, bool &$hasConditions = false): void
    {
        // Ở mode 'raw', dùng tất cả các biến thể
        foreach ($keywordVariants as $variant) {
            $pattern = $this->applySearchTypePattern($variant, $searchType);
            $this->addWhereCondition($query, $column, $pattern, $hasConditions);
            $hasConditions = true;
        }
    }

    /**
     * Thêm điều kiện WHERE vào query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column Tên cột
     * @param string $pattern Pattern để tìm kiếm
     * @param bool $hasConditions Đã có điều kiện trước đó chưa
     * @return void
     */
    private function addWhereCondition($query, string $column, string $pattern, bool &$hasConditions): void
    {
        if ($hasConditions) {
            $query->orWhere($column, 'like', $pattern);
        } else {
            $query->where($column, 'like', $pattern);
            $hasConditions = true;
        }
    }

    /**
     * Áp dụng advance search nếu method advanceSearch tồn tại
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $keywordVariants Các biến thể từ khóa
     * @param array $columns Mảng tên cột
     * @param bool $hasConditions Đã có điều kiện trước đó chưa
     * @return void
     */
    private function applyAdvanceSearch($query, array $keywordVariants, array $columns, bool $hasConditions): void
    {
        if (!method_exists($this, 'advanceSearch')) {
            return;
        }

        $callback = function ($query) use ($keywordVariants, $columns) {
            $this->advanceSearch($query, $keywordVariants, $columns);
        };

        if ($hasConditions) {
            $query->orWhere($callback);
        } else {
            $query->where($callback);
        }
    }

    /**
     * Áp dụng multi-language search nếu được bật
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $keywordVariants Các biến thể từ khóa
     * @param bool $hasConditions Đã có điều kiện trước đó chưa
     * @return void
     */
    private function applyMultiLanguageSearch($query, array $keywordVariants, bool $hasConditions): void
    {
        if (!$this->mlcSearchActive || !Locale::isDefault()) {
            return;
        }

        if (!method_exists($this, 'buildMlcSearch')) {
            return;
        }

        $callback = function ($query) use ($keywordVariants) {
            $this->buildMlcSearch($query, $keywordVariants);
        };

        if ($hasConditions || method_exists($this, 'advanceSearch')) {
            $query->orWhere($callback);
        } else {
            $query->where($callback);
        }
    }
}
