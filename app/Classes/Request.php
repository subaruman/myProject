<?php


namespace App\Classes;


use phpQuery;

class Request
{
    public $csrfToken;
    public $cookies;
    public $headers;

    public function __construct()
    {

    }


    public function authoriseRequest()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.122 Safari/537.36");
        curl_setopt($curl, CURLOPT_URL, 'https://www.reddit.com/login');
        $html = curl_exec($curl);

        //получение заголовков
        $headers = explode("\r\n", $html);
        $this->headers = array_slice($headers, 24, 23);

        //получение куков
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $html, $matches);
        $cookies = [];
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        //преобразование куков в строку из массива
        $keys = array_keys($cookies);
        $i = 0;
        foreach ($cookies as $item) {
            $this->cookies .= $keys[$i] . "=" . $item . "; ";
            $i++;
        }

        //получение csrf токена
        $html = phpQuery::newDocument($html);
        $this->csrfToken = $html->find("input")->filter("[name=csrf_token]")->val();
        $post = "csrf_token=" . $this->csrfToken . "&password=132lmc165cw&dest=https://www.reddit.com&username=antena7";

        //запрос авторизации
        curl_setopt($curl, CURLOPT_COOKIE, $this->cookies);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, 'https://www.reddit.com/login');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        $html = curl_exec($curl);

    }

    public function getTopPosts()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://gateway.reddit.com/desktopapi/v1/subreddits/Pikabu?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=&include=identity&sort=top&t=day&geo_filter=RU&layout=card');
        curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
//            $this->cookies,
            'authority: gateway.reddit.com',
            'method: GET',
            'path: /desktopapi/v1/subreddits/Pikabu?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=&include=prefsSubreddit&sort=hot&geo_filter=RU&layout=card',
            'scheme: https',
            'accept: application/json; q=1.0, */*; q=0.1',
            'accept-encoding: gzip, deflate, br',
            'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'cache-control: no-cache',
            'Content-Type: application/json; charset=UTF-8',
            'origin: https://www.reddit.com',
            'pragma: no-cache',
            'referer: https://www.reddit.com/',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-site',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.122 Safari/537.36',
            'x-reddaid: 5XZKFS4J66SPGLQA',
            'x-reddit-loid: 00000000005b95ibyi.2.1577450094443.Z0FBQUFBQmVOQ3B3UGtzekp1Y2pHWWYxSHZyeWJ2UENPaVFkTk5DZFNfcW1NRWR0cmdxZFBsOWF2VzJudUFRVVNqT3ZVVkdkVG5kc1VWeWliazJpWktxTmlOelRpbWpEY1pUamlkemI2clBSdXlyRFUwZ0YybUZOcG9jZ3lwRFhFc18xRGdqSnFWaXU',
            'x-reddit-session: 9uIxB9F69wNtRO82YR.0.1582885217015.Z0FBQUFBQmVXT2xoVnhPQUFpYUk0Q1RQMmRKdElZaFpkWTcxTFVpcEVhM2E2SWlKU1UzdTF0WXgxMER1SjJXX2lFUTRPMFhfbVEwcVFFU2htVFNGWlo0bGg2UG02YS0xczFCdmNQMW5HZWE0Tk1LbV9NQ2VidTQ0QjNXakdHSE1kWTRnTGlkZDhVNkI',
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response = json_decode($response, true);
    }

    public function getHotPosts()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://gateway.reddit.com/desktopapi/v1/subreddits/Pikabu?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=&include=identity&sort=hot&geo_filter=RU&layout=card');
        curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
//            $this->cookies,
            'path: /desktopapi/v1/subreddits/Pikabu?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=&include=identity&sort=hot&geo_filter=RU&layout=card',
            'method: GET',
            'scheme: https',
            'accept: application/json; q=1.0, */*; q=0.1',
            'accept-encoding: gzip, deflate, br',
            'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'cache-control: no-cache',
            'Content-Type: application/json; charset=UTF-8',
            'origin: https://www.reddit.com',
            'pragma: no-cache',
            'referer: https://www.reddit.com/',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-site',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.122 Safari/537.36',
            'x-reddaid: 5XZKFS4J66SPGLQA',
            'x-reddit-loid: 00000000005b95ibyi.2.1577450094443.Z0FBQUFBQmVOQ3B3UGtzekp1Y2pHWWYxSHZyeWJ2UENPaVFkTk5DZFNfcW1NRWR0cmdxZFBsOWF2VzJudUFRVVNqT3ZVVkdkVG5kc1VWeWliazJpWktxTmlOelRpbWpEY1pUamlkemI2clBSdXlyRFUwZ0YybUZOcG9jZ3lwRFhFc18xRGdqSnFWaXU',
            'x-reddit-session: 9uIxB9F69wNtRO82YR.0.1582885217015.Z0FBQUFBQmVXT2xoVnhPQUFpYUk0Q1RQMmRKdElZaFpkWTcxTFVpcEVhM2E2SWlKU1UzdTF0WXgxMER1SjJXX2lFUTRPMFhfbVEwcVFFU2htVFNGWlo0bGg2UG02YS0xczFCdmNQMW5HZWE0Tk1LbV9NQ2VidTQ0QjNXakdHSE1kWTRnTGlkZDhVNkI',
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response = json_decode($response, true);
    }
}