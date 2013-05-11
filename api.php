<?php

require_once('config.php');

// Connecting, selecting database
//$dbconn = pg_connect("host=$DB_HOST dbname=$DB_NAME user=$DB_USER password=$DB_PASSWORD")
//    or die('Could not connect: ' . pg_last_error());


$htmlBody = <<<END
Hello ! <br>
<form method="GET">
  <div>
    Search Term: <input type="search" id="q" name="q" placeholder="Enter Search Term">
  </div>
  <div>
    Max Results: <input type="number" id="maxResults" name="maxResults" min="1" max="50" step="1" value="25">
  </div>
  <input type="submit" value="Search">
</form>
END;


if ($_GET['q'] && $_GET['maxResults']) {
    $animal1 = $_GET['animal1'];
    $animal2 = $_GET['animal2'];
    $data = get_videos($animal1, $animal2);

    echo json_encode(array('auth'=>'error','status'=>'error'));
    exit(); 
}


function get_videos($animal1, $animal2) {
  //ini_set('memory_limit', '-1');
  set_include_path('.');
  require_once('google-api-php-client/src/Google_Client.php');
  require_once('google-api-php-client/src/contrib/Google_YouTubeService.php');

  /* Set $DEVELOPER_KEY to the "API key" value from the "Access" tab of the
  Google APIs Console <http://code.google.com/apis/console#access>
  Please ensure that you have enabled the YouTube Data API for your project. */

  $client = new Google_Client();
  $client->setDeveloperKey($DEVELOPER_KEY);
  $youtube = new Google_YoutubeService($client);

  $videos_out[] = array();

  try {
    $searchResponse = $youtube->search->listSearch('id,snippet', array(
      //'q' => $_GET['q'],
      'q' => "$animal1 vs $animal2",
      'maxResults' => $_GET['maxResults'],
    ));

    $videos = '';
    $channels = '';
    $playlists = '';

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
                                'video_id' => $searchResult['snippet']['videoId'],
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

    $htmlBody .= <<<END
    <h3>Videos</h3>
    <ul>$videos</ul>
    <h3>Channels</h3>
    <ul>$channels</ul>
    <h3>Playlists</h3>
    <ul>$playlists</ul>
END;
  } catch (Google_ServiceException $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  }
}

/*
<!doctype html>
<html>
  <head>
    <title>YouTube Search</title>
  </head>
  <body>
    <?=$htmlBody?>
  </body>
</html>
*/
?>
