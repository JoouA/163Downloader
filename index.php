<?php
require 'vendor/autoload.php';
use  App\Spider;

$spider = new Spider();
// 填入自己的cookie，不设置就使用默认cookie
//$spider->setCookie('');

//通过list.txt内容下载歌曲
/*$music_list = file('list.txt');
$spider->downloadByListFile($music_list);*/

//下载歌单，473157676是歌单的ID
$spider->downLoadPlayList(473157676);