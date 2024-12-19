<?php

header('content-type: application/json');
header('Access-Control-Allow-Origin: *');
require "db.php";

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
        global $db;
        $html = str_get_html($this->response_body);

        # Define json output array
        $json_output = array();
        $error = false;

        # init all vars
        $name = "";
        $metacritic_score = 0;
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
                    $metacritic_score = trim($element->plaintext);
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
            $json_output['metacritic_score'] = $metacritic_score;
            $json_output['users_score'] = $user_score;
            $json_output['publishers'] = $publisher;
            $json_output['developers'] = $developers;
            $json_output['release_date'] = $release_date;
            $json_output['plateforms'] = $plateforms;
            $json_output['genres'] = $genres;
        }


// Insert game data into the database
        if (isset($json_output['name']) && !empty($json_output['name'])) {
            $insertQuery = $db->prepare("INSERT INTO metacritic (search_name, name, metacritic_score, users_score, publishers, developers, release_date, plateforms, genres) 
                VALUES (:search_name, :name, :metacritic_score, :users_score, :publishers, :developers, :release_date, :plateforms, :genres)");

            $developersJson = json_encode($json_output['developers']);
            $plateformsJson = json_encode($json_output['plateforms']);
            $genresJson = json_encode($json_output['genres']);

            $insertQuery->bindParam(':search_name', $_GET['game_title'], PDO::PARAM_STR);
            $insertQuery->bindParam(':name', $json_output['name'], PDO::PARAM_STR);
            $insertQuery->bindParam(':metacritic_score', $json_output['metacritic_score'], PDO::PARAM_INT);
            $insertQuery->bindParam(':users_score', $json_output['users_score'], PDO::PARAM_STR);
            $insertQuery->bindParam(':publishers', $json_output['publishers'], PDO::PARAM_STR);
            $insertQuery->bindParam(':developers', $developersJson, PDO::PARAM_STR);
            $insertQuery->bindParam(':release_date', $json_output['release_date'], PDO::PARAM_STR);
            $insertQuery->bindParam(':plateforms', $plateformsJson, PDO::PARAM_STR);
            $insertQuery->bindParam(':genres', $genresJson, PDO::PARAM_STR);

            $insertQuery->execute();
        }
        

        # Return JSON format
        return json_encode($json_output);
    }
}


if (isset($_GET['game_title'])) {

    $game_title = $_GET['game_title'];

    // Prepare and execute the PDO query
    $stmt = $db->prepare("SELECT * FROM metacritic WHERE search_name = :game_title");
    $stmt->bindParam(':game_title', $game_title, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);


    if (!empty($results[0])) {
        $selectedGame= $results[0];
        
        if (strtotime($selectedGame['added_at']) >= strtotime('-1 month')) {
            // Code à exécuter si ça a été ajouté il y a moins d'un mois
            $json_output['name'] = $selectedGame['name'];
            $json_output['metacritic_score'] = $selectedGame['metacritic_score'];
            $json_output['users_score'] = $selectedGame['users_score'];
            $json_output['publishers'] = $selectedGame['publishers'];
            $json_output['developers'] = json_decode($selectedGame['developers']);
            $json_output['release_date'] = $selectedGame['release_date'];
            $json_output['plateforms'] = json_decode($selectedGame['plateforms']);
            $json_output['genres'] = json_decode($selectedGame['genres']);

            echo json_encode($json_output);

        } else {

            $metacritic_api = new MetacriticAPI();
            $metacritic_api->getMetacriticPage($game_title);
            echo $metacritic_api->getMetacriticScores();

        }

    } else {

        $metacritic_api = new MetacriticAPI();
        $metacritic_api->getMetacriticPage($game_title);
        echo $metacritic_api->getMetacriticScores();

    }


} else {
    echo json_encode(array("error" => "Game title is empty"));
}

