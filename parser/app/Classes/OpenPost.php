<?php


namespace App\Classes;

use PhpQuery\PhpQuery;

class OpenPost extends Parser
{
    public $text;
    public $img;
    public $gfycat;
    public $gif;
    public $video;
    public $audio_track;

    const TEXT = "._3xX726aBn29LDbsDtzr_6E._1Ap4F5maDtT1E1YuCiaO0r.D3IL3FD0RFy_mkKLPwL4";
    const IMG = "._3Oa0THmZ3f5iZXAQ0hBJ0k";
    const GFYCAT = "._3JgI-GOrkmyIeDeyzXdyUD._2CSlKHjH7lsjx0IpjORx14";
    const GIF = "._3spkFGVnKMHZ83pDAhW3Mx";
    const GIF_REPOST = "._2MkcR85HDnYngvlVW2gMMa";
//_3spkFGVnKMHZ83pDAhW3Mx _2b68Lt6xHaLir5082LDDA9 гиф
//_2MkcR85HDnYngvlVW2gMMa репост гиф

    public function __construct($url, $numberPost)
    {
        parent::__construct($url, $numberPost);
    }

    public function textPost() {
        $this->text = $this->contentOpenPage->find(OpenPost::TEXT);
        return $this->text;
    }

    public function imgPost() {
        $this->img = $this->contentOpenPage->find(OpenPost::IMG)->find('a')->attr('href');
        return $this->img;
    }

    public function gfycatPost() {
        $gfycat = $this->contentOpenPage->find( OpenPost::GFYCAT)->
        find("iframe")->attr("src");
        return $this->gfycat = $gfycat;
    }

    public function gifPost() {
        $this->gif = $this->contentOpenPage->find(OpenPost::GIF)->find('a')->attr('href');
        if (empty($this->gif)){
            $this->gif = $this->contentOpenPage->find(OpenPost::GIF_REPOST)->find('a')->attr('href');
        }
        if (!empty($this->gif)){
            return $this->gif;
        }
        return null;
    }


    public function videoPost() {
        $video_link = $this->contentOpenPage->find("video source");
        echo $video_link;
        if (!empty($video_link))
        {
            $video_link = $video_link->attr('src');
            $video_link = substr($video_link, 0, 32);
            $video_link = $this->checkAudioTrack($video_link);
            $video_link = $video_link  . "DASH_1080";

            if ($video_link !== "DASH_1080" && $video_link !== "DASH_720" &&
                $video_link !== "DASH_480" && $video_link !== "DASH_360" &&
                $video_link !== "DASH_240" && $video_link !== "DASH_144"
                && empty($this->gfycat) && empty($this->gif)){

                $this->video = $this->checkVideoQuality($video_link, 0);
                return $this->video;
            }
            else return null;
        }
    }

    //функция для проверки, есть ли видео определенного качества?
    public function checkVideoQuality($urlForParse, $counterQuality) {
        $arrQuality = ["DASH_720", "DASH_480", "DASH_360", "DASH_240", "DASH_144"];
        if (empty($urlForParse)){
            return null;
        }
        //если выбранное качество не существует
        if (@file_get_contents($urlForParse,false, $this->context) === FALSE){
            $urlForParse = substr($urlForParse, 0, 32) . $arrQuality[$counterQuality];
            $counterQuality++;
            //если счетчик выходит за пределы массива
            if ($counterQuality === 5){
                return null;
            }
            return $this->checkVideoQuality($urlForParse, $counterQuality);
        }
        else {
            return $urlForParse;
        }

    }

    //проверка есть ли звуковая дорожка у видео
    public function checkAudioTrack($video_link) {
        $audio_track = $video_link . "audio";
        //если нет аудиодорожки, то видео является гиф
        if (@file_get_contents($audio_track,false, $this->context) === FALSE){
            if (empty($this->gif)){ //если изначально было спаршено как гиф, то не выполнять
                $this->gif = $this->checkVideoQuality($video_link . "DASH_1080", 0);
                echo $this->gif;
            }
            return null;
        }
        return $video_link;
        /*$video_link = substr($this->video, 0, -9);
        $quality = substr($video_link, -9, 9); //определение какого качества видео
        if ($quality === "DASH_1080"){ //для кач-ва 1080
            $audio_track = $video_link . "audio";
        }
        else { //для остального кач-ва
            $video_link = substr($this->video, 0, -8);
            $audio_track = $video_link . "audio";
        }
        if (!empty($audio_track)){
            $this->audio_track = $audio_track;
            return $audio_track;
        } else {
            $this->video = $this->gif;
            return null;
        }*/
    }


}
