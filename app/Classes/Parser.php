<?php


namespace App\Classes;

use PhpQuery\PhpQuery;


class Parser
{
    public $urlForParse;
    public $numberPost;

    public $contentOpenPage;

    public $urlOpenPost;
    public $header;

    protected $opts;
    protected $context;

    const HEADER = "._2SdHzo12ISmrC8H86TgSCp._3wqmjmv3tb_k-PROt7qFZe";
    const URL = "._2FCtq-QzlfuN-SwVMUZMM3._3wiKjmhpIpoTE2r5KCm2o6";
    const URL2 = ".y8HYJ-y_lTUHkQIc1mdCq._2INHSNB8V5eaWp4P0rY_mE";


    public function __construct($url, $numberPost) {
        $this->opts = [
            'https'=>
            [
                'header' => "User-Agent:Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.75 Safari/537.1\r\n"
            ]
        ];
        $this->context = stream_context_create($this->opts);
        $this->urlForParse = $url;
        $this->numberPost = $numberPost;

        $file = file_get_contents($this->urlForParse,false, $this->context);
        $this->contentOpenPage = phpQuery::newDocument($file);
    }

    public function headerPost() {
        $this->header = $this->contentOpenPage->find(Parser::HEADER . ":eq($this->numberPost)")->find('h3')->text();
        return $this->header;
    }

    public function urlPost() {

        $href = $this->contentOpenPage->find(Parser::URL . ":eq($this->numberPost)")->find
        (Parser::URL2)->find('a')->attr('href');
        $href = substr($href, 0, 26);
        $href = "https://www.reddit.com" . $href;
        $this->urlOpenPost = $href;
        return $href;
    }
}



