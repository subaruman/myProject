<?php

namespace App\Console;

use App\Classes\OpenPost;
use App\Classes\Parser;
use App\Classes\PostingVK;
use App\Classes\SQL;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();

        $schedule->call(function (){
            set_time_limit(10000);
            for ($i = 0; $i < 8; $i++){ //кол-во постов для парсинга, больше 8 не работает, т.к. лента не прогружает
                $parser = new Parser("https://www.reddit.com/r/Pikabu/hot", $i);
                echo "<br>" . $parser->headerPost();
                echo "<br>" . $parser->urlPost();
                //    https://www.reddit.com/r/Pikabu/comments/d80wf6/
                $openpost = new OpenPost($parser->urlOpenPost, $i);
                echo "<br>" . $openpost->textPost();
                echo "<br>" . $openpost->imgPost();
                echo "<br>" . $openpost->gfycatPost();
                echo "<br>" . $openpost->gifPost();
                echo "<br>" . $openpost->videoPost();

                $query = new SQL($parser->header, $parser->urlOpenPost, $openpost->text, $openpost->img, $openpost->video, $openpost->audio, $openpost->gif,
                    $openpost->gfycat);
                $query->insertBD();

                $postVK = new PostingVK();
                echo "<br>" . "__________________________________________________";
            }

        })->everyThirtyMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
