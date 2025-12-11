<?php

namespace App\Helper\Volcengine;

class InsightIndustryTag
{
    const OWLS_FINANCE = '财经金融';
    const OWLS_ANIMAL = '动物';
    const OWLS_LEGAL = '法律法规';
    const OWLS_PUBLIC_WELFARE = '公益';
    const OWLS_EDU = '校园教育';
    const OWLS_MARRIAGE = '婚姻';
    const OWLS_PLOT = '剧情';
    const OWLS_TECH = '科技';
    const OWLS_TECH_PRODUCT = '科技产品';
    const OWLS_POPULAR_SCIENCE = '科普';
    const OWLS_TRAVEL = '旅游';
    const OWLS_FOOD = '食物';
    const OWLS_CELEBRITY = '明星名人';
    const OWLS_MOTHER_CHILD = '母婴';
    const OWLS_CAR = '汽车';
    const OWLS_PARENTING = '亲子';
    const OWLS_EMOTION = '情感心理';
    const OWLS_AGRICULTURE = '农业';
    const OWLS_SOCIETY_POLITICS = '时政';
    const OWLS_PHOTOGRAPHY = '摄影';
    const OWLS_DAILY_LIFE = '日常生活';
    const OWLS_FURNISHING = '家居装饰';
    const OWLS_FASHION = '时尚';
    const OWLS_FASHION_COSMETICS = '美妆';
    const OWLS_SNAPSHOT = '随拍';
    const OWLS_CULTURE = '文化';
    const OWLS_DANCING = '舞蹈';
    const OWLS_ENTERTAINING = '娱乐';
    const OWLS_MEDICATION = '医疗';
    const OWLS_ART = '艺术';
    const OWLS_MUSIC = '音乐';
    const OWLS_CAREER = '职业';
    const OWLS_CARTOON = '二次元';
    const OWLS_SPORTS = '体育';
    const OWLS_UNCATEGORIZED = '未分类';

    /**
     * 获取行业分类标签
     *
     * @return array[]
     */
    public static function getIndustryTagList(): array
    {
        return [
            [
                'label' => self::OWLS_FINANCE,
                'value' => 'owls_finance'
            ],
            [
                'label' => self::OWLS_ANIMAL,
                'value' => 'owls_animal'
            ],
            [
                'label' => self::OWLS_LEGAL,
                'value' => 'owls_legal'
            ],
            [
                'label' => self::OWLS_PUBLIC_WELFARE,
                'value' => 'owls_public_welfare'
            ],
            [
                'label' => self::OWLS_EDU,
                'value' => 'owls_edu'
            ],
            [
                'label' => self::OWLS_MARRIAGE,
                'value' => 'owls_marriage'
            ],
            [
                'label' => self::OWLS_PLOT,
                'value' => 'owls_plot'
            ],
            [
                'label' => self::OWLS_TECH,
                'value' => 'owls_tech'
            ],
            [
                'label' => self::OWLS_TECH_PRODUCT,
                'value' => 'owls_tech_product'
            ],
            [
                'label' => self::OWLS_POPULAR_SCIENCE,
                'value' => 'owls_popular_science'
            ],
            [
                'label' => self::OWLS_TRAVEL,
                'value' => 'owls_travel'
            ],
            [
                'label' => self::OWLS_FOOD,
                'value' => 'owls_food'
            ],
            [
                'label' => self::OWLS_CELEBRITY,
                'value' => 'owls_celebrity'
            ],
            [
                'label' => self::OWLS_MOTHER_CHILD,
                'value' => 'owls_mother&child'
            ],
            [
                'label' => self::OWLS_CAR,
                'value' => 'owls_car'
            ],
            [
                'label' => self::OWLS_PARENTING,
                'value' => 'owls_parenting'
            ],
            [
                'label' => self::OWLS_EMOTION,
                'value' => 'owls_emotion'
            ],
            [
                'label' => self::OWLS_AGRICULTURE,
                'value' => 'OWLS_AGRICULTURE'
            ],
            [
                'label' => self::OWLS_SOCIETY_POLITICS,
                'value' => 'owls_society&politics'
            ],
            [
                'label' => self::OWLS_PHOTOGRAPHY,
                'value' => 'owls_photography'
            ],
            [
                'label' => self::OWLS_DAILY_LIFE,
                'value' => 'owls_daily_life'
            ],
            [
                'label' => self::OWLS_FURNISHING,
                'value' => 'owls_furnishing'
            ],
            [
                'label' => self::OWLS_FASHION,
                'value' => 'owls_fashion'
            ],
            [
                'label' => self::OWLS_FASHION_COSMETICS,
                'value' => 'owls_fashion_cosmetics'
            ],
            [
                'label' => self::OWLS_SNAPSHOT,
                'value' => 'owls_snapshot'
            ],
            [
                'label' => self::OWLS_CULTURE,
                'value' => 'owls_culture'
            ],
            [
                'label' => self::OWLS_DANCING,
                'value' => 'owls_dancing'
            ],
            [
                'label' => self::OWLS_ENTERTAINING,
                'value' => 'owls_entertaining'
            ],
            [
                'label' => self::OWLS_MEDICATION,
                'value' => 'owls_medication'
            ],
            [
                'label' => self::OWLS_ART,
                'value' => 'owls_art'
            ],
            [
                'label' => self::OWLS_MUSIC,
                'value' => 'owls_music'
            ],
            [
                'label' => self::OWLS_CAREER,
                'value' => 'owls_career'
            ],
            [
                'label' => self::OWLS_CARTOON,
                'value' => 'owls_cartoon'
            ],
            [
                'label' => self::OWLS_SPORTS,
                'value' => 'owls_sports'
            ],
            [
                'label' => self::OWLS_UNCATEGORIZED,
                'value' => 'owls_uncategorized'
            ]
        ];
    }
}
