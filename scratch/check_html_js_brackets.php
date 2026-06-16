<?php
$htmlPath = __DIR__ . '/../productores_registrados.html';
if (!file_exists($htmlPath)) {
    die("File not found: $htmlPath\n");
}

$html = file_get_contents($htmlPath);
preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $matches);

echo "Found " . count($matches[1]) . " script blocks.\n";

foreach ($matches[1] as $idx => $script) {
    if (trim($script) === '') continue;
    echo "Checking script block " . ($idx + 1) . "... ";
    
    // Simple bracket/parenthesis/brace checking
    $stack = [];
    $lines = explode("\n", $script);
    $inString = false;
    $stringChar = '';
    $inComment = false; // single line
    $inMultiComment = false;
    
    for ($l = 0; $l < count($lines); $l++) {
        $line = $lines[$l];
        $inComment = false;
        
        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];
            
            // Check multi-line comment
            if ($inMultiComment) {
                if ($char === '*' && isset($line[$i+1]) && $line[$i+1] === '/') {
                    $inMultiComment = false;
                    $i++;
                }
                continue;
            }
            
            // Check single line comment start
            if (!$inString && !$inMultiComment) {
                if ($char === '/' && isset($line[$i+1]) && $line[$i+1] === '/') {
                    break; // ignore rest of line
                }
                if ($char === '/' && isset($line[$i+1]) && $line[$i+1] === '*') {
                    $inMultiComment = true;
                    $i++;
                    continue;
                }
            }
            
            // Handle strings
            if ($inString) {
                if ($char === '\\') {
                    $i++; // skip next char (escaped)
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
                continue;
            }
            
            if ($char === '"' || $char === "'" || $char === '`') {
                $inString = true;
                $stringChar = $char;
                continue;
            }
            
            // Track brackets
            if ($char === '{' || $char === '(' || $char === '[') {
                $stack[] = [
                    'char' => $char,
                    'line' => $l + 1,
                    'col' => $i + 1
                ];
            } elseif ($char === '}' || $char === ')' || $char === ']') {
                if (empty($stack)) {
                    echo "Error: Unmatched closing '$char' at line " . ($l + 1) . ", col " . ($i + 1) . "\n";
                    continue 2;
                }
                $top = array_pop($stack);
                $expected = '';
                if ($char === '}') $expected = '{';
                if ($char === ')') $expected = '(';
                if ($char === ']') $expected = '[';
                
                if ($top['char'] !== $expected) {
                    echo "Error: Mismatched closing '$char' at line " . ($l + 1) . ", col " . ($i + 1) . ". Expected '$expected' from line " . $top['line'] . ", col " . $top['col'] . "\n";
                    continue 2;
                }
            }
        }
    }
    
    if (!empty($stack)) {
        echo "Error: Unmatched opening brackets left in stack:\n";
        foreach ($stack as $item) {
            echo "  '" . $item['char'] . "' from line " . $item['line'] . ", col " . $item['col'] . "\n";
        }
    } else {
        echo "OK!\n";
    }
}
echo "Check completed.\n";
