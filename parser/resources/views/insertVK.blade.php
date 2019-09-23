<?php


$vkAppId = 7081424;
$user_id = 464383830;
$album_id = 249770511;
$group_id = 159140427;
$v = 5.101;

$img = new \CURLFile( downloadImg());
$post_data = array('file1' => $img);

$access_token_user = '287dfb6e58f71708e9c4f91842acc5394edc3a9d6e97fd6e5dd51e3c2f0165f6baf023dafceeae0108d5c';
$access_token_server = 'b833d8a3d3e7133eb176c8ac695147583cbe5d64d355f4c896da3c2c233c2a9622527fb0d62af60f253ec';


$request_params = array(
    'album_id' => $album_id,
    'group_id' => $group_id,
    'access_token' => $access_token_server,
    'v' => $v
);

//получение ссылки для загрузки изображения
$get_params = http_build_query($request_params);
$result = file_get_contents('https://api.vk.com/method/photos.getWallUploadServer?' . $get_params);
$resultArr = json_decode($result);
$url = $resultArr->response->upload_url;
//printr($url);
//[upload_url]https://pu.vk.com/c854524/upload.php?act=do_add&mid=464383830&aid=249770511&gid=159140427&hash=7ea99c7067d1968ebcfbe62ed43e1f0b&rhash=89ef820e32425214d5fe631003541fc1&swfupload=1&api=1

//post запрос
$ch = curl_init( $resultArr->response->upload_url );
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: multipart/form-data"] );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
$response = curl_exec( $ch );
curl_close( $ch );
$responseArr = json_decode($response);





$request_params = array(
    'group_id' => $group_id,
    'server' => $responseArr->server,
    'hash' => $responseArr->hash,
    'photo' => $responseArr->photo,
    'access_token' => $access_token_server,
    'v' => $v
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





$dataFromBD = selectBD();
printr($dataFromBD);


//непосредственно постинг в вк
$request_params = array(
    'user_id' => $user_id,
    'owner_id' => -$group_id,
    'message' => $dataFromBD[0] . PHP_EOL . PHP_EOL . $dataFromBD[1]
        . PHP_EOL . $dataFromBD[2]
        . PHP_EOL . $dataFromBD[4]
        . PHP_EOL . $dataFromBD[5],
    'attachments' => 'photo' . $owner_id. '_' . $photo_id,
    'v' => 5.101,
    'access_token' => "$access_token_user"
);
$get_params = http_build_query($request_params);
$result = json_decode(file_get_contents('https://api.vk.com/method/wall.post?' . $get_params));
printr( $result);






?>
