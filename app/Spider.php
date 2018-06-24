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
        $info =  $this->crawler->searchSong($song_name);

        return $info;
    }


    /**
     * 获取歌曲的信息
     * @param $song_id 歌曲的ID
     * @return mixed
     */
    private function getSongInfo($song_id)
    {
        $songInfo =  $this->crawler->getSongUrl($song_id);

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
    public function download($name_name)
    {
        $song_info =  $this->searchSong($name_name);

        $song_url_info = $this->getSongInfo($song_info['id']);


        $song_detail_info = [
            'id' => $song_info['id'],
            'name' => $song_info['name'],
            'article' => $song_info['article'],
            'album_pic' => $song_info['album_pic'],
            'url' => $song_url_info['url'],
        ];

//        print_r($song_detail_info);

        return  $this->crawler->downloadSong($song_detail_info);
    }
}
