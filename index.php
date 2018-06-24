<?php
require 'vendor/autoload.php';

use  App\Spider;

$music_list = file('list.txt');

$spider = new Spider();

// 填入自己的cookie，不设置就使用默认cookie
//$spider->setCookie();

if (count($music_list)) {
    foreach ($music_list as $song) {
        $response = '';
        echo trim($song) . "下载中:##############";
        $response = $spider->download($song);
        if ($response == 'success') {
            echo '下载成功' . PHP_EOL;
        } else {
            echo '下载失败' . PHP_EOL;
        }
    }
} else {
    echo '歌曲列表不能为空!' . PHP_EOL;
}