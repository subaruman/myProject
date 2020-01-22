<?php


namespace App\Classes;


use Carbon\Carbon;
use Doctrine\Common\Cache\ApcuCache;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\FrameRate;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Driver\FFMpegDriver;
use FFMpeg\Driver\FFProbeDriver;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Audio\Aac;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Format\Video\WebM;
use http\Url;
use PhpParser\Builder\Interface_;

class PostingVK extends SQL
{
    public $group_id = 159140427;
    public $user_id = 464383830;
    public $album_id = 249770511;
    public $vkAppId = 7081424; //id standalone приложения
    public $v = 5.101; //версия апи вк

    public $uploadUrl; //ссылка для загрузки
    public $post_data;  //массив с картинками для запроса

    public $access_token_user;
    public $access_token_group;

    public $dataFromBD;

    public function __construct()
    {
        $this->access_token_user = env('VK_TOKEN_USER', false);
        $this->access_token_group = env('VK_TOKEN_GROUP', false);

        $this->dataFromBD = $this->selectBD();
        if (empty($this->dataFromBD->was_posted)) {

        $methodVK = $this->downloadMedia();
        $responseArr = $this->uploadPostData();
//        dump($responseArr);

        $responseArr = $this->uploadOnServerVK($responseArr, $methodVK);
//        dd($responseArr);



        $this->createPost($responseArr);
        }
        else echo "<br>Такой пост уже был";

    }

    //скачивание медиа контента по юрл
    public function downloadMedia(){
        $dataFromBd = $this->selectBD();
        if (!empty($dataFromBd->Link_img)){

            $url = $dataFromBd->Link_img;
            $path = base_path('resources\src\photo_VK.jpg');
            file_put_contents($path, file_get_contents($url)); //скачивание медиа
            $this->img = new \CURLFile($path);
            $this->post_data = ['file1' => $this->img];
            $this->getUploadUrl('photos.getWallUploadServer');
            $methodVK = 'photos.saveWallPhoto'; //для использования в uploadOnServerVk

        } else if(!empty($dataFromBd->Link_gif)){

            $url = $dataFromBd->Link_gif;
            $path = base_path('resources\src\gif_VK.gif');
            file_put_contents($path, file_get_contents($url));
            $path = $this->convertToGif();
            $this->gif = new \CURLFile($path);
            $this->post_data = ['file' => $this->gif];
            $this->getUploadUrl('docs.getWallUploadServer');
            $methodVK = 'docs.save'; //для использования в uploadOnServerVk

        } else if(!empty($dataFromBd->Link_video)){

            $url = $dataFromBd->Link_video;
            $path1 = base_path('resources\src\video_VK.mp4');
            file_put_contents($path1, file_get_contents($url));

            $audio = $dataFromBd->Link_audio;
            $path2 = base_path('resources\src\audio_VK.mp4');
            file_put_contents($path2, file_get_contents($audio));

            $this->video = $this->concatVideoAudio($path1, $path2);

            $this->video = new \CURLFile($this->video);
            $this->post_data = ['video_file' => $this->video];
            $this->getUploadUrl('video.save');
            $methodVK = 'video.save';
        }
        return $methodVK;
    }

    //конвертация видео без звука в гиф
    public function convertToGif(){
        $ffmpeg = FFMpeg::create();
        $path = base_path('resources\src\rPikabu.gif');
        $video = $ffmpeg->open( base_path('resources\src\gif_VK.gif') );

//        $video
//            ->filters()->framerate(new FrameRate(30), 30);
//        $video->save(new WebM(), '/var/www/html/parser/resources/src/video.webm');
//        $video = $ffmpeg->open('/var/www/html/parser/resources/src/video.webm');
       $video
            ->gif(TimeCode::fromSeconds(0), new Dimension(320, 240))
            ->save($path);

        return $path;
    }

    //склеивание аудио и видео
    public function concatVideoAudio($video, $audio){
        $pathAudio = base_path('resources\src\audio.mp3');
        $pathVideo = base_path('resources\src\video_with_audio.mp4');
        $path = base_path('resources\src\\');

        //удаление старых файлов, от предыдущих постов
        if (file_exists($pathAudio))
            unlink($pathAudio);

        if (file_exists($pathVideo))
            unlink($pathVideo);

        //перекодирование видео в звук
        shell_exec("ffmpeg -i $audio -vn -ar 44100 -ac 2 -ab 320K -f mp3 " . $pathAudio);

        //склеивание звука и видео
        shell_exec("ffmpeg -i " . $pathAudio . " -i $video " . $pathVideo);
        return $pathVideo;

        /*$ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => 'C:\Program Files\ffmpeg-20200121-fc6fde2-win64-static\bin\ffmpeg.exe', // the path to the FFMpeg binary
            'ffprobe.binaries' => 'C:\Program Files\ffmpeg-20200121-fc6fde2-win64-static\bin\ffprobe.exe', // the path to the FFProbe binary
            'timeout'          => 300, // the timeout for the underlying process
            'ffmpeg.threads'   => 4,   // the number of threads that FFMpeg should use);
            'ffprobe.timeout'  => 30,
            ]);
        // Open your video file
        $video = $ffmpeg->open( $video );

// Set an audio format
        $audio_format = new Mp3();

// Extract the audio into a new file as mp3
        $video->save($audio_format, 'audio.mp3');*/
/*
// Set the audio file
        $audio = $ffmpeg->open( $pathAudio );

// Create the waveform
        $waveform = $audio->waveform();
        $waveform->save( 'waveform.png' );*/

    }



//photos.getWallUploadServer для картинки
//docs.getWallUploadServer для гиф
//docs.getUploadServer
    //получение ссылки для загрузки медиа контента
    public function getUploadUrl($methodVK){
        if ($methodVK === 'video.save'){
            $request_params = [
//                'album_id' => $this->album_id,
                'name' => $this->dataFromBD->header . ' r/Pikabu',
                'wallpost' => 0,
                'group_id' => $this->group_id,
                'access_token' => $this->access_token_user,
                'v' => $this->v
            ];
        } else {
            $request_params = [
                'album_id' => $this->album_id,
                'group_id' => $this->group_id,
                'access_token' => $this->access_token_user,
                'v' => $this->v
            ];
        }
        $get_params = http_build_query($request_params);
        $result = file_get_contents('https://api.vk.com/method/' . $methodVK . "?" . $get_params);
        $resultArr = json_decode($result);
        $this->uploadUrl = $resultArr->response->upload_url;
        return $this->uploadUrl;

    }


    //post запрос
    public function uploadPostData(){
        $ch = curl_init( $this->uploadUrl );
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, ["Content-Type: multipart/form-data"] );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->post_data);
        $response = curl_exec( $ch );
        curl_close( $ch );
        $responseArr = json_decode($response);
        return $responseArr;
    }
//photos.saveWallPhoto для постинга картинок
//docs.save для постинга гифок
    public function uploadOnServerVK($responseArr, $methodVK){
        if ($methodVK === 'photos.saveWallPhoto'){
            $request_params = [
                'group_id' => $this->group_id,
                'server' => $responseArr->server,
                'photo' => $responseArr->photo,
                'hash' => $responseArr->hash,
                'access_token' => $this->access_token_user,
                'v' => $this->v
            ];
        } else if ($methodVK === 'docs.save') {
            $request_params = [
                'file' => $responseArr->file,
                'title' => 'r/Pikabu',
//            'tags' => 'testTag',
//            'return_tags' => 0,
                'owner_id' => $this->group_id,
                'access_token' => $this->access_token_user,
                'v' => $this->v
            ];
            //видео загружается сразу, поэтому выход из этого метода
        } else return $responseArr = json_decode(json_encode($responseArr), true); //преобазование
        // обьекта в массив

        $get_params = http_build_query($request_params);

        //загрузка медиа на сервер вк
        $ch = curl_init( 'https://api.vk.com/method/' . $methodVK . '?' . $get_params);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);
        $response = curl_exec( $ch );
        curl_close( $ch );
        $responseArr = json_decode($response, true); //флаг true, возвращается не обьект, а массив
        return $responseArr;
    }

      public function createPost ($responseArr){
        if (!empty($responseArr['response'][0]['id'])) {         //проверка какой тип файла был загружен
            $photo_id = $responseArr['response'][0]['id'];
            $owner_id = $responseArr['response'][0]['owner_id'];
            $request_params = [
                'user_id' => $this->user_id,
                'owner_id' => -$this->group_id,
                'message' => $this->dataFromBD->header . PHP_EOL . PHP_EOL .
                  'Комментарии: ' . $this->dataFromBD->Link_post,
                'attachments' => 'photo' . $owner_id . '_' . $photo_id,
                'v' => 5.101,
                'access_token' => "$this->access_token_user"
            ];
        } else
            if (!empty($responseArr['response']['doc']['id'])) {  //проверка какой тип файла был загружен
                $doc_id = $responseArr['response']['doc']['id'];
                $owner_id = $responseArr['response']['doc']['owner_id'];
                $request_params = [
                    'user_id' => $this->user_id,
                    'owner_id' => -$this->group_id,
                    'message' => $this->dataFromBD->header . PHP_EOL . PHP_EOL .
                        'Комментарии: ' . $this->dataFromBD->Link_post,
                    'attachments' => 'doc' . $owner_id . '_' . $doc_id,
                    'v' => 5.101,
                    'access_token' => "$this->access_token_user"
                ];
            } else
                if (!empty($responseArr['video_id'])) {
                    $video_id = $responseArr['video_id'];
                    $owner_id = $responseArr['owner_id'];
                    $request_params = [
                        'user_id' => $this->user_id,
                        'owner_id' => -$this->group_id,
                        'message' => $this->dataFromBD->header . PHP_EOL . PHP_EOL .
                            'Комментарии: ' . $this->dataFromBD->Link_post,
                        'attachments' => 'video' . $owner_id . '_' . $video_id,
                        'v' => 5.101,
                        'access_token' => "$this->access_token_user",
//                        'publish_date' => self::$time,
                    ];
                }
        //непосредственно постинг в вк
        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/wall.post?' . $get_params));

        $this->updateBD($this->dataFromBD->id);
        printr($result);
    }
}