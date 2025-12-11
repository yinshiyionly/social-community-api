<?php

namespace App\Helper\KeywordParser;


class KeywordRuleParser
{
    private string $input;
    private int $pos = 0;
    private array $fields = ["title", "ocr", "asr", "poi_name", "poi_city_name"];

    public function parse(string $expression): array
    {
        $this->input = trim($expression);
        $this->pos = 0;

        $result = $this->parseExpression();

        return ["Rule" => ["and", $result]];
    }

    private function parseExpression(): array
    {
        return $this->parseAnd();
    }

    /**
     * 查找未转义的运算符位置（考虑括号层级）
     */
    private function findOperator(string $op, int $start, int $end): int
    {
        $depth = 0;
        for ($i = $start; $i < $end; $i++) {
            $char = $this->input[$i];

            // 检查是否被转义
            if ($this->isEscapedAt($i)) {
                continue;
            }

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($char === $op && $depth === 0) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * 检查位置i的字符是否被转义
     */
    private function isEscapedAt(int $i): bool
    {
        if ($i === 0) return false;

        $count = 0;
        $j = $i - 1;
        while ($j >= 0 && $this->input[$j] === '\\') {
            $count++;
            $j--;
        }
        return $count % 2 === 1;
    }

    private function parseAnd(): array
    {
        $start = $this->pos;
        $end = strlen($this->input);

        // 查找未转义的 + 运算符
        $opPos = $this->findOperator('+', $start, $end);

        if ($opPos === -1) {
            return $this->parseOr();
        }

        // 解析左边
        $leftStr = substr($this->input, $start, $opPos - $start);
        $leftParser = new KeywordRuleParser();
        $left = $leftParser->parseSubExpression($leftStr);

        // 解析右边
        $rightStr = substr($this->input, $opPos + 1);
        $rightParser = new KeywordRuleParser();
        $right = $rightParser->parseSubExpression($rightStr);

        return ["and", $left, $right];
    }

    private function parseOr(): array
    {
        $start = $this->pos;
        $end = strlen($this->input);

        // 查找未转义的 / 运算符
        $opPos = $this->findOperator('/', $start, $end);

        if ($opPos === -1) {
            return $this->parsePrimary();
        }

        // 解析左边
        $leftStr = substr($this->input, $start, $opPos - $start);
        $leftParser = new KeywordRuleParser();
        $left = $leftParser->parseSubExpression($leftStr);

        // 解析右边
        $rightStr = substr($this->input, $opPos + 1);
        $rightParser = new KeywordRuleParser();
        $right = $rightParser->parseSubExpression($rightStr);

        return ["or", $left, $right];
    }

    public function parseSubExpression(string $expr): array
    {
        $this->input = trim($expr);
        $this->pos = 0;
        return $this->parseExpression();
    }

    private function parsePrimary(): array
    {
        $str = trim($this->input);

        // 检查是否是括号包裹的表达式（未转义的括号）
        if (strlen($str) > 0 && $str[0] === '(' && !$this->isEscapedAt(0)) {
            // 找到匹配的右括号
            $depth = 1;
            $closePos = -1;
            for ($i = 1; $i < strlen($str); $i++) {
                if ($this->isEscapedAtStr($str, $i)) {
                    continue;
                }
                if ($str[$i] === '(') {
                    $depth++;
                } elseif ($str[$i] === ')') {
                    $depth--;
                    if ($depth === 0) {
                        $closePos = $i;
                        break;
                    }
                }
            }

            if ($closePos === strlen($str) - 1) {
                // 整个表达式被括号包裹
                $inner = substr($str, 1, $closePos - 1);
                $innerParser = new KeywordRuleParser();
                return $innerParser->parseSubExpression($inner);
            }
        }

        return $this->parseKeyword();
    }

    private function isEscapedAtStr(string $str, int $i): bool
    {
        if ($i === 0) return false;

        $count = 0;
        $j = $i - 1;
        while ($j >= 0 && $str[$j] === '\\') {
            $count++;
            $j--;
        }
        return $count % 2 === 1;
    }

    private function parseKeyword(): array
    {
        $str = trim($this->input);

        // 移除转义符，保留被转义的字符
        $keyword = '';
        for ($i = 0; $i < strlen($str); $i++) {
            if ($str[$i] === '\\' && $i + 1 < strlen($str)) {
                $next = $str[$i + 1];
                if (in_array($next, ['+', '/', '(', ')', '\\'])) {
                    $keyword .= $next;
                    $i++; // 跳过下一个字符
                    continue;
                }
            }
            $keyword .= $str[$i];
        }

        return ["in", trim($keyword), ["fl" => $this->fields]];
    }
}
