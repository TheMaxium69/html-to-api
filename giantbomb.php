<?php

require_once 'libs/simple_html_dom.php';

class GiantBombAPI
{
    private $response_body = "";
    private $baseUrl = "https://www.giantbomb.com/";

    public function getGiantBombPage($game_name, $gui)
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
        $url = $this->baseUrl . $game_name . "/" . $gui;

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
        $html = str_get_html($this->response_body);

        # Define json output array
        $json_output = array();
        $error = false;

        # init all vars
        $name = "";

        if (!$html) {
            $json_output['error'] = "Page could not be loaded!";
            $error = true;
        }

        if (!$error) {

            /* NAME */
            foreach ($html->find('h1.entry-title a.wiki-title') as $element) {
                $name = trim($element->plaintext);
            }

            # Prevent memory leak
            $html->clear();
            unset($html);

            # Fill-in the array
            $json_output['name'] = $name;
        }


        # Return JSON format
        header('Content-Type: application/json');
        return json_encode($json_output);
    }
}


if (isset($_GET['game_title']) && isset($_GET['gui'])) {
    $giantbomb_api = new GiantBombAPI();
    $giantbomb_api->getGiantBombPage($_GET['game_title'], $_GET['gui']);
    echo $giantbomb_api->getGiantBombScores();
} else {
    echo json_encode(array("error" => "Game title is empty"));
}

