<?php

return [
    'disable' => env('CAPTCHA_DISABLE', false),
    'characters' => ['2', '3', '4', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'j', 'm', 'n', 'p', 'q', 'r', 't', 'u', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'M', 'N', 'P', 'Q', 'R', 'T', 'U', 'X', 'Y', 'Z'],
    'default' => [
        'length' => 9,
        'width' => 120,
        'height' => 36,
        'quality' => 90,
        'math' => false,
        'encrypt' => false,
    ],
    'math' => [
        'length' => 9,
        'width' => 120,
        'height' => 36,
        'quality' => 100,
        'math' => true,
        'contrast' => -5,
        'lines' => -100,          // 禁止干扰线
        'noise' => 0,          // 禁止噪点
        'sharpen' => 0,        // 不锐化
        'blur' => 0,           // 不模糊
        'angle' => 0,          // 不旋转
        'invert' => false,     // 不反色
        'expire' => 300
    ]
];
