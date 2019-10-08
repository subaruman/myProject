<?php


namespace App\Classes;
use Illuminate\Support\Facades\DB;

class SQL extends OpenPost
{
    public $maxID;
    public function __construct($header, $urlOpenPost, $text = null, $img = null,
                                $video = null, $audio = null, $gif = null, $gfycat = null)
    {
       $this->header = $header;
       $this->urlOpenPost = $urlOpenPost;
       $this->text = $text;
       $this->img = $img;
       $this->video = $video;
       $this->audio = $audio;
       $this->gif = $gif;
       $this->gfycat = $gfycat;
    }

    public function insertBD() {
        $this->maxID = DB::table('post')->max('id');
        $this->maxID++;
        DB::table('post')->insertOrIgnore(
                ['id' => $this->maxID,
                'header' => $this->header,
                'Link_post' => $this->urlOpenPost,
                'text' => $this->text,
                'Link_img' => $this->img,
                'Link_video' => $this->video,
                'Link_audio' => $this->audio,
                'Link_gif' => $this->gif,
                'Link_gfycat' => $this->gfycat]
        );

    }

    public function selectBD() {
        $this->maxID = DB::table('post')->max('id');
        $post = DB::select('SELECT * FROM post WHERE id = ?' , [$this->maxID]);
        return $post[0];
    }

}


