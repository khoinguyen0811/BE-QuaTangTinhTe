<?php

$config = require dirname(__DIR__) . '/product-grid/config.php';
$config['title'] = 'Danh sách sản phẩm';
$config['icon'] = 'fa fa-shopping-bag';
$config['settings']['columns']['value'] = '2';

return $config;
