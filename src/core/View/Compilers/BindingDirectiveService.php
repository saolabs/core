<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class BindingDirectiveService
{
    /**
     * Register the binding directives
     * @val and @bind are aliases of each other
     */
    public function registerDirectives(): void
    {
        // Register @val directive (primary)
        Blade::directive('val', function ($expression) {
            $bindingValue = $this->convertPhpToBinding($expression);
            return 'data-binding="' . $bindingValue . '" data-view-id="<?php echo $__VIEW_ID__; ?>"';
        });
        
        // Register @bind directive as alias of @val
        Blade::directive('bind', function ($expression) {
            // Use the same logic as @val (they are aliases)
            $bindingValue = $this->convertPhpToBinding($expression);
            return 'data-binding="' . $bindingValue . '" data-view-id="<?php echo $__VIEW_ID__; ?>"';
        });
    }
    
    /**
     * Process binding directives (@val and @bind are aliases)
     * @val($userState->name) -> data-binding="userState.name"
     * @bind($username) -> data-binding="username"
     * Both directives produce the same output
     * Supports nested parentheses
     */
    public function processBindingDirective($content, $directiveName = 'val|bind')
    {
        $result = $content;
        
        // Process until no more matches found (handles nested parentheses)
        while (true) {
            // Find @val or @bind directive
            if (!preg_match('/@(' . $directiveName . ')\s*\(/', $result, $matches, PREG_OFFSET_CAPTURE)) {
                break;
            }
            
            $startPos = $matches[0][1] + strlen($matches[0][0]) - 1; // Position of opening (
            $parenCount = 0;
            $i = $startPos;
            $found = false;
            
            // Find matching closing parenthesis
            while ($i < strlen($result)) {
                if ($result[$i] === '(') {
                    $parenCount++;
                } elseif ($result[$i] === ')') {
                    $parenCount--;
                    if ($parenCount === 0) {
                        // Found matching closing parenthesis
                        $expression = trim(substr($result, $startPos + 1, $i - $startPos - 1));
                        $bindingValue = $this->convertPhpToBinding($expression);
                        $replacement = 'data-binding="' . $bindingValue . '"';
                        $result = substr_replace($result, $replacement, $matches[0][1], $i - $matches[0][1] + 1);
                        $found = true;
                        break;
                    }
                }
                $i++;
            }
            
            if (!$found) {
                // No matching parenthesis found, break to avoid infinite loop
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * Process @val directive (alias of processBindingDirective)
     * @val($userState->name) -> data-binding="userState.name"
     * @val($user['name']) -> data-binding="user.name"
     */
    public function processValDirective($content)
    {
        return $this->processBindingDirective($content, 'val');
    }
    
    /**
     * Process @bind directive (alias of processBindingDirective)
     * @bind($username) -> data-binding="username"
     */
    public function processBindDirective($content)
    {
        return $this->processBindingDirective($content, 'bind');
    }
    
    /**
     * Convert PHP expression to JavaScript binding notation
     * $userState->name -> userState.name
     * $user['name'] -> user.name  
     * $username -> username
     */
    protected function convertPhpToBinding($phpExpression)
    {
        $phpExpression = trim($phpExpression);
        
        // Remove $ prefix from variables
        $result = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', '$1', $phpExpression);
        
        // Convert object accessor (->) to (.)
        $result = str_replace('->', '.', $result);
        
        // Convert array access ['key'] to .key
        $result = preg_replace("/\['([^']+)'\]/", '.$1', $result);
        $result = preg_replace('/\["([^"]+)"\]/', '.$1', $result);
        
        // Convert numeric array access [0] to .0
        $result = preg_replace('/\[(\d+)\]/', '.$1', $result);
        
        return $result;
    }
    
    /**
     * Process both @val and @bind directives (they are aliases)
     * This method processes both directives in a single pass
     */
    public function processAllBindingDirectives($content)
    {
        // Process both @val and @bind directives together (they are aliases)
        return $this->processBindingDirective($content, 'val|bind');
    }
}