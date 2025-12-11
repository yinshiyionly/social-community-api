<?php

use App\Helper\KeywordParser\KeywordRuleParser;
use App\Helper\KeywordParser\Lexer;
use App\Helper\KeywordParser\Parser;
use App\Helper\KeywordParser\QueryBuilder;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {


    $parser = new KeywordRuleParser();

// 测试1: 正常表达式
    $expr1 ='(网红 / 创作者)';
    $expr1 = '() / 抖音';
    echo "测试1 - 正常表达式\n";
    echo "输入: $expr1\n";
    $keywordRule = $parser->parse($expr1);

    dd(
        $keywordRule["Rule"]
    );
    dd(
        $parser->parse($expr1)
    );
    dd(
        json_encode($parser->parse($expr1), JSON_UNESCAPED_UNICODE)
    );
    echo "输出: " . json_encode($parser->parse($expr1), JSON_UNESCAPED_UNICODE) . "\n\n";

// 测试2: 转义表达式 - 整个作为关键词
    $expr2 = "(网红 / 创作者) \\+ 抖音";
    echo "测试2 - 转义运算符\n";
    echo "输入: $expr2\n";
    echo "输出: " . json_encode($parser->parse($expr2), JSON_UNESCAPED_UNICODE) . "\n\n";

// 测试3: 互联网+
    $expr3 = "互联网\\+";
    echo "测试3 - 互联网+\n";
    echo "输入: $expr3\n";
    echo "输出: " . json_encode($parser->parse($expr3), JSON_UNESCAPED_UNICODE) . "\n\n";

// 测试4: 复杂嵌套
    $expr4 = "(抖音 / 今日头条) + 营销";
    echo "测试4 - 复杂嵌套\n";
    echo "输入: $expr4\n";
    echo "输出: " . json_encode($parser->parse($expr4), JSON_UNESCAPED_UNICODE) . "\n";










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
        dd(
            $json
        );

        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
});
