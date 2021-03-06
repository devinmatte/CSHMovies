<?php

require 'keys.php';
include 'tmdb-api.php';

$tmdb = new TMDB($tmdbKey);

$db = new mysqli($host, $dbusername, $dbpassword, $dbname) or die("Connection Error: " . mysqli_error($db));

if (isset($_GET['query'])) {
    $search = $db->real_escape_string($_GET['query']);
} else {
    http_response_code(501);
    die('error - no query');
}

$query = "SELECT * FROM movieList WHERE MATCH (title) AGAINST ('$search') LIMIT 400";

if (!$result = $db->query($query)) {
    die(http_response_code(400));
}

$imageBaseURL = 'http://image.tmdb.org/t/p/w185';

$arr = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tempRow = $row;
        $title = $tempRow['title'];
        $type = $tempRow['type'];
        $counter = 0;
        $inArray = false;

        // Check to see if already in array
        for ($i = 0; $i < count($arr); ++$i) {
            if($arr[$i]['title'] == $title){
                if (strpos($arr[$i]['server'],$tempRow['server']) !== false) {
                     $inArray = true;
                }
                else{
                    $arr[$i]['server'] = $arr[$i]['server'].", ".$tempRow['server'];
                    $inArray = true;
                    break;
                    
                }
            }
        }

        if (!$inArray) {
            if ($type == "show") {
                $tvShows = $tmdb->searchTVShow($title);
                foreach ($tvShows as $tvShow) {
                    if ($counter == 1) {
                        break;
                    }
                    $art = $tvShow->getPoster();
                    $counter++;
                }
            } else {
                $movies = $tmdb->searchMovie($title);
                // Returns an array of Movie objects
                foreach ($movies as $movie) {
                    if ($counter == 1) {
                        break;
                    }
                    $art = $movie->getPoster();
                    $counter++;
                }
            }

            if ($art != NULL) {
                $tempRow['art'] = $imageBaseURL . $art;

            }

            // Replace all non-UTF8 characters from the summary
            // This often happens in Plex
            $tempRow['summary'] = iconv("UTF-8", "UTF-8//IGNORE", $tempRow['summary']);

            $arr[] = $tempRow;
        }
    }
}

// JSON-encode the response
$json_response = json_encode($arr);

// Return the response
echo $json_response;