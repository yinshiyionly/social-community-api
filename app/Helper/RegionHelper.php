<?php

declare(strict_types=1);

namespace App\Helper;

use Illuminate\Support\Facades\Cache;

/**
 * 省市区地区查询助手类
 *
 * 从 pca.json 文件中查询省、市、区名称
 */
class RegionHelper
{
    private const CACHE_KEY = 'region_pca_map';

    private const CACHE_TTL = 60 * 60 * 24 * 30; // 30天

    /**
     * 缓存的地区数据（扁平化后的 code => name 映射）
     */
    private static ?array $regionMap = null;

    /**
     * 根据地区编码获取名称
     *
     * @param string|int|null $code
     */
    public static function getName($code): string
    {
        if ($code === null || $code === '') {
            return '';
        }

        self::loadRegionData();

        return self::$regionMap[(string) $code] ?? '';
    }

    /**
     * 批量获取省市区名称
     *
     * @param string|int|null $provinceCode
     * @param string|int|null $cityCode
     * @param string|int|null $districtCode
     * @return array{province: string, city: string, district: string}
     */
    public static function getNames($provinceCode, $cityCode, $districtCode): array
    {
        return [
            'province' => self::getName($provinceCode),
            'city' => self::getName($cityCode),
            'district' => self::getName($districtCode),
        ];
    }

    /**
     * 加载并扁平化地区数据
     */
    private static function loadRegionData(): void
    {
        if (self::$regionMap !== null) {
            return;
        }

        // 优先从 Laravel 缓存读取
        self::$regionMap = Cache::get(self::CACHE_KEY);

        if (self::$regionMap !== null) {
            return;
        }

        $jsonPath = resource_path('json/pca.json');

        if (!file_exists($jsonPath)) {
            self::$regionMap = [];
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        if (!is_array($data)) {
            self::$regionMap = [];
            return;
        }

        self::$regionMap = [];
        self::flattenRegions($data);

        // 写入 Laravel 缓存
        Cache::put(self::CACHE_KEY, self::$regionMap, self::CACHE_TTL);
    }

    /**
     * 递归扁平化地区数据
     */
    private static function flattenRegions(array $regions): void
    {
        foreach ($regions as $region) {
            if (isset($region['code'], $region['name'])) {
                self::$regionMap[$region['code']] = $region['name'];
            }

            if (!empty($region['children'])) {
                self::flattenRegions($region['children']);
            }
        }
    }

    /**
     * 清除缓存（用于测试或需要重新加载时）
     */
    public static function clearCache(): void
    {
        self::$regionMap = null;
        Cache::forget(self::CACHE_KEY);
    }
}
