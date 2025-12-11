<?php

require_once 'Lexer.php';
require_once 'Parser.php';
require_once 'QueryBuilder.php';

$input = '(网红 / 创作者) + 抖音';
// $input = $argv[1] ?? '(网红 / 创作者) + 抖音';

echo "Input: " . $input . "\n";

try {
    $lexer = new Lexer($input);
    $tokens = $lexer->tokenize();
    // print_r($tokens);

    $parser = new Parser($tokens);
    $ast = $parser->parse();
    // print_r($ast);

    $qb = new QueryBuilder();
    $json = $qb->build($ast);

    echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
