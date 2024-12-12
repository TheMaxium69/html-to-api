<?php

header('content-type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'libs/simple_html_dom.php';

class MetacriticAPI
{
    private $response_body = "";
    private $baseUrl = "https://www.metacritic.com/game/";

    public function getMetacriticPage($game_name)
    {
        $returnValue = "";

        /*FORMATAGE NAME*/
        $game_name = trim($game_name);
        $game_name = str_replace(' ', '-', $game_name);
        $game_name = str_replace('& ', '', $game_name);
        $game_name = strtolower($game_name);
        $game_name = preg_replace('/[^a-z\d\?!\-]/', '', $game_name);

        /* URL */
        $url = 'https://www.metacritic.com/game/super-mario-galaxy-2/'; /* FOR TEST */
        $url = $this->baseUrl . $game_name . "/";

        /* GET PAGE WEB */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $output = curl_exec($ch);
        curl_close($ch);

        /* RETURN */
        $returnValue = $output;
        $this->response_body = $returnValue;
    }

    public function getMetacriticScores()
    {
        # Get DOM by string content
        $html = str_get_html($this->response_body);

        # Define json output array
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
            $json_output['error'] = "Page could not be loaded!";
            $error = true;
        }

        if (!$error) {

            /* NAME */
            foreach ($html->find('div.c-productHero_title h1') as $element) {
                $name = trim($element->plaintext);
            }

            /* NOTE */
            $j = 0;
            foreach ($html->find('div.c-productScoreInfo_scoreNumber div.c-siteReviewScore span') as $element) {
                if ($j == 0) {
                    $metascritic_score = trim($element->plaintext);
                } else {
                    $user_score = trim($element->plaintext);
                }

                $j++;
            }

            /* EDITEUR */
            $j = 0;
            foreach ($html->find('div.c-gameDetails_Distributor span') as $element) {
                if ($j !== 0) {
                    $publisher = trim($element->plaintext);
                }
                $j++;
            }

            /* DEVELOPPEUR */
            foreach ($html->find('div.c-gameDetails_Developer li.c-gameDetails_listItem') as $element) {
                $developers[] = trim($element->plaintext);
            }

            /* RELEASE DATE */
            $j=0;
            foreach ($html->find('div.c-gameDetails_ReleaseDate span') as $element) {
                if ($j !== 0) {
                    $release_date = trim($element->plaintext);
                }
                $j++;
            }

            /* PLATEFORME */
            foreach ($html->find('div.c-gameDetails_Platforms li.c-gameDetails_listItem') as $element) {
                array_push($plateforms, trim($element->plaintext));
            }

            /* GENRE */
            foreach ($html->find('ul.c-genreList li.c-genreList_item div a span') as $element) {
                array_push($genres, trim($element->plaintext));
            }


            # Prevent memory leak
            $html->clear();
            unset($html);

            # Fill-in the array
            $json_output['name'] = $name;
            $json_output['metascritic_score'] = $metascritic_score;
            $json_output['users_score'] = $user_score;
            $json_output['publishers'] = $publisher;
            $json_output['developers'] = $developers;
            $json_output['release_date'] = $release_date;
            $json_output['plateforms'] = $plateforms;
            $json_output['genres'] = $genres;
        }


        # Return JSON format
        return json_encode($json_output);
    }
}


if (isset($_GET['game_title'])) {
    $metacritic_api = new MetacriticAPI();
    $metacritic_api->getMetacriticPage($_GET['game_title']);
    echo $metacritic_api->getMetacriticScores();
} else {
    echo json_encode(array("error" => "Game title is empty"));
}

