<?

// Configuration - put your soundcloud client ID and secret here
global $soundCloudConfig;
$soundCloudConfig['clientID'] = "";
$soundCloudConfig['clientSecret'] = "";
// Now we need to know which tracks you want the user to be able to favorite
// Each entry in the array should be a track ID (eg 1231242) or a permalink (eg 'my-great-song')
// If you only want one song, just make the array length-one.
// The default track will be the first track listed
$soundCloudConfig['tracks'] = array( 'cool-song-1', 'cool-song-2' );
// The names for those tracks, can be whatever you want.
// Each entry corresponds to an entry to the tracks array above - so it should be EXACTLY the same size!
$soundCloudConfig['trackNames'] = array( "Cool Song 1", "Cool Song 2" );
// Permalink for your account, which they will automatically follow.
$soundCloudConfig['user'] = 'four-tet';
// Username - this is what gets displayed
$soundCloudConfig['userName'] = 'Four Tet';


include("Services/Soundcloud.php");

class SoundCloud extends Plugin
{
  function callback()
  {
    global $soundCloudConfig;
    global $config;
    // Set the cookie for the track ID to favorite
    if (!isset($_GET['code']))
    {          
      // Get the track they selected - or if it's empty, just use the default (first) track
      $soundCloudTrack = empty($_GET['soundCloudTrack']) ? $soundCloudConfig['tracks'][0] : $_GET['soundCloudTrack'];
      // If the GET track id is not in the track ID array (some kind of weird "hacking" attempt)
      if (!in_array($soundCloudTrack, $soundCloudConfig['tracks']))
      {
        $soundCloudTrack = $soundCloudConfig['tracks'][0];
      }
      // Set the cookie
      setcookie('soundCloudTrack', $soundCloudTrack, time() + 60*5, '/', "." . $config['domain']);
      // Get the SoundCloud authorization URL
      $soundCloud = new Services_Soundcloud($soundCloudConfig['clientID'], $soundCloudConfig['clientSecret'], $config['url'] . '?plugin=soundcloud' ); 
      $soundCloudURL = $soundCloud->getAuthorizeUrl();
      // Redirect to that location
      header('Location: ' . $soundCloudURL);
    }
    else
    {
      // Create soundcloud object
      $soundCloud = new Services_Soundcloud($soundCloudConfig['clientID'], $soundCloudConfig['clientSecret'], $config['url'] . '?plugin=soundcloud' );
      // Store access token
      $accessToken = $soundCloud->accessToken($_GET['code']);
      // Get the track ID to favorite
      $trackID = $_COOKIE['soundCloudTrack'];
      // Favorite it
      $favoriteJSON = json_decode(@$soundCloud->put("me/favorites/$trackID.json"), true);
      // On success, try following the user
      if( !strcmp($favoriteJSON['status'], "201 - Created") || !strcmp($favoriteJSON['status'], "200 - OK") )
      {
        // Favorite the user
        $userJSON = json_decode(@$soundCloud->get("users/" . $soundCloudConfig['user'] . ".json"), true);
        $id = $userJSON['id'];
        $favoriteJSON = json_decode(@$soundCloud->put("users/me/followings/" . $id . ".json"), true);
        // For some reason when you are creating a new connection, it does not populate ['status'], so that's what the final strcmp is doing there
        if( !strcmp($favoriteJSON['status'], "201 - Created") || !strcmp($favoriteJSON['status'], "200 - OK") || !strcmp($favoriteJSON['id'], $id))
        {
          return True;
        }
        else
        {
          return "Couldn't follow the user: " . $favoriteJSON['status'];
        }        
      }
      else
      {
        return "Couldn't favorite the track: " . $favoriteJSON['status'];
      }
    }
  }
  function option()
  {
    global $config;
    global $soundCloudConfig;
    $albumOrTrackText = (count($soundCloudConfig['tracks']) == 1) ? ' the track "' . $soundCloudConfig['trackNames'][0] : ' a track from "' . $config['product'];
    return 'Favorite ' . $albumOrTrackText . '" and follow ' . $soundCloudConfig['userName'] . ' on soundcloud';
  }
  function description()
  {
    global $soundCloudConfig;
    global $config;
    if (count($soundCloudConfig['tracks']) == 1)
    {
      $description = '<a href="' . $config['url'] . '?plugin=soundcloud&soundCloudTrack=' . urlencode($soundCloudConfig['tracks'][0]) . '">Click here</a> to log into SoundCloud and favorite "' . $soundCloudConfig['trackNames'][0] . '" and start following <a href="http://soundcloud.com/' . $soundCloudConfig['user'] . '">' . $soundCloudConfig['userName'] . '</a>.';
    }
    else
    {
      $description = <<<EOD
Please select the song you want to favorite on SoundCloud and hit "submit" to favorite it and start following <a href="http://soundcloud.com/{$soundCloudConfig['user']}">{$soundCloudConfig['userName']}</a>.<br /><br />
</small>
<form action="{$config['url']}" method="get">
<input type="hidden" name="plugin" value="soundcloud" />
<select name="soundCloudTrack">
EOD;
      foreach( $soundCloudConfig['tracks'] as $key=>$track)
      {
        $description .= '<option value="' . $track . '">' . $soundCloudConfig['trackNames'][$key] . '</option>';
      }
$description .= <<<EOD
<input type="submit" value="Submit" style="float: right" /><br /><br />
</form>
<small>
EOD;
    }
    return $description;
  }
  function success()
  {
    global $config;
    global $soundCloudConfig;
    return 'favoriting a track from "' . $config['product'] . '" and following <a href="http://soundcloud.com/' . $soundCloudConfig['user'] . '">' . $soundCloudConfig['userName'] . '</a>';
  }
  function shouldRun()
  {
    return (strcmp( $_GET['plugin'], 'soundcloud') == 0);
  }
  function name()
  {
    return "SoundCloud";
  }
}

$soundcloud = new SoundCloud;

$plugins[] = $soundcloud;


?>
