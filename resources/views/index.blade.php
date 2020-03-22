<?php

namespace App\Classes;


//top/?t=day


set_time_limit(10000);

for ($i = 0; $i < 2; $i++) { //кол-во постов для парсинга, больше 8 не работает, т.к. лента не прогружает
    $parser = new Parser("https://www.reddit.com/r/Pikabu/hot", $i);
    echo "<br>" . $parser->headerPost();
    echo "<br>" . $parser->urlPost();
    if ($parser->urlOpenPost === "https://www.reddit.com") {
        continue;
    }
//    https://www.reddit.com/r/Pikabu/comments/d80wf6/
//    https://www.reddit.com/r/Pikabu/comments/ffanfe/ imgur gifv
//    https://www.reddit.com/r/Pikabu/comments/fkgluz/ imgur jpg
    $openpost = new OpenPost($parser->urlOpenPost, $i);
    echo "<br>" . $openpost->textPost();
    echo "<br>" . $openpost->imgPost();
    echo "<br>" . $openpost->gfycatPost();
    echo "<br>" . $openpost->gifPost();
    echo "<br>" . $openpost->videoPost();
    echo "<br>" . $openpost->imgurPost();

    $query = new SQL($parser->header, $parser->urlOpenPost, $openpost->text, $openpost->img, $openpost->video, $openpost->audio, $openpost->imgur, $openpost->gif,
        $openpost->gfycat);
    $query->insertBD();

    $postVK = new PostingVK();

    echo "<br>" . "__________________________________________________";
}


?>

