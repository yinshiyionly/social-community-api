<?php

namespace App\Helper\KeywordParser;

class TagsParser
{
    public function parse(array $tags): array
    {
        return [
            "list_intersect",
            ["f" => "tags"],
            ["l" => $tags]
        ];
    }
}
