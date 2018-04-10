<?php
$data = [
    [
        'sku' => 'PS0000889497-24',
        'name' => 'LG 25" Full HD UltraWide 21:9 IPS Monitor',
        'description' => 'The UltraWide 21:9 aspect ratio makes games and movies more immersive than ever. The clarity of 1080p pixel Full HD resolution with IPS is a game-changer. Simply put, from any viewing angle, everything looks more crisp and detailed in Full HD.',
        'terms' => 'Please allow 3 – 5 weeks for processing and delivery. Valid while supplies last. Award is non-transferable and has no cash surrender value.',
        'active' => 1,
        'category' => '29',
        'brand' => 'LG',
        'price_ranged' => 0,
        'price_wholesale' => 20.99,
        'price_retail' => 20.99,
        'price_shipping' => 20,
        'price_handling' => 0,
        'vendor' => 'replink',
        'source' => 'api'
    ],
    [
        'sku' => 'PS0000889498-24',
        'name' => 'Apple MacBook 12" i5 1.3GHz 8/512GB - Silver',
        'description' => 'With a Retina display and a thin, lightweight design, the mid 2017 Apple 12\'\' MacBook provides both portability and performance. Measuring just 0.5\'\' (13.1mm) thin and weighing only 2 pounds, the MacBook is compact yet still has a premium feel, thanks to its unibody design.',
        'terms' => 'Please allow 3 – 5 weeks for processing and delivery. Valid while supplies last. Award is non-transferable and has no cash surrender value.',
        'active' => 1,
        'category' => '29',
        'brand' => 'Apple',
        'price_ranged' => 0,
        'price_wholesale' => 10,
        'price_retail' => 15,
        'price_shipping' => 5,
        'price_handling' => 0,
        'vendor' => 'replink',
        'source' => 'api'
    ],
    [
        'sku' => 'PS0000889491-24',
        'name' => 'Apple iPhone 7 256GB Unlocked - Gold',
        'description' => 'Advanced new camera systems. The best performance and battery life in an iPhone. Immersive stereo sound. The brightest, most colorful iPhone display. And splash and water resistant. iPhone 7 dramatically improves the most important aspects of the iPhone experience. This is iPhone 7.',
        'terms' => 'Please allow 3 – 5 weeks for processing and delivery. Valid while supplies last. Award is non-transferable and has no cash surrender value.',
        'active' => 1,
        'category' => '29',
        'brand' => 'Apple',
        'price_ranged' => 0,
        'price_wholesale' => 25,
        'price_retail' => 25,
        'price_shipping' => 20,
        'price_handling' => 0,
        'vendor' => 'replink',
        'source' => 'api'
    ],
    [
        'sku' => 'PS0000168274-24',
        'name' => 'SMEG SMEG 2-Slice Toaster- Red',
        'description' => 'In its shapes, the SMEG toaster combines ergonomics, functionality, and aesthetic balance. Breakfast, lunch, brunch, or just snacking - when you fall in love with the SMEG 50s Style toaster, you\'ll find every available excuse to use it.',
        'terms' => 'Please allow 3 – 5 weeks for processing and delivery. Valid while supplies last. Award is non-transferable and has no cash surrender value.',
        'active' => 1,
        'category' => '13',
        'brand' => 'SMEG',
        'price_ranged' => 0,
        'price_wholesale' => 5.95,
        'price_retail' => 5.95,
        'price_shipping' => 2.66,
        'price_handling' => 0,
        'vendor' => 'replink',
        'source' => 'api'
    ],
    [
        'sku' => 'PS0000883442-24',
        'name' => 'SmartSilk Ladies White Robe, 100% Cotton, Large/X-Large',
        'description' => 'Terry Velour Robe for Her features shawl collar, 3/4 sleeve, adjustable positioning for belt, hanger loop on inner collar, available in white, 1 chest pocket, 2 waist pockets, machine washable, 100% cotton. Packaging: flat pack poly bag.',
        'terms' => 'Please allow 3 – 5 weeks for processing and delivery. Valid while supplies last. Award is non-transferable and has no cash surrender value.',
        'active' => 1,
        'category' => '10',
        'brand' => 'SmartSilk',
        'price_ranged' => 0,
        'price_wholesale' => 30,
        'price_retail' => 35,
        'price_shipping' => 5,
        'price_handling' => 2,
        'vendor' => 'replink',
        'source' => 'api'
    ]
];

$container = [];

foreach($data as $product) {
    $container[] = new \AllDigitalRewards\Services\Catalog\Entity\Product($product);
}

return $container;