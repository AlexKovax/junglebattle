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
$app->get('/videos/:animal1/:animal2/:max_results', 'get_videos_two_json');
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

  $query = '
            SELECT
              animal_id, name, image
            FROM
              animals
           ';
  $result = pg_query_params($dbconn, $query, array()) or die('Query failed: ' . pg_last_error());

  $animals = array();
  while ($data = pg_fetch_assoc($result))
  {
    $animals[intval($data['animal_id'])] = array(
                                         'animal_id' => intval($data['animal_id']),
                                         'name' => $data['name'],
                                         'image' => $data['image'],
                                         'rank' => 0
                                        );
  }
  return $animals;
}


function get_animal_list_json() {
  global $dbconn;
  $animals = get_animal_list();

  $query = '
            SELECT a.animal_id, a.name, a.image, count(*) as cnt
            FROM
              votes vt,
              videos v,
                     animals a
            WHERE
                vt.video_id = v.video_id
            AND (    ( v.animal1_id = a.animal_id AND vt.winner = 1 )
                  OR ( v.animal2_id = a.animal_id AND vt.winner = 2 )
                )
            GROUP BY
              a.animal_id, a.name, a.image
            ORDER BY cnt DESC, a.animal_id DESC
           ';
  $result = pg_query_params($dbconn, $query, array()) or die('Query failed: ' . pg_last_error());
  $rank_current = 1;

  $animals_ranked = array();
  while ($data = pg_fetch_assoc($result))
  {
    $animals[intval($data['animal_id'])]['rank'] = $rank_current;

    $animals_ranked[intval($data['animal_id'])] = array(
                                                         'animal_id' => intval($data['animal_id']),
                                                         'name' => $data['name'],
                                                         'image' => $data['image'],
                                                         'rank' => $rank_current
                                                        );
    $rank_current += 1;
  }

  foreach ($animals as $animal) {
    if (!array_key_exists($animals_ranked, $animal['animal_id'])) {
      $animals_ranked[$animal['animal_id']] = $animal;
    }
  }

  $animals_out = array();

  foreach ($animals_ranked as $animal) {
    $animals_out[] = $animal;
  }

  echo json_encode($animals_out);
}





function get_videos_zero($max_results) {
  global $DEVELOPER_KEY;
  $animals = get_animal_list();

  $num_pairs = 10;
  $selected = array_rand($animals, $num_pairs * 2);
  //echo length($animals) . '<br>';
  $videos = array();
  for($i=0;$i<$num_pairs;$i++) {
    $videos_current = get_videos_two($animals[$selected[$i*2]]['name'], $animals[$selected[$i*2+1]]['name'], $max_results);
    $videos = array_merge($videos, $videos_current);
  }

  shuffle($videos);
  echo json_encode($videos);
}





function get_videos_one($animal1, $max_results) {
  global $DEVELOPER_KEY;
  $animals = get_animal_list();

  $num_pairs = 10;
  //print_r($animals);

  $selected = array_rand($animals, $num_pairs);
  $videos = array();
  for($i=0;$i<$num_pairs;$i++) {
    if( $animals[$selected[$i]]['name'] != $animal1 ) {
      $videos_current = get_videos_two($animal1, $animals[$selected[$i]]['name'], $max_results);
      $videos = array_merge($videos, $videos_current);
    }
  }

  echo json_encode($videos);
}



function get_videos_two_json($animal1, $animal2, $max_results) {
  echo json_encode(get_videos_two($animal1, $animal2, $max_results));
}


function get_videos_two($animal1, $animal2, $max_results) {
  global $dbconn, $DEVELOPER_KEY;

  $animals = get_animal_list();
  $animals_by_name = array();
  foreach ($animals as $animal) {
    $animals_by_name[$animal['name']] = $animal;
  }

  $animal1_fixed = 0;
  $animal2_fixed = 0;

  if($animals_by_name[$animal1]['animal_id'] <= $animals_by_name[$animal2]['animal_id']) {
    $animal1_fixed = $animal1;
    $animal2_fixed = $animal2;
  } else {
    $animal1_fixed = $animal2;
    $animal2_fixed = $animal1;
  }

  $user_id = $_SERVER['REMOTE_ADDR'];

  $client = new Google_Client();
  $client->setDeveloperKey($DEVELOPER_KEY);
  $youtube = new Google_YoutubeService($client);

  $videos_out[] = array();

  $videos = '';
  $channels = '';
  $playlists = '';

  try {
    $searchResponse = $youtube->search->listSearch('id,snippet', array(
      'q' => "$animal1_fixed vs $animal2_fixed",
      'maxResults' => $max_results,
      //'videoEmbeddable' => 'true',
      //'videoCategoryId' => '12'
      //'videoDuration' => "short",
      //'order' => 'viewCount',
    ));

    foreach ($searchResponse['items'] as $searchResult) {
      switch ($searchResult['id']['kind']) {
        case 'youtube#video':
          $videos .= sprintf('<li>%s (%s) - (%s) - (%s)</li>', $searchResult['snippet']['title'],
            $searchResult['id']['videoId'],
            $searchResult['snippet']['description'],
            $searchResult['snippet']['videoCategoryId']
            );
          $videos_out[] = array(
                                'animal1' => $animal1_fixed,
                                'animal2' => $animal2_fixed,
                                'video_id' => $searchResult['id']['videoId'],
                                'source' => 'youtube'
                               );
          /*
          if( $animals_by_name[$animal1_fixed]['animal_id'] == null || $animals_by_name[$animal2_fixed]['animal_id'] == null) {
            echo 'error<br>';
            echo $animal1_fixed . ' | ' . $animal2_fixed . '<br>';
            echo $animals_by_name[$animal1_fixed]['animal_id'] . ' | ' . $animals_by_name[$animal2_fixed]['animal_id'] . '<br>';
          }
          */

          $query = 'INSERT INTO videos (video_id, animal1_id, animal2_id, date_added, added_type) SELECT $1, $2, $3, now(), $4
                    WHERE NOT EXISTS (SELECT video_id FROM videos WHERE video_id=$1)';
          pg_query_params($dbconn, $query, array($searchResult['id']['videoId'], $animals_by_name[$animal1_fixed]['animal_id'], $animals_by_name[$animal2_fixed]['animal_id'], 'youtube')) or die('Query failed: ' . pg_last_error());

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

  return $videos_out2;
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
