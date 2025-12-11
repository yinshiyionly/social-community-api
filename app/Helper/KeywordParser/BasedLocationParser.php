<?php

namespace App\Helper\KeywordParser;


class BasedLocationParser
{
    public function parse(array $location): array
    {
        $locationData = [];

        if (isset($location['region'])) {
            $locationData['region'] = $location['region'];
        }
        if (isset($location['province'])) {
            $locationData['province'] = $location['province'];
        }
        if (isset($location['city'])) {
            $locationData['city'] = $location['city'];
        }
        if (isset($location['district'])) {
            $locationData['district'] = $location['district'];
        }

        return [
            "in_list",
            ["f" => "based_location"],
            ["l" => [$locationData]]
        ];
    }

    /**
     * 解析多个地点
     */
    public function parseMultiple(array $locations): array
    {
        $locationList = [];

        foreach ($locations as $location) {
            $locationData = [];
            if (isset($location['region'])) {
                $locationData['region'] = $location['region'];
            }
            if (isset($location['province'])) {
                $locationData['province'] = $location['province'];
            }
            if (isset($location['city'])) {
                $locationData['city'] = $location['city'];
            }
            if (isset($location['district'])) {
                $locationData['district'] = $location['district'];
            }
            $locationList[] = $locationData;
        }

        return [
            "in_list",
            ["f" => "based_location"],
            ["l" => $locationList]
        ];
    }
}
