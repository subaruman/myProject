<?php


namespace App\Classes;

use Illuminate\Support\Facades\DB;

class SQL extends OpenPost
{
    public $maxID;

    public function __construct()
    {

    }

    public function insertBD($header, $urlOpenPost, $text = null, $img = null,
                             $video = null, $audio = null, $silent_video = null, $gif = null, $gfycat = null,
                             $long_img = null)
    {

        $this->maxID = DB::table('post')->max('id');
        $this->maxID++;
        DB::table('post')->insertOrIgnore(
            [
                'id' => $this->maxID,
                'header' => $header,
                'Link_post' => $urlOpenPost,
                'text' => $text,
                'Link_img' => $img,
                'Link_video' => $video,
                'Link_audio' => $audio,
                'Link_silent_video' => $silent_video,
                'Link_gif' => $gif,
                'Link_gfycat' => $gfycat,
                'Link_long_img' => $long_img,
            ]
        );

    }

    public function selectBD()
    {
        $this->maxID = DB::table('post')->max('id');
        $post = DB::select('SELECT * FROM post WHERE id = ?', [$this->maxID]);
        return $post[0];
    }

    public function updateBD($id, $status)
    {
        DB::table('post')->where('id', $id)
            ->update([
                'was_posted' => $status
            ]);
    }

    public function checkLinkBD($link)
    {
       $response = DB::table('post')->where('Link_post', "=", $link)->get();
       if (!empty($response[0])) {
           return true;
       }
       return false;
    }

}


