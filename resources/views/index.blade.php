<?php

namespace App\Classes;

set_time_limit(10000);

$request = new Request();
$request->authoriseRequest();
$response = $request->getPosts();


foreach ($response["posts"] as $post) {

    $pikabu = substr($post["permalink"], 25, 6);
    if ($pikabu !== "Pikabu") {
        continue;
    }

$openpost = new OpenPost($post["permalink"], $request->cookies, $request->headers);
echo "<br>" . $openpost->textPost();
echo "<br>" . $openpost->imgPost();
echo "<br>" . $openpost->gfycatPost();
echo "<br>" . $openpost->gifPost();
echo "<br>" . $openpost->videoPost();

$query = new SQL($post["title"], $post["permalink"], $openpost->text, $openpost->img, $openpost->video, $openpost->audio,
    $openpost->silent_video, $openpost->gif, $openpost->gfycat);
$query->insertBD();

$postVK = new PostingVK();

echo "<br>" . "__________________________________________________";
}


?>

