<?php

namespace App\Helper\KeywordParser;

use InvalidArgumentException;

class KeywordRuleParser
{
    private string $input;
    private int $pos = 0;
    private array $fields = ["title", "ocr", "asr", "poi_name", "poi_city_name"];

    public function parse(string $expression): array
    {
        $this->input = trim($expression);
        $this->pos = 0;

        // 先验证表达式
        $this->validate($this->input);

        $result = $this->parseExpression();

        return ["rule" => ["and", $result]];
    }

    /**
     * 验证表达式语法
     * @throws InvalidArgumentException
     */
    public function validate(string $expression): void
    {
        $expr = trim($expression);

        if (empty($expr)) {
            throw new InvalidArgumentException("表达式不能为空");
        }

        // 1. 检查括号匹配
        $this->validateBrackets($expr);

        // 2. 检查运算符位置
        $this->validateOperators($expr);

        // 3. 检查空操作数
        $this->validateOperands($expr);
    }

    /**
     * 验证括号匹配
     */
    private function validateBrackets(string $expr): void
    {
        $depth = 0;
        $positions = [];

        for ($i = 0; $i < strlen($expr); $i++) {
            if ($this->isEscapedAtStr($expr, $i)) {
                continue;
            }

            if ($expr[$i] === '(') {
                $depth++;
                $positions[] = $i;

                // 检查空括号
                $closePos = $this->findMatchingBracket($expr, $i);
                if ($closePos !== -1) {
                    $inner = substr($expr, $i + 1, $closePos - $i - 1);
                    if (empty(trim($inner))) {
                        throw new InvalidArgumentException("存在空括号");
                    }
                }
            } elseif ($expr[$i] === ')') {
                $depth--;
                if ($depth < 0) {
                    throw new InvalidArgumentException("位置 {$i} 处存在多余的右括号 ')'");
                }
                array_pop($positions);
            }
        }

        if ($depth > 0) {
            $pos = end($positions);
            throw new InvalidArgumentException("位置 {$pos} 处的左括号 '(' 没有匹配的右括号");
        }
    }

    /**
     * 验证运算符位置
     */
    private function validateOperators(string $expr): void
    {
        $len = strlen($expr);
        $operators = ['+', '/'];

        // 找到第一个非空白、非转义的字符
        $firstNonSpace = -1;
        for ($i = 0; $i < $len; $i++) {
            if (!ctype_space($expr[$i])) {
                $firstNonSpace = $i;
                break;
            }
        }

        // 找到最后一个非空白字符
        $lastNonSpace = -1;
        for ($i = $len - 1; $i >= 0; $i--) {
            if (!ctype_space($expr[$i])) {
                $lastNonSpace = $i;
                break;
            }
        }

        if ($firstNonSpace === -1) {
            return;
        }

        // 检查是否以运算符开头（排除转义）
        if (in_array($expr[$firstNonSpace], $operators) && !$this->isEscapedAtStr($expr, $firstNonSpace)) {
            throw new InvalidArgumentException("表达式不能以运算符 '{$expr[$firstNonSpace]}' 开头");
        }

        // 检查是否以运算符结尾（排除转义）
        if (in_array($expr[$lastNonSpace], $operators) && !$this->isEscapedAtStr($expr, $lastNonSpace)) {
            throw new InvalidArgumentException("表达式不能以运算符 '{$expr[$lastNonSpace]}' 结尾");
        }

        // 检查连续运算符
        $prevOp = null;
        $prevOpPos = -1;
        for ($i = 0; $i < $len; $i++) {
            if ($this->isEscapedAtStr($expr, $i)) {
                continue;
            }

            $char = $expr[$i];
            if (in_array($char, $operators)) {
                if ($prevOp !== null) {
                    // 检查两个运算符之间是否只有空白
                    $between = trim(substr($expr, $prevOpPos + 1, $i - $prevOpPos - 1));
                    if (empty($between)) {
                        throw new InvalidArgumentException("位置 {$prevOpPos} 和 {$i} 处存在连续的运算符 '{$prevOp}' 和 '{$char}'");
                    }
                }
                $prevOp = $char;
                $prevOpPos = $i;
            } elseif (!ctype_space($char) && $char !== '(' && $char !== ')') {
                $prevOp = null;
                $prevOpPos = -1;
            }
        }
    }

    /**
     * 验证操作数（检查空操作数）
     */
    private function validateOperands(string $expr): void
    {
        $this->validateOperandsRecursive($expr, 0);
    }

    private function validateOperandsRecursive(string $expr, int $basePos): void
    {
        $expr = trim($expr);
        if (empty($expr)) {
            return;
        }

        // 如果整个表达式被括号包裹，递归检查内部
        if ($expr[0] === '(' && !$this->isEscapedAtStr($expr, 0)) {
            $closePos = $this->findMatchingBracket($expr, 0);
            if ($closePos === strlen($expr) - 1) {
                $inner = substr($expr, 1, $closePos - 1);
                $this->validateOperandsRecursive($inner, $basePos + 1);
                return;
            }
        }

        // 查找顶层运算符并验证两边
        $operators = ['+', '/'];
        $depth = 0;

        for ($i = 0; $i < strlen($expr); $i++) {
            if ($this->isEscapedAtStr($expr, $i)) {
                continue;
            }

            $char = $expr[$i];
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif (in_array($char, $operators) && $depth === 0) {
                $left = trim(substr($expr, 0, $i));
                $right = trim(substr($expr, $i + 1));

                if (empty($left)) {
                    throw new InvalidArgumentException("运算符 '{$char}' 左侧缺少操作数");
                }
                if (empty($right)) {
                    throw new InvalidArgumentException("运算符 '{$char}' 右侧缺少操作数");
                }

                // 检查左侧是否以运算符结尾
                $leftLast = $left[strlen($left) - 1];
                if (in_array($leftLast, $operators) && !$this->isEscapedAtStr($left, strlen($left) - 1)) {
                    throw new InvalidArgumentException("运算符 '{$leftLast}' 后面缺少操作数");
                }

                // 检查右侧是否以运算符开头
                $rightFirst = $right[0];
                if (in_array($rightFirst, $operators) && !$this->isEscapedAtStr($right, 0)) {
                    throw new InvalidArgumentException("运算符 '{$rightFirst}' 前面缺少操作数");
                }

                // 递归验证左右两边
                $this->validateOperandsRecursive($left, $basePos);
                $this->validateOperandsRecursive($right, $basePos + $i + 1);
                return;
            }
        }

        // 检查括号内的表达式
        $depth = 0;
        for ($i = 0; $i < strlen($expr); $i++) {
            if ($this->isEscapedAtStr($expr, $i)) {
                continue;
            }

            if ($expr[$i] === '(') {
                $closePos = $this->findMatchingBracket($expr, $i);
                if ($closePos !== -1) {
                    $inner = substr($expr, $i + 1, $closePos - $i - 1);
                    if (empty(trim($inner))) {
                        throw new InvalidArgumentException("位置 {$i} 处存在空括号");
                    }
                    $this->validateOperandsRecursive($inner, $basePos + $i + 1);
                    $i = $closePos;
                }
            }
        }
    }

    /**
     * 找到匹配的右括号位置
     */
    private function findMatchingBracket(string $str, int $openPos): int
    {
        $depth = 1;
        for ($i = $openPos + 1; $i < strlen($str); $i++) {
            if ($this->isEscapedAtStr($str, $i)) {
                continue;
            }
            if ($str[$i] === '(') {
                $depth++;
            } elseif ($str[$i] === ')') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }
        return -1;
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
        // 收集所有被 + 分隔的部分
        $parts = $this->splitByOperator('+');

        if (count($parts) === 1) {
            return $this->parseOrFromString($parts[0]);
        }

        // 解析每个部分（作为 or 表达式）
        $result = ["and"];
        foreach ($parts as $part) {
            $parser = new KeywordRuleParser();
            $parser->input = trim($part);
            $parser->pos = 0;
            $result[] = $parser->parseOrFromString(trim($part));
        }

        return $result;
    }

    private function parseOrFromString(string $str): array
    {
        $this->input = trim($str);
        $this->pos = 0;

        // 收集所有被 / 分隔的部分
        $parts = $this->splitByOperator('/');

        if (count($parts) === 1) {
            return $this->parsePrimaryFromString($parts[0]);
        }

        // 解析每个部分（作为 primary 表达式）
        $result = ["or"];
        foreach ($parts as $part) {
            $result[] = $this->parsePrimaryFromString(trim($part));
        }

        return $result;
    }

    private function parseOr(): array
    {
        return $this->parseOrFromString($this->input);
    }

    /**
     * 按运算符分割表达式（考虑括号层级和转义）
     */
    private function splitByOperator(string $op): array
    {
        $parts = [];
        $start = 0;
        $depth = 0;
        $len = strlen($this->input);

        for ($i = 0; $i < $len; $i++) {
            if ($this->isEscapedAt($i)) {
                continue;
            }

            $char = $this->input[$i];
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($char === $op && $depth === 0) {
                $parts[] = substr($this->input, $start, $i - $start);
                $start = $i + 1;
            }
        }

        // 添加最后一部分
        $parts[] = substr($this->input, $start);

        return $parts;
    }

    private function parsePrimaryFromString(string $str): array
    {
        $str = trim($str);

        // 检查是否是括号包裹的表达式
        if (strlen($str) > 0 && $str[0] === '(' && !$this->isEscapedAtStr($str, 0)) {
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
                // 整个表达式被括号包裹，递归解析内部
                $inner = substr($str, 1, $closePos - 1);
                $innerParser = new KeywordRuleParser();
                return $innerParser->parseSubExpression($inner);
            }
        }

        return $this->parseKeywordFromString($str);
    }

    private function parseKeywordFromString(string $str): array
    {
        $str = trim($str);

        // 移除转义符，保留被转义的字符
        $keyword = '';
        for ($i = 0; $i < strlen($str); $i++) {
            if ($str[$i] === '\\' && $i + 1 < strlen($str)) {
                $next = $str[$i + 1];
                if (in_array($next, ['+', '/', '(', ')', '\\'])) {
                    $keyword .= $next;
                    $i++;
                    continue;
                }
            }
            $keyword .= $str[$i];
        }

        return ["in", trim($keyword), ["fl" => $this->fields]];
    }

    public function parseSubExpression(string $expr): array
    {
        $this->input = trim($expr);
        $this->pos = 0;
        return $this->parseExpression();
    }

    private function parsePrimary(): array
    {
        return $this->parsePrimaryFromString($this->input);
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
        return $this->parseKeywordFromString($this->input);
    }
}
