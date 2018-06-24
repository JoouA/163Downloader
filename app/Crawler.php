<?php
/**
 * Created by PhpStorm.
 * User: TANG
 * Date: 2018/6/23
 * Time: 20:04
 */

namespace App;

use GuzzleHttp\Client;

class Crawler
{
    private $headers = [];
    private $client;
    private $encrypyed;

    public function __construct()
    {
        $this->headers = [
            'Referer' => 'https://music.163.com/',
            'Cookie' => 'appver=1.5.9;',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36',
            'X-Real-IP' => long2ip(mt_rand(1884815360, 1884890111)),
            'Accept' => '*/*',
            'Accept-Language' => 'zh-CN,zh;q=0.8,gl;q=0.6,zh-TW;q=0.4',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $this->encrypyed = new Encrypyed();
    }

    /**
     * 设置client
     * @param array $config
     */
    private function setClient($config = [])
    {
        $this->client = new Client($config);
    }


    public function setCookie($cookie)
    {
        $this->headers['Cookie'] = $cookie;
    }

    /**
     * 格式化歌曲内容返回
     * @param $song_name
     * @return mixed
     */
    public function searchSong($song_name)
    {
        // 搜索歌曲API
        $url = 'http://music.163.com/weapi/cloudsearch/get/web?csrf_token=';

        $params = [
            'body' => [
                's' => $song_name,
                'limit' => 2,
                'sub' => false,
                'type' => 1,
                'offset' => 0,
            ]
        ];

        $paramsInfo = $this->encrypyed->neteaseAESCBC($params);


        $song_all_infos = $this->search($url, $paramsInfo)['result']['songs'][0];


        $song_format_infos = [
            'id' => $song_all_infos['id'],
            'name' => $song_all_infos['name'],
            'article' => [],
            'album' => $song_all_infos['al']['name'],
            'album_pic' => $song_all_infos['al']['picUrl'],
            'lyric_id' => $song_all_infos['id'],
            'mv_id' => $song_all_infos['mv'],
            'publishTime' => date('Y-m-d', $song_all_infos['publishTime'] / 1000),
        ];

        foreach ($song_all_infos['ar'] as $song_ar_info) {
            $song_format_infos['article'][] = $song_ar_info['name'];
        }

        return $song_format_infos;
    }


    /**
     * 搜索歌曲
     * @return mixed
     * @param string $url 接口信息
     * @param array $paramsInfo 表单信息
     */
    private function search($url = '', $paramsInfo = [])
    {
        return $this->guzzle($url, $paramsInfo);
    }


    /**
     * 获取歌曲的链接
     * @param $song_id
     * @param int $bit_rate
     * @return mixed
     */
    public function getSongUrl($song_id, $bit_rate = 320000)
    {
        $url = 'http://music.163.com/weapi/song/enhance/player/url?csrf_token=';

        $csrf = '';

        $params = [
            'body' => [
                'ids' => [$song_id], 'br' => $bit_rate, 'csrf_token' => $csrf
            ]
        ];

        $paramsInfo = $this->encrypyed->neteaseAESCBC($params);

        $songInfo = $this->guzzle($url, $paramsInfo)['data'][0];

        $songInfoFormat = [
            'id' => $songInfo['id'],
            'url' => $songInfo['url'],
            'bite' => intval($songInfo['br'] / 1000),
            'size' => number_format($songInfo['size'] / (1000 * 1000), 1) . 'M',
            'type' => $songInfo['type'],
        ];

        return $songInfoFormat;
    }

    /**
     * 下载歌曲
     * @param $song_detail_info
     * @return string
     */
    public function downloadSong($song_detail_info)
    {
        $ch = curl_init();

        $folder = pathinfo(__DIR__, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . 'music';


        if (!is_dir($folder)) {
            mkdir($folder, '0777', true);
        }

        if ($song_detail_info['url']) {
            $ext = explode('.', $song_detail_info['url'])[count(explode('.', $song_detail_info['url'])) - 1];

            $filename = $song_detail_info['name'] . '.' . $ext;

            $fp = fopen($folder . DIRECTORY_SEPARATOR . $filename, 'w');

            curl_setopt($ch, CURLOPT_URL, $song_detail_info['url']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,10);
            curl_setopt($ch, CURLOPT_TIMEOUT,100);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FILE, $fp);

            // 执行后返回true或者false
            $response = curl_exec($ch);

            curl_close($ch);

            fclose($fp);

            if ($response) {
                return $response = 'success';
            } else {
                return $response = 'fail';
            }
        } else {
            echo $song_detail_info['name'] . '没有超找到这个资源';
            return $response = 'fail';
        }

    }


    /**
     * 发起API请求
     * @param string $url
     * @param array $paramsInfo
     * @return mixed
     */
    private function guzzle($url = '', $paramsInfo = [])
    {
        $this->setClient();

        $response = $this->client->request('POST', $url, [
            'headers' => $this->headers,
            'form_params' => $paramsInfo,
        ]);

        return json_decode($response->getBody(), true);
    }

}