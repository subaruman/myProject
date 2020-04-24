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

//$post["permalink"] = "https://www.reddit.com/r/Pikabu/comments/g6u8b5/%D0%B0%D0%B4%D0%B0%D0%BF%D1%82%D0%B0%D1%86%D0%B8%D1%8F/";
//$post["title"] = "test";

$openpost = new OpenPost($post["permalink"], $request->cookies, $request->headers);
echo "<br>" . $openpost->textPost();
echo "<br>" . $openpost->imgPost();
echo "<br>" . $openpost->gfycatPost();
echo "<br>" . $openpost->gifPost();
echo "<br>" . $openpost->videoPost();

$query = new SQL($post["title"], $post["permalink"], $openpost->text, $openpost->img, $openpost->video, $openpost->audio,
    $openpost->silent_video, $openpost->gif, $openpost->gfycat, $openpost->long_img);
$query->insertBD();

$postVK = new PostingVK();

echo "<br>" . "__________________________________________________";
}


?>

