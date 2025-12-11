<?php

namespace App\Helper\KeywordParser;

class QueryBuilder
{
    private $defaultFields = [
        "title",
        "ocr",
        "asr",
        "poi_name",
        "poi_city_name"
    ];

    public function build($ast)
    {
        return $this->visit($ast);
    }

    private function visit($node)
    {
        if ($node['type'] === 'binary') {
            return [
                'bool' => [
                    ($node['operator'] === 'OR' ? 'should' : 'must') => [
                        $this->visit($node['left']),
                        $this->visit($node['right'])
                    ]
                ]
            ];
        } elseif ($node['type'] === 'group') {
            return $this->visit($node['expression']);
        } elseif ($node['type'] === 'literal') {
            return [
                'multi_match' => [
                    'query' => $node['value'],
                    'fields' => $this->defaultFields
                ]
            ];
        }
        return [];
    }
}
