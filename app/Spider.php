<?php

namespace App;

class Spider
{
    private $crawler;

    public function __construct()
    {
        $this->crawler = new Crawler();
    }


    /**
     * 搜索歌曲
     * @param string $song_name 歌曲名称
     * @return mixed
     */
    private function searchSong($song_name = '')
    {
        $info = $this->crawler->searchSong($song_name);

        return $info;
    }


    /**
     * 获取歌曲的信息
     * @param $song_id 歌曲的ID
     * @return mixed
     */
    private function getSongInfo($song_id)
    {
        $songInfo = $this->crawler->getSongUrl($song_id);

        return $songInfo;
    }


    /**
     * 设置cookie
     * @param $cookie
     */
    public function setCookie($cookie)
    {
        $this->crawler->setCookie($cookie);
    }

    /**
     * 下载歌曲
     * @param $name_name 歌曲名称
     * @return string
     */
    private function download($name_name)
    {
        $song_info = $this->searchSong($name_name);

        $song_url_info = $this->getSongInfo($song_info['id']);


        $song_detail_info = [
            'id' => $song_info['id'],
            'name' => $song_info['name'],
            'article' => $song_info['article'],
            'album_pic' => $song_info['album_pic'],
            'url' => $song_url_info['url'],
        ];

        return $this->crawler->downloadSong($song_detail_info);
    }

    /**
     * 通过 list文件下载歌曲
     * @param $music_list
     */
    public function downloadByListFile($music_list)
    {
        if (count($music_list)) {
            foreach ($music_list as $song) {
                echo trim($song) . ">>>" . "下载中:" . PHP_EOL;
                $response = $this->download($song);
                if ($response == 'success') {
                    echo '下载成功' . PHP_EOL;
                } else {
                    echo '下载失败' . PHP_EOL;
                }
            }
        } else {
            echo '歌曲列表不能为空!' . PHP_EOL;
        }
    }


    /**
     * 获取歌单的信息
     * @param $id
     * @return array
     */
    private function getPlayList($id)
    {
        return $this->crawler->getPlayList($id);
    }

    /**
     *下载歌单功能
     * @param $id
     */
    public function downLoadPlayList($id)
    {
        $play_list_songs = $this->getPlayList($id)['songs'];

        if (count($play_list_songs)) {

            foreach ($play_list_songs as $play_list_song) {
                $song_url_info = $this->getSongInfo($play_list_song['id']);
                $song_detail_info = [
                    'id' => $play_list_song['id'],
                    'name' => $play_list_song['name'],
                    'article' => $play_list_song['article'],
                    'album_pic' => $play_list_song['album_pic'],
                    'url' => $song_url_info['url'],
                ];

                echo $song_detail_info['name'] . ">>>" . "下载中:" . PHP_EOL;
                $response = $this->crawler->downloadSong($song_detail_info);
                if ($response == 'success') {
                    echo '下载成功' . PHP_EOL;
                } else {
                    echo '下载失败' . PHP_EOL;
                }
            }

        } else {
            echo '歌单错误!' . PHP_EOL;
            die();
        }
    }
}
