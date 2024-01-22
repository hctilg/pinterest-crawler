<?php

require_once __DIR__ . '/pinterest.php';

// Usage
$configs = new PinterestConfig("Coffee", 200, "orig", '');
$scraper = new PinterestScraper($configs);

$scraper->downloadImages();   // download images directly
print_r($scraper->getUrls()); // just bring image links