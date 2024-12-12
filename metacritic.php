<?php

header('content-type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'libs/simple_html_dom.php';

class MetacriticAPI
{
    private $response_body = "";
    private $baseurl = "https://www.metacritic.com/game/";

    public function getmetacriticpage($game_name)
    {
        $returnvalue = "";

        /*formatage name*/
        $game_name = trim($game_name);
        $game_name = str_replace(' ', '-', $game_name);
        $game_name = str_replace('& ', '', $game_name);
        $game_name = strtolower($game_name);
        $game_name = preg_replace('/[^a-z\d\?!\-]/', '', $game_name);

        /* url */
        $url = 'https://www.metacritic.com/game/super-mario-galaxy-2/'; /* for test */
        $url = $this->baseurl . $game_name . "/";

        /* get page web */
        $ch = curl_init();
        curl_setopt($ch, curlopt_url, $url);
        curl_setopt($ch, curlopt_returntransfer, 1);
        curl_setopt($ch, curlopt_ssl_verifypeer, false);
        curl_setopt($ch, curlopt_ssl_verifyhost, 0);
        $output = curl_exec($ch);
        curl_close($ch);

        /* return */
        $returnvalue = $output;
        $this->response_body = $returnvalue;
    }

    public function getmetacriticscores()
    {
        # get dom by string content
        $html = str_get_html($this->response_body);

        # define json output array
        $json_output = array();
        $error = false;

        # init all vars
        $name = "";
        $metascritic_score = 0;
        $user_score = 0.0;
        $publisher = "";
        $developers = array();
        $release_date = "";
        $plateforms = array();
        $genres = array();

        if (!$html) {
            $json_output['error'] = "page could not be loaded!";
            $error = true;
        }

        if (!$error) {

            /* name */
            foreach ($html->find('div.c-producthero_title h1') as $element) {
                $name = trim($element->plaintext);
            }

            /* note */
            $j = 0;
            foreach ($html->find('div.c-productscoreinfo_scorenumber div.c-sitereviewscore span') as $element) {
                if ($j == 0) {
                    $metascritic_score = trim($element->plaintext);
                } else {
                    $user_score = trim($element->plaintext);
                }

                $j++;
            }

            /* editeur */
            $j = 0;
            foreach ($html->find('div.c-gamedetails_distributor span') as $element) {
                if ($j !== 0) {
                    $publisher = trim($element->plaintext);
                }
                $j++;
            }

            /* developpeur */
            foreach ($html->find('div.c-gamedetails_developer li.c-gamedetails_listitem') as $element) {
                $developers[] = trim($element->plaintext);
            }

           /* release date */
            $j=0;
            foreach ($html->find('div.c-gamedetails_releasedate span') as $element) {
                if ($j !== 0) {
                    $release_date = trim($element->plaintext);
                }
                $j++;
            }

            /* plateforme */
            foreach ($html->find('div.c-gamedetails_platforms li.c-gamedetails_listitem') as $element) {
                array_push($plateforms, trim($element->plaintext));
            }

            /* genre */
            foreach ($html->find('ul.c-genrelist li.c-genrelist_item div a span') as $element) {
                array_push($genres, trim($element->plaintext));
            }


            # prevent memory leak
            $html->clear();
            unset($html);

            # fill-in the array
            $json_output['name'] = $name;
            $json_output['metascritic_score'] = $metascritic_score;
            $json_output['users_score'] = $user_score;
            $json_output['publishers'] = $publisher;
            $json_output['developers'] = $developers;
            $json_output['release_date'] = $release_date;
            $json_output['plateforms'] = $plateforms;
            $json_output['genres'] = $genres;
        }


        # return json format
        return json_encode($json_output);
    }
}


if (isset($_get['game_title'])) {
    $metacritic_api = new MetacriticAPI();
    $metacritic_api->getmetacriticpage($_get['game_title']);
    echo $metacritic_api->getmetacriticscores();
} else {
    echo json_encode(array("error" => "game title is empty"));
}

