<?php


namespace App\Classes;


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

    public $access_token_user;
    public $access_token_server;

    public function __construct()
    {
        $this->access_token_user = env('VK_TOKEN_USER', false);
        $this->access_token_server = env('VK_TOKEN_SERVER', false);
        $this->request_params = array(
            'album_id' => $this->album_id,
            'group_id' => $this->group_id,
            'access_token' => $this->access_token_server,
            'v' => $this->v);

        $this->img = new \CURLFile( $this->downloadMedia());
        $this->post_data = array('file1' => $this->img);
        $this->getUploadUrl("docs.getWallUploadServer");

        $responseArr = $this->uploadPostData();
        printr($responseArr);
//        $responseArr = $this->uploadOnServerVK($responseArr, 'docs.save');

    }

//https://api.vk.com/method/photos.getWallUploadServer? для картинки
//docs.getWallUploadServer для гиф
    //получение ссылки для загрузки изображения
    public function getUploadUrl($methodVK){
        $get_params = http_build_query($this->request_params);
        $result = file_get_contents('https://api.vk.com/method/' . $methodVK . "?" . $get_params);
        $resultArr = json_decode($result);
//        $this->uploadUrl = $resultArr->response->upload_url;
//        return $this->uploadUrl;
        printr($resultArr);
    }

    //скачивание картинки по юрл
    public function downloadMedia(){
      //  $indexImg = 3; //индекс юрл картинки в бд
        $path = '';
        $dataFromBd = $this->selectBD();
        if (!empty($dataFromBd->Link_img)){
            $url = $dataFromBd->Link_img;
            $path = '/var/www/html/parser/resources/src/media_postVK.png';
            file_put_contents($path, file_get_contents($url));
        } else if(!empty($dataFromBd->Link_gif)){
            $url = $dataFromBd->Link_gif;
            $path = '/var/www/html/parser/resources/src/media_postVK.gif';
            file_put_contents($path, file_get_contents($url));
        }

        return $path;
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
        $request_params = array(
            'group_id' => $this->group_id,
            'server' => $responseArr->server,
            'hash' => $responseArr->hash,
            'photo' => $responseArr->photo,
            'access_token' => $this->access_token_server,
            'v' => $this->v
        );

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
        $responseArr = json_decode($response);
        return $responseArr;
    }

    /*
    public function createPost ($responseArr){



        $photo_id = $responseArr->response[0]->id;
        $owner_id = $responseArr->response[0]->owner_id;

        $dataFromBD = $this->selectBD();
        printr($dataFromBD);

        //непосредственно постинг в вк
        if (!empty($photo_id)){
            $request_params = array(
                'user_id' => $this->user_id,
                'owner_id' => -$this->group_id,
                'message' => $dataFromBD->header . PHP_EOL . PHP_EOL . $dataFromBD->Link_post
                    . PHP_EOL . $dataFromBD->text
//                . PHP_EOL . $dataFromBD->Link_img
                    . PHP_EOL . $dataFromBD->Link_video,
                'attachments' => 'photo' . $owner_id. '_' . $photo_id,
                'v' => 5.101,
                'access_token' => "$this->access_token_user"
            );
        }

        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/wall.post?' . $get_params));
//        printr( $result);
    }*/
}
