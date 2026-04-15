<?php

namespace Saola\Core\View\Compilers;

class SmartBladeCompiler
{
    public function compile(string $bladeContent): string
    {
        $content = $this->preprocess($bladeContent);
        $content = $this->processSequentially($content);
        $content = $this->convertPhpToJs($content);
        $content = $this->finalCleanup($content);
        return $content;
    }

    private function preprocess(string $content): string
    {
        $content = preg_replace('/\{\{--.*?--\}\}/s', '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/^\s*[\r\n]/m', '', $content);
        return trim($content);
    }

    private function processSequentially(string $content): string
    {
        $previousContent = '';
        $maxIterations = 50;
        $iteration = 0;

        while ($content !== $previousContent && $iteration < $maxIterations) {
            $previousContent = $content;
            $iteration++;

            $content = $this->processComplexDirectives($content);
            $content = $this->processSimpleDirectives($content);
            $content = $this->processRemainingDirectives($content);
            $content = $this->processNestedDirectives($content);
            $content = $this->processFinalDirectives($content);
            $content = $this->processUltimateDirectives($content);
            $content = $this->processLastDirectives($content);
            $content = $this->processFinalRemainingDirectives($content);
        }
        return $content;
    }

    private function processComplexDirectives(string $content): string
    {
        $content = $this->processForeachBlocks($content);
        $content = $this->processIfBlocks($content);
        $content = $this->processForBlocks($content);
        $content = $this->processWhileBlocks($content);
        $content = $this->processSwitchBlocks($content);
        $content = $this->processPhpBlocks($content);
        return $content;
    }

    private function processForeachBlocks(string $content): string
    {
        // Xử lý @foreach với key => value
        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $key = trim($matches[2]);
                $value = trim($matches[3]);
                $innerContent = $this->processInnerContent($matches[4]);
                return "\${SPA.foreach({$array}, ({$value}, {$key}) => `{$innerContent}`)}";
            },
            $content
        );

        // Xử lý @foreach với value
        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $value = trim($matches[2]);
                $innerContent = $this->processInnerContent($matches[3]);
                return "\${SPA.foreach({$array}, ({$value}) => `{$innerContent}`)}";
            },
            $content
        );

        return $content;
    }

    private function processIfBlocks(string $content): string
    {
        $content = preg_replace_callback(
            '/@if\s*\(\s*([^)]+)\s*\)(.*?)@endif/s',
            function($matches) {
                $condition = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@elseif\s*\(\s*([^)]+)\s*\)/', '`; } else if($1){ return `', $innerContent);
                $innerContent = preg_replace('/@else/', '`; } else { return `', $innerContent);
                
                return "\${SPA.execute(() => { if({$condition}){ return `{$innerContent}`; } return ''; })}";
            },
            $content
        );
        return $content;
    }

    private function processForBlocks(string $content): string
    {
        $content = preg_replace_callback(
            '/@for\s*\(\s*([^)]+)\s*\)(.*?)@endfor/s',
            function($matches) {
                $condition = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                return "\${SPA.execute(() => { let __outputString = ``; for({$condition}){ __outputString += `{$innerContent}`; } return __outputString; })}";
            },
            $content
        );
        return $content;
    }

    private function processWhileBlocks(string $content): string
    {
        $content = preg_replace_callback(
            '/@while\s*\(\s*([^)]+)\s*\)(.*?)@endwhile/s',
            function($matches) {
                $condition = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                return "\${SPA.execute(() => { let __outputString = ``; while({$condition}){ __outputString += `{$innerContent}`; } return __outputString; })}";
            },
            $content
        );
        return $content;
    }

    private function processSwitchBlocks(string $content): string
    {
        $content = preg_replace_callback(
            '/@switch\s*\(\s*([^)]+)\s*\)(.*?)@endswitch/s',
            function($matches) {
                $value = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@case\s*\(\s*([^)]+)\s*\)/', 'case $1: return `', $innerContent);
                $innerContent = preg_replace('/@break/', '`; break', $innerContent);
                $innerContent = preg_replace('/@default/', 'default: return `', $innerContent);
                
                return "\${SPA.execute(() => { switch({$value}){ {$innerContent} } return ''; })}";
            },
            $content
        );
        return $content;
    }

    private function processPhpBlocks(string $content): string
    {
        $content = preg_replace_callback(
            '/@php\s*(.*?)@endphp/s',
            function($matches) {
                $phpCode = trim($matches[1]);
                $jsCode = $this->convertPhpCodeToJs($phpCode);
                return "\${SPA.execute(() => { {$jsCode}; return ''; })}";
            },
            $content
        );
        return $content;
    }

    private function processSimpleDirectives(string $content): string
    {
        $content = preg_replace('/@case\s*\(\s*([^)]+)\s*\)/', 'case $1: return `', $content);
        $content = preg_replace('/@break/', '`; break', $content);
        $content = preg_replace('/@default/', 'default: return `', $content);
        $content = preg_replace('/@elseif\s*\(\s*([^)]+)\s*\)/', '`; } else if($1){ return `', $content);
        $content = preg_replace('/@else/', '`; } else { return `', $content);
        $content = preg_replace('/@vars\s*\(([^)]+)\)/', '', $content);
        return $content;
    }

    private function processNestedDirectives(string $content): string
    {
        $content = $this->processNestedForeach($content);
        $content = $this->processNestedIf($content);
        $content = $this->processNestedSwitch($content);
        return $content;
    }

    private function processNestedForeach(string $content): string
    {
        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $value = trim($matches[2]);
                $innerContent = $this->processInnerContent($matches[3]);
                return "\${SPA.foreach({$array}, ({$value}) => `{$innerContent}`)}";
            },
            $content
        );

        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $key = trim($matches[2]);
                $value = trim($matches[3]);
                $innerContent = $this->processInnerContent($matches[4]);
                return "\${SPA.foreach({$array}, ({$value}, {$key}) => `{$innerContent}`)}";
            },
            $content
        );

        return $content;
    }

    private function processNestedIf(string $content): string
    {
        $content = preg_replace_callback(
            '/@if\s*\(\s*([^)]+)\s*\)(.*?)@endif/s',
            function($matches) {
                $condition = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@elseif\s*\(\s*([^)]+)\s*\)/', '`; } else if($1){ return `', $innerContent);
                $innerContent = preg_replace('/@else/', '`; } else { return `', $innerContent);
                
                return "\${SPA.execute(() => { if({$condition}){ return `{$innerContent}`; } return ''; })}";
            },
            $content
        );
        return $content;
    }

    private function processNestedSwitch(string $content): string
    {
        $content = preg_replace_callback(
            '/@switch\s*\(\s*([^)]+)\s*\)(.*?)@endswitch/s',
            function($matches) {
                $value = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@case\s*\(\s*([^)]+)\s*\)/', 'case $1: return `', $innerContent);
                $innerContent = preg_replace('/@break/', '`; break', $innerContent);
                $innerContent = preg_replace('/@default/', 'default: return `', $innerContent);
                
                return "\${SPA.execute(() => { switch({$value}){ {$innerContent} } return ''; })}";
            },
            $content
        );
        return $content;
    }

    private function processFinalDirectives(string $content): string
    {
        $content = $this->processFinalForeach($content);
        $content = $this->processFinalSwitch($content);
        return $content;
    }

    private function processFinalForeach(string $content): string
    {
        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $value = trim($matches[2]);
                $innerContent = $this->processInnerContent($matches[3]);
                return "\${SPA.foreach({$array}, ({$value}) => `{$innerContent}`)}";
            },
            $content
        );

        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $key = trim($matches[2]);
                $value = trim($matches[3]);
                $innerContent = $this->processInnerContent($matches[4]);
                return "\${SPA.foreach({$array}, ({$value}, {$key}) => `{$innerContent}`)}";
            },
            $content
        );

        return $content;
    }

    private function processFinalSwitch(string $content): string
    {
        $content = preg_replace_callback(
            '/@switch\s*\(\s*([^)]+)\s*\)(.*?)@endswitch/s',
            function($matches) {
                $value = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@case\s*\(\s*([^)]+)\s*\)/', 'case $1: return `', $innerContent);
                $innerContent = preg_replace('/@break/', '`; break', $innerContent);
                $innerContent = preg_replace('/@default/', 'default: return `', $innerContent);
                
                return "\${SPA.execute(() => { switch({$value}){ {$innerContent} } return ''; })}";
            },
            $content
        );
        return $content;
    }

    private function processUltimateDirectives(string $content): string
    {
        $content = $this->processUltimateForeach($content);
        $content = $this->processUltimateSwitch($content);
        return $content;
    }

    private function processUltimateForeach(string $content): string
    {
        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $value = trim($matches[2]);
                $innerContent = $this->processInnerContent($matches[3]);
                return "\${SPA.foreach({$array}, ({$value}) => `{$innerContent}`)}";
            },
            $content
        );

        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $key = trim($matches[2]);
                $value = trim($matches[3]);
                $innerContent = $this->processInnerContent($matches[4]);
                return "\${SPA.foreach({$array}, ({$value}, {$key}) => `{$innerContent}`)}";
            },
            $content
        );

        return $content;
    }

    private function processUltimateSwitch(string $content): string
    {
        $content = preg_replace_callback(
            '/@switch\s*\(\s*([^)]+)\s*\)(.*?)@endswitch/s',
            function($matches) {
                $value = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@case\s*\(\s*([^)]+)\s*\)/', 'case $1: return `', $innerContent);
                $innerContent = preg_replace('/@break/', '`; break', $innerContent);
                $innerContent = preg_replace('/@default/', 'default: return `', $innerContent);
                
                return "\${SPA.execute(() => { switch({$value}){ {$innerContent} } return ''; })}";
            },
            $content
        );
        return $content;
    }

    private function processLastDirectives(string $content): string
    {
        $content = $this->processLastForeach($content);
        $content = $this->processLastSwitch($content);
        return $content;
    }

    private function processLastForeach(string $content): string
    {
        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $value = trim($matches[2]);
                $innerContent = $this->processInnerContent($matches[3]);
                return "\${SPA.foreach({$array}, ({$value}) => `{$innerContent}`)}";
            },
            $content
        );

        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $key = trim($matches[2]);
                $value = trim($matches[3]);
                $innerContent = $this->processInnerContent($matches[4]);
                return "\${SPA.foreach({$array}, ({$value}, {$key}) => `{$innerContent}`)}";
            },
            $content
        );

        return $content;
    }

    private function processLastSwitch(string $content): string
    {
        $content = preg_replace_callback(
            '/@switch\s*\(\s*([^)]+)\s*\)(.*?)@endswitch/s',
            function($matches) {
                $value = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@case\s*\(\s*([^)]+)\s*\)/', 'case $1: return `', $innerContent);
                $innerContent = preg_replace('/@break/', '`; break', $innerContent);
                $innerContent = preg_replace('/@default/', 'default: return `', $innerContent);
                
                return "\${SPA.execute(() => { switch({$value}){ {$innerContent} } return ''; })}";
            },
            $content
        );
        return $content;
    }

    private function processFinalRemainingDirectives(string $content): string
    {
        $content = $this->processFinalRemainingForeach($content);
        $content = $this->processFinalRemainingSwitch($content);
        return $content;
    }

    private function processFinalRemainingForeach(string $content): string
    {
        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $value = trim($matches[2]);
                $innerContent = $this->processInnerContent($matches[3]);
                return "\${SPA.foreach({$array}, ({$value}) => `{$innerContent}`)}";
            },
            $content
        );

        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $key = trim($matches[2]);
                $value = trim($matches[3]);
                $innerContent = $this->processInnerContent($matches[4]);
                return "\${SPA.foreach({$array}, ({$value}, {$key}) => `{$innerContent}`)}";
            },
            $content
        );

        return $content;
    }

    private function processFinalRemainingSwitch(string $content): string
    {
        $content = preg_replace_callback(
            '/@switch\s*\(\s*([^)]+)\s*\)(.*?)@endswitch/s',
            function($matches) {
                $value = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@case\s*\(\s*([^)]+)\s*\)/', 'case $1: return `', $innerContent);
                $innerContent = preg_replace('/@break/', '`; break', $innerContent);
                $innerContent = preg_replace('/@default/', 'default: return `', $innerContent);
                
                return "\${SPA.execute(() => { switch({$value}){ {$innerContent} } return ''; })}";
            },
            $content
        );
        return $content;
    }

    private function processRemainingDirectives(string $content): string
    {
        $content = preg_replace('/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)/', '${SPA.foreach($1, ($2) => `', $content);
        $content = preg_replace('/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)/', '${SPA.foreach($1, ($3, $2) => `', $content);
        $content = str_replace('@endforeach', '`)}', $content);

        $content = preg_replace('/@if\s*\(\s*([^)]+)\s*\)/', '${SPA.execute(() => { if($1){ return `', $content);
        $content = str_replace('@endif', '`; } return \'\'; })}', $content);

        $content = preg_replace('/@for\s*\(\s*([^)]+)\s*\)/', '${SPA.execute(() => { let __outputString = ``; for($1){ __outputString += `', $content);
        $content = str_replace('@endfor', '`; } return __outputString; })}', $content);

        $content = preg_replace('/@while\s*\(\s*([^)]+)\s*\)/', '${SPA.execute(() => { let __outputString = ``; while($1){ __outputString += `', $content);
        $content = str_replace('@endwhile', '`; } return __outputString; })}', $content);

        $content = preg_replace('/@switch\s*\(\s*([^)]+)\s*\)/', '${SPA.execute(() => { switch($1){', $content);
        $content = str_replace('@endswitch', '} return \'\'; })}', $content);

        $content = preg_replace('/@php\s*(.*?)@endphp/s', '${SPA.execute(() => { $1; return \'\'; })}', $content);

        $content = preg_replace('/@include\s*\(\s*([^)]+)\s*\)/', '${SPA.include($1)}', $content);
        $content = str_replace('@csrf', '${SPA.csrf()}', $content);

        $content = $this->convertRemainingPhpArrays($content);
        
        return $content;
    }

    private function convertRemainingPhpArrays(string $content): string
    {
        $content = preg_replace('/\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*([^,\]]+)\s*\]/', '{$1: $2}', $content);
        $content = preg_replace('/\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\]/', '{$1: $2}', $content);
        $content = preg_replace('/\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*([^,\]]+)\s*,\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*([^,\]]+)\s*\]/', '{$1: $2, $3: $4}', $content);
        return $content;
    }

    private function parseExpression(string $expression): string
    {
        $expression = trim($expression);
        $expression = $this->convertPhpArrayToJs($expression);
        $expression = $this->convertPhpObjectOperators($expression);
        $expression = $this->convertPhpVariables($expression);
        return $expression;
    }

    private function convertPhpArrayToJs(string $expression): string
    {
        $expression = preg_replace('/\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*([^,\]]+)\s*\]/', '{$1: $2}', $expression);
        $expression = preg_replace('/\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\]/', '{$1: $2}', $expression);
        $expression = preg_replace('/\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*([^,\]]+)\s*,\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*([^,\]]+)\s*\]/', '{$1: $2, $3: $4}', $expression);
        return $expression;
    }

    private function convertPhpObjectOperators(string $expression): string
    {
        $expression = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)/', '$1.$2', $expression);
        $expression = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]/', '$1.$2[$3]', $expression);
        $expression = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]->([a-zA-Z_][a-zA-Z0-9_]*)/', '$1.$2[$3].$4', $expression);
        $expression = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', '$1.$2(', $expression);
        return $expression;
    }

    private function convertPhpVariables(string $expression): string
    {
        $expression = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', '$1', $expression);
        return $expression;
    }

    private function processInnerContent(string $content): string
    {
        $content = $this->convertBladeVariables($content);
        $content = $this->convertPhpObjectOperators($content);
        $content = $this->convertPhpVariables($content);
        return trim($content);
    }

    private function convertBladeVariables(string $content): string
    {
        $content = preg_replace('/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', '${$1}', $content);
        $content = preg_replace('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', '${$1}', $content);
        $content = preg_replace('/\{\{\s*([^}]+)\s*\}\}/', '${$1}', $content);
        $content = preg_replace('/\{!!\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*!!\}/', '${$1}', $content);
        $content = preg_replace('/\{!!\s*([^}]+)\s*!!\}/', '${$1}', $content);
        return $content;
    }

    private function convertPhpCodeToJs(string $phpCode): string
    {
        $phpCode = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*([^;]+);/', 'let $1 = $2;', $phpCode);
        $phpCode = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)\+\+;/', '$1++;', $phpCode);
        $phpCode = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)--;/', '$1--;', $phpCode);
        $phpCode = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', '$1', $phpCode);
        return $phpCode;
    }

    private function convertPhpToJs(string $content): string
    {
        $content = preg_replace('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\)\s*\}\}/', '${SPA.$1($2)}', $content);
        $content = preg_replace('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*([^}]+)\s*\)\s*\}\}/', '${SPA.$1($2)}', $content);
        
        $content = preg_replace('/\$\{([^}]+)\s+\}/', '${$1}', $content);
        return $content;
    }

    private function finalCleanup(string $content): string
    {
        $content = preg_replace('/\{\{([^}]+)\}\}/', '${$1}', $content);
        $content = preg_replace('/([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)/', '$1.$2', $content);
        $content = preg_replace('/(?<!\{)(?<!\$)\$([a-zA-Z_][a-zA-Z0-9_]*)(?!\})/', '$1', $content);
        $content = str_replace(';;', ';', $content);
        $content = preg_replace('/\s*\)\s*\}\s*$/', '})}', $content);
        
        // Sửa lỗi switch case syntax - thêm dấu : sau case
        $content = preg_replace('/case\s+([^:]+)\s+return\s+`/', 'case $1: return `', $content);
        $content = preg_replace('/case\s+([^:]+)\s+break/', 'case $1: break', $content);
        $content = preg_replace('/case\s+([^:]+)\s+default/', 'case $1: default', $content);
        $content = preg_replace('/case\s+([^:]+)\s+}/', 'case $1: }', $content);
        $content = preg_replace('/case\s+([^:]+)\s+return/', 'case $1: return', $content);
        
        // Xử lý các directive còn lại sau khi đã xử lý các directive chính
        $content = $this->processRemainingDirectivesAfterCleanup($content);
        
        return $content;
    }

    private function processRemainingDirectivesAfterCleanup(string $content): string
    {
        // Xử lý @foreach còn lại với biểu thức phức tạp - sử dụng regex mạnh hơn
        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $value = trim($matches[2]);
                $innerContent = $this->processInnerContent($matches[3]);
                return "\${SPA.foreach({$array}, ({$value}) => `{$innerContent}`)}";
            },
            $content
        );

        // Xử lý @foreach với key => value còn lại - sử dụng regex mạnh hơn
        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $key = trim($matches[2]);
                $value = trim($matches[3]);
                $innerContent = $this->processInnerContent($matches[4]);
                return "\${SPA.foreach({$array}, ({$value}, {$key}) => `{$innerContent}`)}";
            },
            $content
        );

        // Xử lý @if còn lại
        $content = preg_replace_callback(
            '/@if\s*\(\s*([^)]+)\s*\)(.*?)@endif/s',
            function($matches) {
                $condition = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@elseif\s*\(\s*([^)]+)\s*\)/', '`; } else if($1){ return `', $innerContent);
                $innerContent = preg_replace('/@else/', '`; } else { return `', $innerContent);
                
                return "\${SPA.execute(() => { if({$condition}){ return `{$innerContent}`; } return ''; })}";
            },
            $content
        );

        // Xử lý @for còn lại
        $content = preg_replace_callback(
            '/@for\s*\(\s*([^)]+)\s*\)(.*?)@endfor/s',
            function($matches) {
                $condition = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                return "\${SPA.execute(() => { let __outputString = ``; for({$condition}){ __outputString += `{$innerContent}`; } return __outputString; })}";
            },
            $content
        );

        // Xử lý @while còn lại
        $content = preg_replace_callback(
            '/@while\s*\(\s*([^)]+)\s*\)(.*?)@endwhile/s',
            function($matches) {
                $condition = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                return "\${SPA.execute(() => { let __outputString = ``; while({$condition}){ __outputString += `{$innerContent}`; } return __outputString; })}";
            },
            $content
        );

        // Xử lý @switch còn lại - sửa lỗi switch case syntax
        $content = preg_replace_callback(
            '/@switch\s*\(\s*([^)]+)\s*\)(.*?)@endswitch/s',
            function($matches) {
                $value = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@case\s*\(\s*([^)]+)\s*\)/', 'case $1: return `', $innerContent);
                $innerContent = preg_replace('/@break/', '`; break', $innerContent);
                $innerContent = preg_replace('/@default/', 'default: return `', $innerContent);
                
                return "\${SPA.execute(() => { switch({$value}){ {$innerContent} } return ''; })}";
            },
            $content
        );

        // Xử lý @php còn lại
        $content = preg_replace_callback(
            '/@php\s*(.*?)@endphp/s',
            function($matches) {
                $phpCode = trim($matches[1]);
                $jsCode = $this->convertPhpCodeToJs($phpCode);
                return "\${SPA.execute(() => { {$jsCode}; return ''; })}";
            },
            $content
        );

        // Xử lý các directive còn lại sau khi đã xử lý các directive chính - lần cuối
        $content = $this->processFinalRemainingDirectivesAfterCleanup($content);

        return $content;
    }

    private function processFinalRemainingDirectivesAfterCleanup(string $content): string
    {
        // Xử lý @foreach còn lại với biểu thức phức tạp - lần cuối
        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $value = trim($matches[2]);
                $innerContent = $this->processInnerContent($matches[3]);
                return "\${SPA.foreach({$array}, ({$value}) => `{$innerContent}`)}";
            },
            $content
        );

        // Xử lý @foreach với key => value còn lại - lần cuối
        $content = preg_replace_callback(
            '/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s',
            function($matches) {
                $array = $this->parseExpression($matches[1]);
                $key = trim($matches[2]);
                $value = trim($matches[3]);
                $innerContent = $this->processInnerContent($matches[4]);
                return "\${SPA.foreach({$array}, ({$value}, {$key}) => `{$innerContent}`)}";
            },
            $content
        );

        // Xử lý @if còn lại - lần cuối
        $content = preg_replace_callback(
            '/@if\s*\(\s*([^)]+)\s*\)(.*?)@endif/s',
            function($matches) {
                $condition = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@elseif\s*\(\s*([^)]+)\s*\)/', '`; } else if($1){ return `', $innerContent);
                $innerContent = preg_replace('/@else/', '`; } else { return `', $innerContent);
                
                return "\${SPA.execute(() => { if({$condition}){ return `{$innerContent}`; } return ''; })}";
            },
            $content
        );

        // Xử lý @for còn lại - lần cuối
        $content = preg_replace_callback(
            '/@for\s*\(\s*([^)]+)\s*\)(.*?)@endfor/s',
            function($matches) {
                $condition = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                return "\${SPA.execute(() => { let __outputString = ``; for({$condition}){ __outputString += `{$innerContent}`; } return __outputString; })}";
            },
            $content
        );

        // Xử lý @while còn lại - lần cuối
        $content = preg_replace_callback(
            '/@while\s*\(\s*([^)]+)\s*\)(.*?)@endwhile/s',
            function($matches) {
                $condition = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                return "\${SPA.execute(() => { let __outputString = ``; while({$condition}){ __outputString += `{$innerContent}`; } return __outputString; })}";
            },
            $content
        );

        // Xử lý @switch còn lại - lần cuối, sửa lỗi switch case syntax
        $content = preg_replace_callback(
            '/@switch\s*\(\s*([^)]+)\s*\)(.*?)@endswitch/s',
            function($matches) {
                $value = $this->parseExpression($matches[1]);
                $innerContent = $this->processInnerContent($matches[2]);
                
                $innerContent = preg_replace('/@case\s*\(\s*([^)]+)\s*\)/', 'case $1: return `', $innerContent);
                $innerContent = preg_replace('/@break/', '`; break', $innerContent);
                $innerContent = preg_replace('/@default/', 'default: return `', $innerContent);
                
                return "\${SPA.execute(() => { switch({$value}){ {$innerContent} } return ''; })}";
            },
            $content
        );

        // Xử lý @php còn lại - lần cuối
        $content = preg_replace_callback(
            '/@php\s*(.*?)@endphp/s',
            function($matches) {
                $phpCode = trim($matches[1]);
                $jsCode = $this->convertPhpCodeToJs($phpCode);
                return "\${SPA.execute(() => { {$jsCode}; return ''; })}";
            },
            $content
        );

        return $content;
    }
}
