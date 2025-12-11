<?php

namespace App\Helper\KeywordParser;


class Lexer
{
    const T_AND = 'AND';
    const T_OR = 'OR';
    const T_LPAREN = 'LPAREN';
    const T_RPAREN = 'RPAREN';
    const T_STRING = 'STRING';
    const T_EOF = 'EOF';

    private $input;
    private $position = 0;
    private $length;

    public function __construct($input)
    {
        $this->input = $input;
        $this->length = strlen($input);
    }

    public function tokenize()
    {
        $tokens = [];
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            if (ctype_space($char)) {
                $this->position++;
                continue;
            }

            if ($char === '+') {
                $tokens[] = ['type' => self::T_AND, 'value' => '+'];
                $this->position++;
            } elseif ($char === '/') {
                $tokens[] = ['type' => self::T_OR, 'value' => '/'];
                $this->position++;
            } elseif ($char === '(') {
                $tokens[] = ['type' => self::T_LPAREN, 'value' => '('];
                $this->position++;
            } elseif ($char === ')') {
                $tokens[] = ['type' => self::T_RPAREN, 'value' => ')'];
                $this->position++;
            } elseif ($char === '\\') {
                $tokens[] = ['type' => self::T_STRING, 'value' => $this->readString()];
            } else {
                $tokens[] = ['type' => self::T_STRING, 'value' => $this->readString()];
            }
        }
        $tokens[] = ['type' => self::T_EOF, 'value' => null];
        return $tokens;
    }

    private function readString()
    {
        $string = '';
        while ($this->position < $this->length) {
            $char = $this->input[$this->position];

            if ($char === '\\') {
                $this->position++;
                if ($this->position < $this->length) {
                    $string .= $this->input[$this->position];
                    $this->position++;
                } else {
                    $string .= '\\';
                }
            } elseif (in_array($char, ['+', '/', '(', ')'])) {
                break;
            } elseif (ctype_space($char)) {
                break;
            } else {
                $string .= $char;
                $this->position++;
            }
        }
        return $string;
    }
}
