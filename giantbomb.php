<?php

header('content-type: application/json');
header('Access-Control-Allow-Origin: *');
require "db.php";

require_once 'libs/simple_html_dom.php';

class GiantBombAPI
{
    private $response_body = "";
    private $baseUrl = "https://www.giantbomb.com/";

    public function getGiantBombPage($game_name, $guid)
    {
        $returnValue = "";

        /*FORMATAGE NAME*/
        $game_name = trim($game_name);
        $game_name = str_replace(' ', '-', $game_name);
        $game_name = str_replace('& ', '', $game_name);
        $game_name = strtolower($game_name);
        $game_name = preg_replace('/[^a-z\d\?!\-]/', '', $game_name);

        /* URL */
        $url = 'https://www.giantbomb.com/super-mario-galaxy/3030-16094/'; /* FOR TEST */
        $url = $this->baseUrl . $game_name . "/" . $guid;

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

    public function getGiantBombScores()
    {
        # Get DOM by string content
        global $db;
        $html = str_get_html($this->response_body);

        # Define json output array
        $json_output = array();
        $error = false;

        # init all vars
        $name = "";
        $release_date = "";
        $average_score = "";
        $detail = [];
        $picture = [];

        if (!$html) {
            $json_output['error'] = "Page could not be loaded!";
            $error = true;
        }

        if (!$error) {

            /* NAME */
            foreach ($html->find('h1.entry-title a.wiki-title') as $element) {
                $name = trim($element->plaintext);
            }

            /* REALEASE */
            foreach ($html->find('p.wiki-descriptor') as $element) {
                $last_release_date = trim(substr($element->plaintext, strpos($element->plaintext, 'Released') + strlen('Released')));
                $release_date = trim(substr($last_release_date, 0, 18));
            }

            /* AVERAGHE */
            foreach ($html->find('span.average-score') as $element) {
                $average_score = trim(str_replace('stars', '', $element->plaintext));
            }

            /* DETAILS */
            foreach ($html->find('div.wiki-details table tbody tr') as $element) {
                if (!empty($element->nodes[1])){
//                    var_dump(strtolower(trim($element->nodes[1]->plaintext)));

                    /* Name*/
                    if (strtolower(trim($element->nodes[1]->plaintext)) == 'name'){
                        $elementName = $element->find('td', 0);
                        $detail["name"] = [
                            'name' => trim($elementName->find('a', 0)->plaintext),
                            'url' => trim($elementName->find('a', 0)->href)
                        ];
                    }

                    /* FIRST_REALEASE */
                    if (strtolower(trim($element->nodes[1]->plaintext)) == 'first release date'){
                        $elementFirstReleaseDate = $element->find('td', 0);
                        $detail["firs_release_date"] = trim($elementFirstReleaseDate->find('span', 0)->plaintext);
                    }

                    /* PLATEFORM */
                    if (strtolower(trim($element->nodes[1]->plaintext)) == 'platform'){
                        $elementSub = $element->find('td a');
                        foreach ($elementSub as $oneElement) {
                            $elementName = trim($oneElement->plaintext);
                            $elementUrl = trim($oneElement->href);
                            $detail['platform'][] = [
                                'name' => $elementName,
                                'url' => $elementUrl
                            ];
                        };
                    }

                    /* developer */
                    if (strtolower(trim($element->nodes[1]->plaintext)) == 'developer'){
                        $elementSub = $element->find('td a');
                        foreach ($elementSub as $oneElement) {
                            $elementName = trim($oneElement->plaintext);
                            $elementUrl = trim($oneElement->href);
                            $detail['developer'][] = [
                                'name' => $elementName,
                                'url' => $elementUrl
                            ];
                        };
                    }

                    /* publisher */
                    if (strtolower(trim($element->nodes[1]->plaintext)) == 'publisher'){
                        $elementSub = $element->find('td a');
                        foreach ($elementSub as $oneElement) {
                            $elementName = trim($oneElement->plaintext);
                            $elementUrl = trim($oneElement->href);
                            $detail['publisher'][] = [
                                'name' => $elementName,
                                'url' => $elementUrl
                            ];
                        };
                    }

                    /* genre */
                    if (strtolower(trim($element->nodes[1]->plaintext)) == 'genre'){
                        $elementSub = $element->find('td a');
                        foreach ($elementSub as $oneElement) {
                            $elementName = trim($oneElement->plaintext);
                            $elementUrl = trim($oneElement->href);
                            $detail['genre'][] = [
                                'name' => $elementName,
                                'url' => $elementUrl
                            ];
                        };
                    }

                    /* theme */
                    if (strtolower(trim($element->nodes[1]->plaintext)) == 'theme'){
                        $elementSub = $element->find('td a');
                        foreach ($elementSub as $oneElement) {
                            $elementName = trim($oneElement->plaintext);
                            $elementUrl = trim($oneElement->href);
                            $detail['theme'][] = [
                                'name' => $elementName,
                                'url' => $elementUrl
                            ];
                        };
                    }

                    /* franchises */
                    if (strtolower(trim($element->nodes[1]->plaintext)) == 'franchises'){
                        $elementSub = $element->find('td a');
                        foreach ($elementSub as $oneElement) {
                            $elementName = trim($oneElement->plaintext);
                            $elementUrl = trim($oneElement->href);
                            $detail['franchises'][] = [
                                'name' => $elementName,
                                'url' => $elementUrl
                            ];
                        };
                    }


                }

            }

            /* PICTURE BOXART */
            foreach ($html->find('.wiki-boxart img') as $element) {
                if (strpos($element->src, 'data:') !== 0 && $element->src != "https://www.giantbomb.com/a/bundles/phoenixsite/images/core/loose/img_broken.png" && $element->src != ""){
                    $picture[] = $element->src;
                }
            }

            /* PICTURE BANNER */
            foreach ($html->find('.kubrick-strip') as $element) {
                preg_match('/url\((.*?)\)/', $element->style, $matches);
                if (isset($matches[1]) && !empty($matches[1])) {
                    if ($matches[1] != "https://www.giantbomb.com/a/bundles/phoenixsite/images/core/loose/bg-default-wiki.png"){
                        $picture[] = $matches[1];
                    }
                }
            }

            /* PICTURE IN WIKI */
            foreach ($html->find('.primary-content img') as $element) {
                if (strpos($element->src, 'data:') !== 0 && $element->src != "https://www.giantbomb.com/a/bundles/phoenixsite/images/core/loose/img_broken.png" && $element->src != ""){
                    $picture[] = $element->src;
                }
            }

            /* PICTURE IN LATEST IMAGES */
            foreach ($html->find('.gallery-box-pod figure a') as $element) {
                if (strpos($element->href, 'data:') !== 0 && $element->href != "https://www.giantbomb.com/a/bundles/phoenixsite/images/core/loose/img_broken.png" && $element->href != ""){
                    $picture[] = $element->href;
                }
            }






            # Prevent memory leak
            $html->clear();
            unset($html);


            # Fill-in the array
            $json_output['name'] = $name;
            $json_output['release_date'] = $release_date;
            $json_output['average_score'] = $average_score;
            $json_output['detail'] = $detail;
            $json_output['picture'] = $picture;
        }


        if (isset($json_output['name']) && !empty($json_output['name'])) {
            $insertQuery = $db->prepare("INSERT INTO gamenium_giantbomb (search_guid, name, release_date, average_score, detail, picture) 
                VALUES (:search_guid, :name, :release_date, :average_score, :detail, :picture)");

            $detailJson = json_encode($json_output['detail']);
            $pictureJson = json_encode($json_output['picture']);

            $insertQuery->bindParam(':search_guid', $_GET['guid']);
            $insertQuery->bindParam(':name', $json_output['name']);
            $insertQuery->bindParam(':release_date', $json_output['release_date']);
            $insertQuery->bindParam(':average_score', $json_output['average_score']);
            $insertQuery->bindParam(':detail', $detailJson);
            $insertQuery->bindParam(':picture', $pictureJson);

            $insertQuery->execute();
        }



        # Return JSON format
        return json_encode($json_output);
    }
}


if (isset($_GET['game_title']) && isset($_GET['guid'])) {

    $game_guid = $_GET['guid'];

    // Prepare and execute the PDO query
    $stmt = $db->prepare("SELECT * FROM gamenium_giantbomb WHERE search_guid = :game_guid");
    $stmt->bindParam(':game_guid', $game_guid);
    $stmt->execute();

    // Fetch results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);


    if (!empty($results[0])) {
        $selectedGame = $results[0];

        if (strtotime($selectedGame['added_at']) >= strtotime('-1 month')) {
            // Code à exécuter si ça a été ajouté il y a moins d'un mois
            $json_output['name'] = $selectedGame['name'];
            $json_output['release_date'] = $selectedGame['release_date'];
            $json_output['average_score'] = $selectedGame['average_score'];
            $json_output['detail'] = json_decode($selectedGame['detail']);
            $json_output['picture'] = json_decode($selectedGame['picture']);
            echo json_encode($json_output);

        } else {

            $giantbomb_api = new GiantBombAPI();
            $giantbomb_api->getGiantBombPage($_GET['game_title'], $_GET['guid']);
            echo $giantbomb_api->getGiantBombScores();
        }

    } else {

        $giantbomb_api = new GiantBombAPI();
        $giantbomb_api->getGiantBombPage($_GET['game_title'], $_GET['guid']);
        echo $giantbomb_api->getGiantBombScores();

    }

} else {
    echo json_encode(array("error" => "Game title is empty"));
}

