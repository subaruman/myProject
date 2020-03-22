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
    public $gfycatVkId;

    public $nextPostTime;

    public function __construct()
    {
        $this->access_token_user = env('VK_TOKEN_USER', false);
        $this->access_token_group = env('VK_TOKEN_GROUP', false);

        $this->getPost();

        $this->dataFromBD = $this->selectBD();
        if (empty($this->dataFromBD->was_posted) && empty($this->dataFromBD->text )) {

            $methodVK = $this->downloadMedia();

            $responseArr = $this->uploadPostData();
//         dd($responseArr);

            $responseArr = $this->uploadOnServerVK($responseArr, $methodVK);
//         dd($responseArr);


            $this->createPost($responseArr);
        } else {
            echo "<br>Такой пост уже был";
        }

    }

    //получение последнего поста со стены
    public function getPost()
    {
        $request_params = [
            'owner_id' => -$this->group_id,
            'domain' => 'https://vk.com/club' . $this->group_id,
            'count' => 10,
            'filter' => 'postponed',
            'access_token' => $this->access_token_group,
            'v' => 5.101,
        ];
        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/wall.get?' . $get_params));
        if (!empty($result->response->items)) {
            $arItems = $result->response->items;
            $arLastItem = end($arItems);
            $this->nextPostTime = $arLastItem->date + 1728; // + 30 минут для след. поста
        } else {
            $time = time();
            $this->nextPostTime = $time + 600;
        }
    }

    //скачивание медиа контента по юрл
    public function downloadMedia()
    {
        $dataFromBd = $this->selectBD();
        if (!empty($dataFromBd->Link_img)) {

            $url = $dataFromBd->Link_img;
            $path = base_path('resources\src\photo_VK.jpg');
            file_put_contents($path, file_get_contents($url)); //скачивание медиа
            $this->img = new \CURLFile($path);
            $this->post_data = ['file1' => $this->img];
            $this->getUploadUrl('photos.getWallUploadServer');
            $methodVK = 'photos.saveWallPhoto'; //для использования в uploadOnServerVk

        } else {
            if (!empty($dataFromBd->Link_gif)) {

                $url = $dataFromBd->Link_gif;
                $path = base_path('resources\src\gif_VK.gif');
                file_put_contents($path, file_get_contents($url));
                $path = $this->convertToGif();
                $this->gif = new \CURLFile($path);
                $this->post_data = ['file' => $this->gif];
                $this->getUploadUrl('docs.getWallUploadServer');
                $methodVK = 'docs.save'; //для использования в uploadOnServerVk

            } else {
                if (!empty($dataFromBd->Link_video)) {

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
                } else {
                    if (!empty($dataFromBd->Link_imgur)) {

                        $url = $dataFromBd->Link_imgur;
                        $path = base_path('resources\src\imgur_VK.mp4');
                        unlink($path);
                        file_put_contents($path, file_get_contents($url));

                        $this->imgur = new \CURLFile($path);
                        $this->post_data = ['video_file' => $this->imgur];
                        $this->getUploadUrl('video.save');
                        $methodVK = 'video.save';
                    } else {
                        if (!empty($dataFromBd->Link_gfycat)) {
                            $this->getUploadUrl('video.save');
                            $methodVK = "video.save";
                        } else {
                            if (!empty($dataFromBd->text)) {
                                $methodVK = "text";
                            }
                        }
                    }
                }

            }
        }
        return $methodVK;
    }

    //конвертация видео без звука в гиф
    public function convertToGif()
    {
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => 'C:\Program Files\ffmpeg\bin\ffmpeg.exe', // the path to the FFMpeg binary
            'ffprobe.binaries' => 'C:\Program Files\ffmpeg\bin\ffprobe.exe', // the path to the FFProbe binary
            'timeout'          => 8000, // the timeout for the underlying process
            'ffmpeg.threads'   => 12,   // the number of threads that FFMpeg should use
        ]);
        $path = base_path('resources\src\rPikabu.gif'); //гиф для загрузки в группу
        $video = $ffmpeg->open(base_path('resources\src\gif_VK.gif')); //гиф скачаенная с реддита

        $video
            ->gif(TimeCode::fromSeconds(0), new Dimension(430, 300))
            ->save($path);

        // shell_exec("ffmpeg -i " . $video . "-filter_complex ""[0:v] fps=12,scale=480:-1,split [a][b];[a] palettegen [p];[b][p] paletteuse" . $path);
        // ffmpeg -i gif_VK.mp4 -pix_fmt rgb24 -filter_complex "[0:v] fps=12,scale=480:-1,split" rPikabu.gif

        return $path;
    }

    //склеивание аудио и видео
    public function concatVideoAudio($video, $audio)
    {
        $pathAudio = base_path('resources\src\audio.mp3');
        $pathVideo = base_path('resources\src\video_with_audio.mp4');
        $path = base_path('resources\src\\');

        //удаление старых файлов, от предыдущих постов
        if (file_exists($pathAudio)) {
            unlink($pathAudio);
        }

        if (file_exists($pathVideo)) {
            unlink($pathVideo);
        }

        //перекодирование видео в звук
        shell_exec("ffmpeg -i $audio -vn -ar 44100 -ac 2 -ab 320K -f mp3 " . $pathAudio);

        //склеивание звука и видео
        shell_exec("ffmpeg -i " . $pathAudio . " -i $video " . $pathVideo);
        return $pathVideo;

    }



//photos.getWallUploadServer для картинки
//docs.getWallUploadServer для гиф
//docs.getUploadServer
    //получение ссылки для загрузки медиа контента
    public function getUploadUrl($methodVK)
    {
        if ($methodVK === 'video.save') {
            if (!empty($this->dataFromBD->Link_gfycat)) {
                $request_params = [
                    'name' => $this->dataFromBD->header . ' r/Pikabu',
                    'description' => '',
                    'link' => $this->dataFromBD->Link_gfycat,
                    'wallpost' => 0,
                    'group_id' => $this->group_id,
                    'access_token' => $this->access_token_user,
                    'v' => $this->v
                ];
            } else {
                $request_params = [
//                'album_id' => $this->album_id,
                    'name' => $this->dataFromBD->header . ' r/Pikabu',
                    'wallpost' => 0,
                    'group_id' => $this->group_id,
                    'access_token' => $this->access_token_user,
                    'v' => $this->v
                ];
            }

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
//        dd($resultArr);
        if ($methodVK === "video.save") {
            $this->gfycatVkId = $resultArr->response->video_id;
        }
        $this->uploadUrl = $resultArr->response->upload_url;
        return $this->uploadUrl;

    }


    //post запрос
    public function uploadPostData()
    {
        $ch = curl_init($this->uploadUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: multipart/form-data"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post_data);
        $response = curl_exec($ch);
        curl_close($ch);
        $responseArr = json_decode($response);
        return $responseArr;
    }
//photos.saveWallPhoto для постинга картинок
//docs.save для постинга гифок
    public function uploadOnServerVK($responseArr, $methodVK)
    {
        if ($methodVK === 'photos.saveWallPhoto') {
            $request_params = [
                'group_id' => $this->group_id,
                'server' => $responseArr->server,
                'photo' => $responseArr->photo,
                'hash' => $responseArr->hash,
                'access_token' => $this->access_token_user,
                'v' => $this->v
            ];
        } else {
            if ($methodVK === 'docs.save') {
                $request_params = [
                    'file' => $responseArr->file,
                    'title' => 'r/Pikabu',
                    'owner_id' => $this->group_id,
                    'access_token' => $this->access_token_user,
                    'v' => $this->v
                ];
                //видео загружается сразу, поэтому выход из этого метода
            } else {
                return $responseArr = json_decode(json_encode($responseArr), true); //преобазование обьекта в массив

            }
        }

        $get_params = http_build_query($request_params);

        //загрузка медиа на сервер вк
        $ch = curl_init('https://api.vk.com/method/' . $methodVK . '?' . $get_params);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);
        $response = curl_exec($ch);
        curl_close($ch);
        $responseArr = json_decode($response, true); //флаг true, возвращается не обьект, а массив
        return $responseArr;
    }

    public function createPost($responseArr)
    {
        if (!empty($responseArr['response'][0]['id'])) {         //проверка какой тип файла был загружен
            $photo_id = $responseArr['response'][0]['id'];
            $owner_id = $responseArr['response'][0]['owner_id'];
            $request_params = [
                'user_id' => $this->user_id,
                'owner_id' => -$this->group_id,
                'message' => $this->dataFromBD->header . PHP_EOL . PHP_EOL .
                    'Комментарии: ' . $this->dataFromBD->Link_post,
                'attachments' => 'photo' . $owner_id . '_' . $photo_id . ','
                . $this->dataFromBD->Link_post,
                'publish_date' => $this->nextPostTime,
                'v' => 5.101,
                'access_token' => "$this->access_token_user"
            ];
        } else {
            if (!empty($responseArr['response']['doc']['id'])) {  //проверка какой тип файла был загружен
                $doc_id = $responseArr['response']['doc']['id'];
                $owner_id = $responseArr['response']['doc']['owner_id'];
                $request_params = [
                    'user_id' => $this->user_id,
                    'owner_id' => -$this->group_id,
                    'message' => $this->dataFromBD->header . PHP_EOL . PHP_EOL .
                        'Комментарии: ' . $this->dataFromBD->Link_post,
                    'attachments' => 'doc' . $owner_id . '_' . $doc_id,
                    'publish_date' => $this->nextPostTime,
                    'v' => 5.101,
                    'access_token' => "$this->access_token_user"
                ];
            } else {
                if (!empty($responseArr['video_id'])) {
                    $video_id = $responseArr['video_id'];
                    $owner_id = $responseArr['owner_id'];
                    $request_params = [
                        'user_id' => $this->user_id,
                        'owner_id' => -$this->group_id,
                        'message' => $this->dataFromBD->header . PHP_EOL . PHP_EOL .
                            'Комментарии: ' . $this->dataFromBD->Link_post,
                        'attachments' => 'video' . $owner_id . '_' . $video_id,
                        'publish_date' => $this->nextPostTime,
                        'v' => 5.101,
                        'access_token' => "$this->access_token_user",
                    ];
                } else {
                    if ($responseArr["response"] === 1) {
                        $request_params = [
                            'user_id' => $this->user_id,
                            'owner_id' => -$this->group_id,
                            'message' => $this->dataFromBD->header . PHP_EOL . PHP_EOL .
                                'Комментарии: ' . $this->dataFromBD->Link_post,
                            'attachments' => 'video' . '-' . $this->group_id . '_' . $this->gfycatVkId,
                            'publish_date' => $this->nextPostTime,
                            'v' => 5.101,
                            'access_token' => "$this->access_token_user",
                        ];
                    }
                }
            }
        }
        //непосредственно постинг в вк
        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/wall.post?' . $get_params));

        $this->updateBD($this->dataFromBD->id); //was_posted = 1
        printr($result);
    }
}
