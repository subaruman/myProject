<?php

namespace App\Classes;
//top/?t=day

set_time_limit(1000);

for ($i = 0; $i < 1; $i++){ //кол-во постов для парсинга, больше 8 не работает, т.к. лента не прогружает
    $parser = new Parser("https://www.reddit.com/r/Pikabu/top/?t=day", $i);
    echo "<br>" . $parser->headerPost();
    echo "<br>" . $parser->urlPost();

    $openpost = new OpenPost("https://www.reddit.com/r/Pikabu/comments/d916r9/", $i);
    echo "<br>" . $openpost->textPost();
    echo "<br>" . $openpost->imgPost();
    echo "<br>" . $openpost->gfycatPost();
    echo "<br>" . $openpost->gifPost();
    echo "<br>" . $openpost->videoPost();

    $query = new SQL($parser->header, $parser->urlOpenPost, $openpost->text, $openpost->img, $openpost->video, $openpost->gif,
        $openpost->gfycat);
    $query->insertBD();


    echo "<br>" . "__________________________________________________";
}
    $postVK = new PostingVK();




?>




