<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class SubscribeDirectiveService
{
    public function registerDirectives(): void {
         // Directive @subscribe - state change subscription
         Blade::directive('subscribe', function ($expression) {
            return $this->processSubscribeDirective($expression);
        });
        Blade::directive('Subscribe', function ($expression) {
            return $this->processSubscribeDirective($expression);
        });
        Blade::directive('dontsubscribe', function ($expression) {
            return $this->processDontSubscribeDirective($expression);
        });
        Blade::directive('dontSubscribe', function ($expression) {
            return $this->processDontSubscribeDirective($expression);
        });
    }
    /**
     * Process @subscribe/@watch directive - state change subscription with new approach
     */
    public function processSubscribeDirective($expression)
    {
        $content = is_null($expression) ? '' : trim($expression);
        if ($content === '') {
            return '';
        }

        // @subscribe(@all / @ALL / @All)
        if (preg_match('/^@?all$/i', $content)) {
            return "<?php \$__helper->subscribeState(\$__VIEW_PATH__, \$__VIEW_ID__, true);?>";
        }

        // @subscribe(true|false)
        if (preg_match('/^(true|false)$/i', $content, $m)) {
            $bool = strtolower($m[1]) === 'true' ? 'true' : 'false';
            return "<?php \$__helper->subscribeState(\$__VIEW_PATH__, \$__VIEW_ID__, {$bool});?>";
        }

        // @subscribe([$a, $b, ...])
        if (strpos($content, '[') === 0 && substr($content, -1) === ']') {
            $inner = trim(substr($content, 1, -1));
            $stateKeys = $this->parseStateArraySimple($inner);
            $json = json_encode($stateKeys);
            return "<?php \$__helper->subscribeState(\$__VIEW_PATH__, \$__VIEW_ID__, {$json});?>";
        }

        // @subscribe($a, $b, ...)
        if (strpos($content, ',') !== false) {
            $stateKeys = $this->parseStateArraySimple($content);
            $json = json_encode($stateKeys);
            return "<?php \$__helper->subscribeState(\$__VIEW_PATH__, \$__VIEW_ID__, {$json});?>";
        }

        // @subscribe($a)
        if (preg_match('/^\$?(\w+)$/', $content, $m)) {
            $json = json_encode([$m[1]]);
            return "<?php \$__helper->subscribeState(\$__VIEW_PATH__, \$__VIEW_ID__, {$json});?>";
        }

        return '';
    }

    /**
     * Parse state array content and extract state keys
     */
    protected function parseStateArraySimple($arrayContent)
    {
        $stateKeys = [];
        $items = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';

        for ($i = 0; $i < strlen($arrayContent); $i++) {
            $char = $arrayContent[$i];

            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                $inQuotes = false;
                $quoteChar = '';
                $current .= $char;
            } elseif ($char === ',' && !$inQuotes) {
                if (trim($current)) {
                    $items[] = trim($current);
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (trim($current)) {
            $items[] = trim($current);
        }

        foreach ($items as $item) {
            $item = trim($item);
            if (strpos($item, '$') === 0) {
                $stateKey = substr($item, 1);
            } else {
                $stateKey = $item;
            }
            $stateKey = trim($stateKey, " \t\n\r\0\x0B\"'");
            if ($stateKey !== '') {
                $stateKeys[] = $stateKey;
            }
        }

        return $stateKeys;
    }
    
    /**
     * dontsubscribe handler
     */
    protected function processDontSubscribeDirective($expression)
    {
        return "<?php \$__helper->subscribeState(\$__VIEW_PATH__, \$__VIEW_ID__, false);?>";
    }
}
