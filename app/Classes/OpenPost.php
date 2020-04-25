<?php


namespace App\Classes;

use FFMpeg\FFProbe;
use PhpQuery\PhpQuery;
use PHPUnit\Exception;
use Symfony\Component\Finder\SplFileInfo;

class OpenPost extends Parser
{
    public $text;
    public $img;
    public $gfycat;
    public $gif;
    public $video;
    public $audio;
    public $silent_video;
    public $long_img;
    public $arPartsLongImg;

    const TEXT = "._3xX726aBn29LDbsDtzr_6E._1Ap4F5maDtT1E1YuCiaO0r.D3IL3FD0RFy_mkKLPwL4";
    const IMG = "._3Oa0THmZ3f5iZXAQ0hBJ0k";
    const GFYCAT = "._3JgI-GOrkmyIeDeyzXdyUD._2CSlKHjH7lsjx0IpjORx14";
    const GIF = "._3spkFGVnKMHZ83pDAhW3Mx";
    const GIF_REPOST = "._2MkcR85HDnYngvlVW2gMMa";

//_3spkFGVnKMHZ83pDAhW3Mx _2b68Lt6xHaLir5082LDDA9 гиф
//_2MkcR85HDnYngvlVW2gMMa репост гиф

    public function __construct($url, $cookies, $headers)
    {
        //курл вместо file_get_contents
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_COOKIE, $cookies);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        $file = curl_exec($curl);
        curl_close($curl);

        $this->contentOpenPage = phpQuery::newDocument($file);
    }

    public function textPost()
    {
        $this->text = strip_tags($this->contentOpenPage->find(OpenPost::TEXT));
        return $this->text;
    }

    public function imgPost()
    {
        $this->img = $this->contentOpenPage->find(OpenPost::IMG)->find('a')->attr('href');
        if (!empty($this->img)) {
            if ($this->isLongImg($this->img) === true) {
                $this->long_img = $this->img;
                $this->img = null;
                return $this->long_img;
            } else {
                return $this->img;
            }
        }
        return $this->img;
    }

    public function gfycatPost()
    {
        $gfycat = $this->contentOpenPage->find(OpenPost::GFYCAT)->
        find("iframe")->attr("src");
        if (!empty($gfycat)) {
            $file = file_get_contents($gfycat);
            $file = phpQuery::newDocument($file);

            $gfycat = $file->find(".embedly-embed")->attr("src");
            $url = mb_stristr($gfycat, "url=");
            $url = substr(urldecode($url), 4); //убирает кодированные символы и обрезает url=
            $endSymbol = strpos($url, "&");
            $gfycat = substr($url, 0, $endSymbol);
        }


        return $this->gfycat = $gfycat;
    }

    public function gifPost()
    {
        $this->gif = $this->contentOpenPage->find(OpenPost::GIF)->find('a')->attr('href');
//        dump($this->gif);
        $this->getDuration($this->gif);

        if (empty($this->gif)) {
            $this->gif = $this->contentOpenPage->find(OpenPost::GIF_REPOST)->find('a')->attr('href');
//            dump($this->gif);

            if (!empty($this->gif)) {
                $file = file_get_contents($this->gif);
                $file = phpQuery::newDocument($file);
                $imgurGif = $file->find(".video-container")->children()->attrs("content"); //gif imgur
                if (!empty($imgurGif[8])) {
                    $imgurGif = $imgurGif[8];
                    $format = substr($imgurGif, -4, 4); //8 индекс гифки
                    if (!empty($imgurGif) && $format === "gifv") {
                        $imgurVideo = $file->find("source")->attr("src"); //video imgur
                        $link = "https:" . $imgurVideo;
                        return $this->getDuration($link);
                    }
                }

                $imgurVideo = $file->find("source")->attr("src"); //video imgur
                if (!empty($imgurVideo)) {
                    return $this->silent_video = "https:" . $imgurVideo;
                } else {
                    $imgurJpg = $file->find(".post-title-meta")->find("a")->attr("href"); //img imgur
                    $file = file_get_contents($imgurJpg);
                    $this->contentOpenPage = phpQuery::newDocument($file);
                    $this->imgPost();
                    return $this->gif = null;
                }
            }
        }
        return $this->gif;
    }


    public function videoPost()
    {
        $video_link = $this->contentOpenPage->find("video source");
        echo $video_link;
        if (!empty($video_link)) {
            $video_link = $video_link->attr('src');
            $video_link = substr($video_link, 0, 32);
            $video_link = $this->checkAudioTrack($video_link);

            $video_link = $video_link . "DASH_1080";
            //проверка video_link на пустоту
            if ($video_link !== "DASH_1080" && $video_link !== "DASH_720" &&
                $video_link !== "DASH_480" && $video_link !== "DASH_360" &&
                $video_link !== "DASH_240" && $video_link !== "DASH_144"
                && empty($this->gfycat) && empty($this->gif)) {

                $this->video = $this->checkVideoQuality($video_link, 0);
                return $this->video;
            } else return null;
        }
    }

    //функция для проверки, есть ли видео определенного качества?
    public function checkVideoQuality($urlForParse, $counterQuality)
    {
        $arrQuality = ["DASH_720", "DASH_480", "DASH_360", "DASH_240", "DASH_144"];
        if (empty($urlForParse)) {
            return null;
        }
        //если выбранное качество не существует
        if (@file_get_contents($urlForParse, false, $this->context) === FALSE) {
            $urlForParse = substr($urlForParse, 0, 32) . $arrQuality[$counterQuality];
            $counterQuality++;
            //если счетчик выходит за пределы массива
            if ($counterQuality === 5) {
                return null;
            }
            return $this->checkVideoQuality($urlForParse, $counterQuality);
        } else {
            return $urlForParse;
        }

    }

    //проверка есть ли звуковая дорожка у видео
    public function checkAudioTrack($video_link)
    {
        $audio_track = $video_link . "audio";
        //если нет аудиодорожки, то видео является гиф
        if (@file_get_contents($audio_track, false, $this->context) === FALSE) {
            if (empty($this->gif)) { //если изначально было спаршено как гиф, то не выполнять
                $this->gif = $this->checkVideoQuality($video_link . "DASH_1080", 0);
                $this->getDuration($this->gif);
                echo $this->gif;
            }

            return null;
        }
        $this->audio = $audio_track;
        return $video_link;
    }

    //получение длины видео
    public function getDuration($link)
    {
        $ffprobe = FFProbe::create();
        $path = base_path('resources\src\getDuration.mp4');

        if (empty($link)) {
            return null;
        }

        file_put_contents($path, file_get_contents($link));
        if ($ffprobe->format($path)->get('duration') > 30) {
            unlink($path);
            $this->gif = null;
            return $this->silent_video = $link;
        } else {
            unlink($path);
            $this->silent_video = null;
            return $this->gif = $link;
        }
    }

    public function isLongImg($url)
    {
        $path = base_path('resources\src\long_img\long_img.jpg');
        file_put_contents($path, file_get_contents($url));

        $width = getimagesize($path)[0];
        $height = getimagesize($path)[1];           //1 индекс высоты картинки из массива getimagesize

        try {
            $longImg = @(imagecreatefromjpeg($path));
        } catch (Exception $e) {
            $longImg = imagecreatefrompng($path);
        }

        if ($height > 2500) {
            $this->cropLongImg($height, $width, $longImg);
            return true;
        }
        return false;
    }

    public function cropLongImg($height, $width, $longImg)
    {
        $this->arPartsLongImg = [
            base_path('resources\src\long_img\parts\part1.jpg'),
            base_path('resources\src\long_img\parts\part2.jpg'),
            base_path('resources\src\long_img\parts\part3.jpg'),
            base_path('resources\src\long_img\parts\part4.jpg'),
            base_path('resources\src\long_img\parts\part5.jpg'),
            base_path('resources\src\long_img\parts\part6.jpg'),
            base_path('resources\src\long_img\parts\part7.jpg'),
            base_path('resources\src\long_img\parts\part8.jpg'),
            base_path('resources\src\long_img\parts\part9.jpg'),
        ];

        foreach ($this->arPartsLongImg as $elem) {
            if (file_exists($elem)) {
                unlink($elem);
            }
        }

        $limit = floor($height / 2000);
        $residue = -($limit * 2000 - $height);     //остаток

        $y = 0;
        for ($i = 0; $i < $limit; $i++) {
            $arCoordinate = [
                "x" => "0",
                "y" => $y,
                "width" => $width,
                "height" => 2000,
            ];
            $img = imagecrop($longImg, $arCoordinate);
            imagejpeg($img, $this->arPartsLongImg[$i], 100);
            $y += 2000;
        }

        if ($residue > 500) {
            $arCoordinate = [
                "x" => "0",
                "y" => $y,
                "width" => $width,
                "height" => $residue,
            ];
            $img = imagecrop($longImg, $arCoordinate);
            imagejpeg($img, $this->arPartsLongImg[$i], 100);
        }
    }
}
