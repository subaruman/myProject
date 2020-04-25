<?php

namespace App\Classes;

set_time_limit(10000);

$request = new Request();
$request->authoriseRequest();
$response = $request->getHotPosts();

foreach ($response["posts"] as $post) {

    $pikabu = substr($post["permalink"], 25, 6);
    if ($pikabu !== "Pikabu") {
        continue;
    }

    $query = new SQL();
    if ($query->checkLinkBD($post["permalink"]) === true) {
        continue;
    }

//$post["permalink"] = "https://www.reddit.com/r/Pikabu/comments/g7858t/%D1%81%D0%B5%D0%B3%D0%BE%D0%B4%D0%BD%D1%8F_%D1%81%D0%BB%D1%83%D1%88%D0%B0%D0%B5%D1%88%D1%8C_%D1%82%D1%8B_%D0%B4%D0%B6%D0%B0%D0%B7/";
//$post["title"] = "test";

$openpost = new OpenPost($post["permalink"], $request->cookies, $request->headers);
echo "<br>" . $openpost->textPost();
echo "<br>" . $openpost->imgPost();
echo "<br>" . $openpost->gfycatPost();
echo "<br>" . $openpost->gifPost();
echo "<br>" . $openpost->videoPost();

$query->insertBD($post["title"], $post["permalink"], $openpost->text, $openpost->img, $openpost->video, $openpost->audio,
    $openpost->silent_video, $openpost->gif, $openpost->gfycat, $openpost->long_img);

$postVK = new PostingVK();

echo "<br>" . "__________________________________________________";
}


?>

