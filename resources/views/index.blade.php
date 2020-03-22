<?php

namespace App\Classes;


//top/?t=day


set_time_limit(10000);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'https://gateway.reddit.com/desktopapi/v1/subreddits/Pikabu?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=&include=identity&sort=top&t=day&geo_filter=RU&layout=card');
curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
curl_setopt($curl, CURLOPT_ENCODING, "");
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'cookie: edgebucket=E7tjLaGS2pT7Y8GNiA; loid=00000000005lr4230u.2.1580842138405.Z0FBQUFBQmVPYnlhQVVZYUpnM0ZJVHZscDhUTzFSRFpseHJyLUlFSTJQSTN5NTZadGhaQldDM1Bid0tRWVZCNm1za2xfZ0J4NmJoNERyY2ZFaEk0dmo4UE9taHBrYjhGQlc0SU01ZUZNUVJ6aVlQZjFKdUJaVTUwWjVhQ0YzeEh4ZDcxRExENkQzbEU; d2_token=3.aa6e00f68e5be9c2a98d797305795c41cbcd071986a1cd2a6540f2144ce278fa.eyJhY2Nlc3NUb2tlbiI6Ii04WElXS0lHclQ0dnh0eTIyOUtxQVlkdTlsRUEiLCJleHBpcmVzIjoiMjAyMC0wMi0wNFQxOTo0ODo1OC4wMDBaIiwibG9nZ2VkT3V0Ijp0cnVlLCJzY29wZXMiOlsiKiIsImVtYWlsIl19',
    'authority: gateway.reddit.com',
    'method: GET',
    'path: /desktopapi/v1/subreddits/Pikabu?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=&include=prefsSubreddit&sort=hot&geo_filter=RU&layout=card',
    'scheme: https',
    'accept: application/json; q=1.0, */*; q=0.1',
    'accept-encoding: gzip, deflate, br',
    'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
    'cache-control: no-cache',
    'Content-Type: application/json; charset=UTF-8',
    'origin: https://www.reddit.com',
    'pragma: no-cache',
    'referer: https://www.reddit.com/',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-site',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.122 Safari/537.36',
    'x-reddaid: 5XZKFS4J66SPGLQA',
    'x-reddit-loid: 00000000005b95ibyi.2.1577450094443.Z0FBQUFBQmVOQ3B3UGtzekp1Y2pHWWYxSHZyeWJ2UENPaVFkTk5DZFNfcW1NRWR0cmdxZFBsOWF2VzJudUFRVVNqT3ZVVkdkVG5kc1VWeWliazJpWktxTmlOelRpbWpEY1pUamlkemI2clBSdXlyRFUwZ0YybUZOcG9jZ3lwRFhFc18xRGdqSnFWaXU',
    'x-reddit-session: 9uIxB9F69wNtRO82YR.0.1582885217015.Z0FBQUFBQmVXT2xoVnhPQUFpYUk0Q1RQMmRKdElZaFpkWTcxTFVpcEVhM2E2SWlKU1UzdTF0WXgxMER1SjJXX2lFUTRPMFhfbVEwcVFFU2htVFNGWlo0bGg2UG02YS0xczFCdmNQMW5HZWE0Tk1LbV9NQ2VidTQ0QjNXakdHSE1kWTRnTGlkZDhVNkI',
]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
$response = curl_exec($curl);
curl_close($curl);

$response = json_decode($response, true);

foreach ($response["posts"] as $post) {
//    printr($post["title"] . "<br>");
//    printr($post["permalink"]);
    $pikabu = substr($post["permalink"], 25, 6);
    if ($pikabu !== "Pikabu") {
        continue;
    }
    $url = substr($post["permalink"], 0, 48);
//    printr("__________________");


//for ($i = 0; $i < 8; $i++) { //кол-во постов для парсинга, больше 8 не работает, т.к. лента не прогружает
//    $parser = new Parser("https://www.reddit.com/r/Pikabu/top/?t=day", $i);
//    echo "<br>" . $parser->headerPost();
//    echo "<br>" . $parser->urlPost();
//    if ($parser->urlOpenPost === "https://www.reddit.com") {
//        continue;
//    }
//    https://www.reddit.com/r/Pikabu/comments/d80wf6/
//    https://www.reddit.com/r/Pikabu/comments/ffanfe/ imgur gifv
//    https://www.reddit.com/r/Pikabu/comments/fkgluz/ imgur jpg
//    $openpost = new OpenPost($parser->urlOpenPost, $i);
    $openpost = new OpenPost($url);
    echo "<br>" . $openpost->textPost();
    echo "<br>" . $openpost->imgPost();
    echo "<br>" . $openpost->gfycatPost();
    echo "<br>" . $openpost->gifPost();
    echo "<br>" . $openpost->videoPost();
    echo "<br>" . $openpost->imgurPost();

//    $query = new SQL($parser->header, $parser->urlOpenPost, $openpost->text, $openpost->img, $openpost->video, $openpost->audio, $openpost->imgur, $openpost->gif,
//        $openpost->gfycat);
        $query = new SQL($post["title"], $url, $openpost->text, $openpost->img, $openpost->video, $openpost->audio, $openpost->imgur, $openpost->gif,
        $openpost->gfycat);
    $query->insertBD();

    $postVK = new PostingVK();

    echo "<br>" . "__________________________________________________";
}


?>

