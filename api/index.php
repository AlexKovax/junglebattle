<?php

header('Access-Control-Allow-Origin: *');
header('Content-type: application/json');

set_include_path('.');
require_once('config.php');
require_once('google-api-php-client/src/Google_Client.php');
require_once('google-api-php-client/src/contrib/Google_YouTubeService.php');


require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app->contentType('application/json; charset=utf-8');

// Connecting, selecting database
$dbconn = pg_connect("host=$DB_HOST dbname=$DB_NAME user=$DB_USER password=$DB_PASSWORD")
    or die('Could not connect: ' . pg_last_error());

function make_seed()
{
  list($usec, $sec) = explode(' ', microtime());
  return (float) $sec + ((float) $usec * 100000);
}

srand(make_seed());


$app->get('/animals', 'get_animal_list_json');
$app->get('/videos/:max_results', 'get_videos_zero');
$app->get('/videos/:animal1/:max_results', 'get_videos_one');
$app->get('/videos/:animal1/:animal2/:max_results', 'get_videos_two');
$app->get('/vote/:video_id/:winner', 'vote');
$app->get('/complete/:video_ids', 'get_video_details');
$app->run();


/*
if ($_GET['action']) {
  if ($_GET['action'] == 'videos') {
    $animal1 = $_GET['animal1'];
    $animal2 = $_GET['animal2'];
    $data = get_videos($animal1, $animal2, $_GET['maxResults']);
    echo json_encode($data);
    exit(); 
  } else if ($_GET['action'] == 'animal_list') {
    $data = get_animal_list();
    echo json_encode($data);
  } else {
    echo json_encode(array('status'=>'error', 'error_message'=>'Invalid parameters'));
    exit();
  }
} else {
  echo json_encode(array('status'=>'error', 'error_message'=>'Invalid parameters'));
  exit();
}
*/



function get_animal_list() {
  global $dbconn;

  $query = 'SELECT animal_id, name, image FROM animals';
  $result = pg_query_params($dbconn, $query, array()) or die('Query failed: ' . pg_last_error());

  $animals = array();
  while ($data = pg_fetch_assoc($result))
  {
    $animals[] = array(
                       'name' => $data['name'],
                       'image' => $data['image'],
                       'rank' => 0
                      );
  }
  return $animals;
}


function get_animal_list_json() {
  echo json_encode(get_animal_list());
}  




function get_videos_zero($max_results) {
  global $DEVELOPER_KEY;
  $animals = get_animal_list();
  $selected = array_rand($animals, 2);
  //echo length($animals) . '<br>';
  return get_videos_two($animals[$selected[0]]['name'], $animals[$selected[1]]['name'], $max_results);
}





function get_videos_one($animal1, $max_results) {
  global $DEVELOPER_KEY;
  $animals = get_animal_list();

  while(true) {
    $selected = array_rand($animals, 1);
    if( $selected[0]['name'] != $animal1 ) {
      return get_videos_two($animals1, $animals[$selected[0]]['name'], $max_results);
    }
  }
}





function get_videos_two($animal1, $animal2, $max_results) {
  global $DEVELOPER_KEY;

  //ini_set('memory_limit', '-1');

  /* Set $DEVELOPER_KEY to the "API key" value from the "Access" tab of the
  Google APIs Console <http://code.google.com/apis/console#access>
  Please ensure that you have enabled the YouTube Data API for your project. */

  $client = new Google_Client();
  $client->setDeveloperKey($DEVELOPER_KEY);
  $youtube = new Google_YoutubeService($client);

  $videos_out[] = array();

  $videos = '';
  $channels = '';
  $playlists = '';

  try {
    $searchResponse = $youtube->search->listSearch('id,snippet', array(
      'q' => "$animal1 vs $animal2",
      'maxResults' => $max_results,
      //'videoDuration' => "short",
      //'order' => 'viewCount',
    ));

    foreach ($searchResponse['items'] as $searchResult) {
      switch ($searchResult['id']['kind']) {
        case 'youtube#video':
          $videos .= sprintf('<li>%s (%s) - (%s) - (%s)</li>', $searchResult['snippet']['title'],
            $searchResult['id']['videoId'],
            $searchResult['snippet']['description'],
            $searchResult['snippet']['categoryId']
            );
          $videos_out[] = array(
                                'animal1' => $animal1,
                                'animal2' => $animal2,
                                'video_id' => $searchResult['id']['videoId'],
                                'source' => 'youtube'
                               );

          break;
        case 'youtube#channel':
          $channels .= sprintf('<li>%s (%s)</li>', $searchResult['snippet']['title'],
            $searchResult['id']['channelId']);
          break;
        case 'youtube#playlist':
          $playlists .= sprintf('<li>%s (%s)</li>', $searchResult['snippet']['title'],
            $searchResult['id']['playlistId']);
          break;
      }
    }

    //echo $htmlBody;
  } catch (Google_ServiceException $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  }

  $videos_out2 = array();
  foreach ($videos_out as $video) {
    if(!empty($video)) {
      $videos_out2[] = $video;
    }
  }


  
  echo json_encode($videos_out2);
}


function get_video_details($video_ids) {
  global $DEVELOPER_KEY;
  echo 'complete_videos' . $video_ids;

  $client = new Google_Client();
  $client->setDeveloperKey($DEVELOPER_KEY);
  $youtube = new Google_YoutubeService($client);

  $searchResponse2 = $youtube->videos->listVideos($video_ids, 'id,contentDetails', array(
    
  ));

  foreach ($searchResponse2['items'] as $searchResult2) {
    print_r($searchResult2);
  }

}



function vote($video_id, $winner) {
  global $dbconn, $app;
  $user_id = $_SERVER['REMOTE_ADDR'];

  $query = 'INSERT INTO votes (video_id, user_id, winner, date_voted) VALUES ($1, $2, $3, now())';
  $result = pg_query_params($dbconn, $query, array($video_id, $user_id, $winner)) or die('Query failed: ' . pg_last_error());

  $query = 'SELECT video_id, winner, count(*) as cnt FROM votes WHERE video_id=$1 GROUP BY winner, video_id';
  $result = pg_query_params($dbconn, $query, array($video_id)) or die('Query failed: ' . pg_last_error());

  $votes = array();
  $votes[1] = array(
                      'video_id' => $video_id,
                      'animal_number' => 1,
                      'num_votes' => 0
                    );

  $votes[2] = array(
                      'video_id' => $video_id,
                      'animal_number' => 2,
                      'num_votes' => 0
                    );
  while ($data = pg_fetch_assoc($result)) {
    $votes[$data['winner']] = array(
                                'video_id' => $data['video_id'],
                                'animal_number' => $data['winner'],
                                'num_votes' => $data['cnt']
                              );
  }

  $votes_out= array();
  foreach ($votes as $vote) {
    $votes_out[] = $vote;
  }

  echo json_encode($votes_out);
}



?>
