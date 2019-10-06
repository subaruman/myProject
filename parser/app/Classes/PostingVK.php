<?php


namespace App\Classes;


use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;

class PostingVK extends SQL
{
    public $group_id = 159140427;
    public $user_id = 464383830;
    public $album_id = 249770511;
    public $vkAppId = 7081424; //id standalone приложения
    public $v = 5.101; //версия апи вк

    public $request_params;

    public $uploadUrl; //ссылка для загрузки
    public $img;    //сама картинка
    public $post_data;  //массив с картинками для запроса

    public $media;

    public $access_token_user;
    public $access_token_group;

    public function __construct()
    {
        $this->access_token_user = env('VK_TOKEN_USER', false);
        $this->access_token_group = env('VK_TOKEN_GROUP', false);

        $methodVK = $this->downloadMedia();
        $responseArr = $this->uploadPostData();
        printr($responseArr);

        $responseArr = $this->uploadOnServerVK($responseArr, 'photos.saveWallPhoto');
        printr($responseArr);

        $this->createPost($responseArr);

    }

    //скачивание медиа контента по юрл
    public function downloadMedia(){
        $path = '';
        $dataFromBd = $this->selectBD();
        if (!empty($dataFromBd->Link_img)){
            $url = $dataFromBd->Link_img;
            $path = '/var/www/html/parser/resources/src/photo_VK.jpg';
            file_put_contents($path, file_get_contents($url)); //скачивание медиа
            $this->img = new \CURLFile($path);
            $this->post_data = array('file1' => $this->img);
            $methodVK = 'photos';
            $this->getUploadUrl($methodVK . '.getWallUploadServer');
            $methodVK = 'photos.saveWallPhoto'; //для использования в uploadOnServerVk

        } else if(!empty($dataFromBd->Link_gif)){

            $url = $dataFromBd->Link_gif;
            $path = '/var/www/html/parser/resources/src/gif_VK.gif';
            file_put_contents($path, file_get_contents($url));
            $path = $this->convertToGif();
            $this->gif = new \CURLFile($path);
            $this->post_data = ['file' => $this->gif];
            $methodVK = 'docs';
            $this->getUploadUrl($methodVK . '.getWallUploadServer');
            $methodVK = 'docs.save'; //для использования в uploadOnServerVk
        }
        return $methodVK;
    }

    //конвертация видео без звука в гиф
    public function convertToGif(){
        $ffmpeg = FFMpeg::create();
        $path = '/var/www/html/parser/resources/src/converted_Gif_VK.gif';
        $video = $ffmpeg->open( '/var/www/html/parser/resources/src/gif_VK.gif' );
        $video
            ->gif(TimeCode::fromSeconds(0), new Dimension(640, 480))
            ->save($path);
        return $path;
    }



//https://api.vk.com/method/photos.getWallUploadServer? для картинки
//docs.getWallUploadServer для гиф
//docs.getUploadServer
    //получение ссылки для загрузки изображения
    public function getUploadUrl($methodVK){
        $request_params = array(
            'album_id' => $this->album_id,
            'group_id' => $this->group_id,
            'access_token' => $this->access_token_user,
            'v' => $this->v
        );
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
            $request_params = array(
                'group_id' => $this->group_id,
                'server' => $responseArr->server,
                'photo' => $responseArr->photo,
                'hash' => $responseArr->hash,
                'access_token' => $this->access_token_user,
                'v' => $this->v
            );
        }

        if ($methodVK === 'docs.save') {
            $request_params = [
                'file' => $responseArr->file,
//            'title' => 'test',
//            'tags' => 'testTag',
//            'return_tags' => 0,
                'owner_id' => $this->group_id,
                'access_token' => $this->access_token_user,
                'v' => $this->v
            ];
        }
        $get_params = http_build_query($request_params);

        //загрузка изображения на сервер вк
        $ch = curl_init( 'https://api.vk.com/method/' . $methodVK . '?' . $get_params);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);
        $response = curl_exec( $ch );
        curl_close( $ch );
        $responseArr = json_decode($response, true); //возвращается не обьект, а массив
        return $responseArr;
    }

      public function createPost ($responseArr){
        $dataFromBD = $this->selectBD();
        printr($dataFromBD);

        if (!empty($responseArr['response'][0]['id'])) {         //проверка какой тип файла был загружен
            $photo_id = $responseArr['response'][0]['id'];
            $owner_id = $responseArr['response'][0]['owner_id'];                //отрицательное значение для вк
            $request_params = array(
                'user_id' => $this->user_id,
                'owner_id' => -$this->group_id,
                'message' => $dataFromBD->header . PHP_EOL . PHP_EOL . $dataFromBD->Link_post,
                'attachments' => 'photo' . $owner_id . '_' . $photo_id,
                'v' => 5.101,
                'access_token' => "$this->access_token_user"
            );
        } else
            if (isset($responseArr['response']['doc']['id']) === true) {  //проверка какой тип файла был загружен
//                $doc_id = $responseArr->response->doc->id;
                $doc_id = $responseArr['response']['doc']['id'];
                $request_params = [
                    'user_id' => $this->user_id,
                    'owner_id' => -$this->group_id,
                    'message' => $dataFromBD->header . PHP_EOL . PHP_EOL . $dataFromBD->Link_post,
                    'attachments' => 'doc' . $owner_id. '_' . $doc_id,
                    'v' => 5.101,
                    'access_token' => "$this->access_token_user"
                ];
            }


        //непосредственно постинг в вк
        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/wall.post?' . $get_params));
        printr( $result);
    }
}
