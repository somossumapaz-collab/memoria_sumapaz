<?php
$html = file_get_contents('productores_registrados.html');
preg_match_all('/<script\b[^>]*>([\s\S]*?)<\/script>/i', $html, $matches);

$js = "";
foreach ($matches[1] as $block) {
    if (strpos($block, 'downloadTemplateWithAnswers') !== false) {
        $js = $block;
        break;
    }
}

if (!$js) {
    echo "Could not find downloadTemplateWithAnswers block\n";
    exit(1);
}

echo "Found Javascript block of length: " . strlen($js) . " bytes\n";

// Simple stack-based bracket checker
$stack = [];
$len = strlen($js);
$lines = explode("\n", $js);

// Check if it parses as a basic php syntax block or we can count braces
$braces = 0;
$parens = 0;
$brackets = 0;

for ($i = 0; $i < $len; $i++) {
    $char = $js[$i];
    if ($char === '{') $braces++;
    else if ($char === '}') $braces--;
    else if ($char === '(') $parens++;
    else if ($char === ')') $parens--;
    else if ($char === '[') $brackets++;
    else if ($char === ']') $brackets--;
}

echo "Braces diff: $braces (expected 0)\n";
echo "Parens diff: $parens (expected 0)\n";
echo "Brackets diff: $brackets (expected 0)\n";
?>
