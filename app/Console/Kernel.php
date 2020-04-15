<?php

namespace App\Console;

use App\Classes\OpenPost;
use App\Classes\Parser;
use App\Classes\PostingVK;
use App\Classes\Request;
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
            $request = new Request();
            $request->authoriseRequest();
            $response = $request->getHotPosts();

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

        })->hourly();
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
