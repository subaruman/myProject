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

        $this->img = new \CURLFile( $this->downloadImg());
        $this->post_data = array('file1' => $this->img);
        $this->getUploadUrl();

        $responseArr = $this->uploadPostData();
        $this->uploadOnServerVK($responseArr);

    }

    //получение ссылки для загрузки изображения
    public function getUploadUrl(){
        $get_params = http_build_query($this->request_params);
        $result = file_get_contents('https://api.vk.com/method/photos.getWallUploadServer?' . $get_params);
        $resultArr = json_decode($result);
        $this->uploadUrl = $resultArr->response->upload_url;
        return $this->uploadUrl;
    }

    //скачивание картинки по юрл
    public function downloadImg(){
        //  $indexImg = 3; //индекс юрл картинки в бд
        $path = '';
        $dataFromBd = $this->selectBD();
        $url = $dataFromBd->Link_img;
        if (!empty($url)){
            $path = '/var/www/html/parser/resources/src/img_postVK.png';
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


    public function uploadOnServerVK($responseArr){
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
        $ch = curl_init( 'https://api.vk.com/method/photos.saveWallPhoto?' . $get_params);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);
        $response = curl_exec( $ch );
        curl_close( $ch );
        $responseArr = json_decode($response);

        $photo_id = $responseArr->response[0]->id;
        $owner_id = $responseArr->response[0]->owner_id;

        $dataFromBD = $this->selectBD();
        printr($dataFromBD);

        //непосредственно постинг в вк
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
        $get_params = http_build_query($request_params);
        $result = json_decode(file_get_contents('https://api.vk.com/method/wall.post?' . $get_params));
//        printr( $result);
    }
}
