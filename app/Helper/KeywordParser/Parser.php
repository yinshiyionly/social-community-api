<?php

namespace App\Helper\KeywordParser;


class Parser
{
    private $tokens;
    private $position = 0;
    private $count;

    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
        $this->count = count($tokens);
    }

    public function parse()
    {
        return $this->expression();
    }

    private function expression()
    {
        $left = $this->term();
        while ($this->match(Lexer::T_OR)) {
            $operator = $this->previous();
            $right = $this->term();
            $left = ['type' => 'binary', 'operator' => 'OR', 'left' => $left, 'right' => $right];
        }
        return $left;
    }

    private function term()
    {
        $left = $this->factor();
        while ($this->match(Lexer::T_AND)) {
            $operator = $this->previous();
            $right = $this->factor();
            $left = ['type' => 'binary', 'operator' => 'AND', 'left' => $left, 'right' => $right];
        }
        return $left;
    }

    private function factor()
    {
        if ($this->match(Lexer::T_LPAREN)) {
            $expr = $this->expression();
            $this->consume(Lexer::T_RPAREN, "Expect ')' after expression.");
            return ['type' => 'group', 'expression' => $expr];
        }
        if ($this->match(Lexer::T_STRING)) {
            return ['type' => 'literal', 'value' => $this->previous()['value']];
        }
        throw new Exception("Expect expression.");
    }

    private function match($type)
    {
        if ($this->check($type)) {
            $this->advance();
            return true;
        }
        return false;
    }

    private function check($type)
    {
        if ($this->isAtEnd())
            return false;
        return $this->peek()['type'] === $type;
    }

    private function advance()
    {
        if (!$this->isAtEnd())
            $this->position++;
        return $this->previous();
    }

    private function isAtEnd()
    {
        return $this->peek()['type'] === Lexer::T_EOF;
    }

    private function peek()
    {
        if ($this->position >= $this->count)
            return ['type' => Lexer::T_EOF];
        return $this->tokens[$this->position];
    }

    private function previous()
    {
        return $this->tokens[$this->position - 1];
    }

    private function consume($type, $message)
    {
        if ($this->check($type))
            return $this->advance();
        throw new Exception($message);
    }
}
