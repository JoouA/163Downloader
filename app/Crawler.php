<?php
/**
 * Created by PhpStorm.
 * User: TANG
 * Date: 2018/6/23
 * Time: 20:04
 */

namespace App;

use GuzzleHttp\Client;
use App\ProgressBar\Manager;

class Crawler
{
    private $headers = [];
    private $client;
    private $encrypyed;
    protected $bar;
    private $progressBar;

    // 是否下载完成
    protected $downloaded = false;

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

        $this->progressBar = new Manager(0, 100);
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

        $song_format_infos = $this->songInfoFormat($song_all_infos);

        return $song_format_infos;
    }

    /**
     * 格式化歌曲信息
     * @param $song_info
     * @return array
     */
    private  function songInfoFormat($song_info)
    {
        $format_song_infos =  [
            'id' => $song_info['id'],
            'name' => $song_info['name'],
            'article' => [],
            'album' => $song_info['al']['name'],
            'album_pic' => $song_info['al']['picUrl'],
            'lyric_id' => $song_info['id'],
            'mv_id' => $song_info['mv'],
            'publishTime' => date('Y-m-d', $song_info['publishTime'] / 1000),
        ];

        foreach ($song_info['ar'] as $song_ar_info) {
            $format_song_infos['article'][] = $song_ar_info['name'];
        }

        return $format_song_infos;

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
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 100);
            // 进度条的触发函数
            // 每次都要实例化一下对象，不然foreach在此进来就找不到progress这个方法了
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array(new self(), 'progress'));
            // 是否开启进度条  0 表示开启下载进度条
            curl_setopt($ch, CURLOPT_NOPROGRESS, 0);
            // 1 的时候不输出现在的内容 0 输出
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($ch, CURLOPT_FILE, $fp);


            // 执行后返回true或者false
            $response = curl_exec($ch);

            fclose($fp);

            curl_close($ch);


            if ($response) {
                $this->progressBar->update(100);
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
     * 返回歌单的信息
     * @param $id
     * @return array
     */
    public function getPlayList($id)
    {
        $url = 'http://music.163.com/weapi/v3/playlist/detail';
        $params = [
            'body'   => [
                's'  => '0',
                'id' => $id,
                'n'  => '1000',
                't'  => '0',
            ],
        ];

        $paramsInfo = $this->encrypyed->neteaseAESCBC($params);

        $info =  $this->guzzle($url,$paramsInfo);

        $creator =  $info['playlist']['creator'];
        $tracks = $info['playlist']['tracks'];

        $songs = [];

        foreach ($tracks as $track) {
            $songs[] = $this->songInfoFormat($track);
        }


        $play_list_info_format = [
            'Author' => [
                'nickname' => $creator['nickname'],
                'signature' => $creator['signature'],
                'avatarUrl' => $creator['avatarUrl'],
            ],
            'songs' => $songs,
            'trackCount' => $info['playlist']['trackCount'],
            'coverImgUrl' => $info['playlist']['coverImgUrl'],
        ];

        return $play_list_info_format;
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


    /**
     * 进度条下载.
     *
     * @param $resource
     * @param $countDownloadSize 总下载量
     * @param $currentDownloadSize 当前下载量
     * @param $countUploadSize
     * @param $currentUploadSize
     */
    public function progress($resource, $countDownloadSize, $currentDownloadSize, $countUploadSize, $currentUploadSize)
    {

        // 等于 0 的时候，应该是预读资源不等于0的时候即开始下载
        // 这里的每一个判断都是坑，多试试就知道了
        if (0 === $countDownloadSize) {
            return false;
        }
        // 有时候会下载两次，第一次很小，应该是重定向下载
        if ($countDownloadSize > $currentDownloadSize) {
            $this->downloaded = false;
            // 继续显示进度条
        } // 已经下载完成还会再发三次请求
        elseif ($this->downloaded) {
            return false;
        } // 两边相等下载完成并不一定结束，
        elseif ($currentDownloadSize === $countDownloadSize) {
            return false;
        }

        $curl_info = curl_getinfo($resource);

        $size_download = $curl_info['size_download'];
        $size_content_length = $curl_info['download_content_length'];

        if ($size_content_length > 0) {
            $percentage = intval(($size_download / $size_content_length) * 100);
            if ($percentage <= 100) {
                $this->progressBar->update($percentage);
            }
        }
    }

}