<?php

header('Access-Control-Allow-Origin: *');
header('Content-type: application/json');

set_include_path('.');
require_once('config.php');
require_once('google-api-php-client/src/Google_Client.php');
require_once('google-api-php-client/src/contrib/Google_YouTubeService.php');

// Connecting, selecting database
$dbconn = pg_connect("host=$DB_HOST dbname=$DB_NAME user=$DB_USER password=$DB_PASSWORD")
    or die('Could not connect: ' . pg_last_error());

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




function get_animal_list() {
  global $dbconn;

  $query = 'SELECT animal_id, name FROM animals';
  $result = pg_query_params($dbconn, $query, array()) or die('Query failed: ' . pg_last_error());

  $animals = array();
  while ($data = pg_fetch_assoc($result))
  {
    $animals[] = array('animal_id' => $data['animal_id'],
                       'name' => $data['name'],
                       'rank' => 0);
  }
  return $animals;
}


function get_videos($animal1, $animal2, $max_results) {
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

  
  return $videos_out;
}
?>
