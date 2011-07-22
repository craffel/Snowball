<?

// Configuration - put your last.fm API key and secret here:
global $lastFMConfig;
$lastFMConfig['APIKey'] = '';
$lastFMConfig['secret'] = '';
// Put the artist and album names here
// - all tracks from this album will be scrobbled, and the user will select one to favorite
$lastFMConfig['artistName'] = 'The Beatles';
$lastFMConfig['albumName'] = 'Abbey Road';

global $plugins;

// Form a last.fm signature... expects $names and $values to be in alphabetical order
function lastfmSig($names, $values, $secret)
{
  $apisig = '';
  foreach ($names as $key => $value)
  {
    $apisig .= utf8_encode($names[$key]) . utf8_encode($values[$key]);
  }
  $apisig .= $secret;
  //echo $apisig;
  return md5($apisig);
}

// Convert objects into an array
function objectsIntoArray($arrObjData, $arrSkipIndices = array())
{
    $arrData = array();
    
    // if input is object, convert into array
    if (is_object($arrObjData)) {
        $arrObjData = get_object_vars($arrObjData);
    }
    
    if (is_array($arrObjData)) {
        foreach ($arrObjData as $index => $value) {
            if (is_object($value) || is_array($value)) {
                $value = objectsIntoArray($value, $arrSkipIndices); // recursive call
            }
            if (in_array($index, $arrSkipIndices)) {
                continue;
            }
            $arrData[$index] = $value;
        }
    }
    return $arrData;
}

class Lastfm extends Plugin
{
  function callback()
  {
    global $lastFMConfig;
    global $config;
    if (!isset($_GET['token']))
    {
      $trackInfoRaw = file_get_contents("http://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=" . $lastFMConfig['APIKey'] . "&artist=".urlencode($lastFMConfig['artistName'])."&album=".urlencode($lastFMConfig['albumName']));
      $trackInfoXML = simplexml_load_string($trackInfoRaw);
      $trackInfoArray = objectsIntoArray($trackInfoXML);
      $tracks = array();
      foreach ($trackInfoArray['album']['tracks']['track'] as $track)
      {
        $tracks[] = $track['name'];
      }
      // Get the track they selected - or if it's empty, just use the default (first) track
      $lastfmTrack = empty($_GET['lastfmTrack']) ? $tracks[0] : $_GET['lastfmTrack'];
      // If the GET track id is not in the track ID array (some kind of weird "hacking" attempt)
      if (!in_array($lastfmTrack, $tracks))
      {
        $lastfmTrack = $tracks[0];
      }
      // Set the cookie
      setcookie('lastfmTrack', $lastfmTrack, time() + 60*5, '/', "." . $config['domain']);
      $lastfmURL = 'http://www.last.fm/api/auth/?api_key='. $lastFMConfig['APIKey'] . '&cb=' . urlencode($config['url']) . '?plugin=lastfm';
      // Redirect to that location
      header('Location: ' . $lastfmURL);
    }
    else
    {
      // Get last.fm token from URL
      $token = $_GET['token'];
      // last.fm method
      $method = 'auth.getSession';
      // Should be ordered alphabetically by parameter name (WTF remember?)!
      $authSig = lastfmSig(array('api_key', 'method', 'token'), array($lastFMConfig['APIKey'], $method, $token), $lastFMConfig['secret']);
      // Get last.fm session key
      $skURL = "http://ws.audioscrobbler.com/2.0/?method=auth.getSession&token=$token&api_key=" . $lastFMConfig['APIKey'] . "&api_sig=$authSig";
      $lastfmSKRaw = file_get_contents($skURL);
      $lastfmSKXML = simplexml_load_string($lastfmSKRaw);
      $lastfmSKArray = objectsIntoArray($lastfmSKXML);
      $lastfmSK = $lastfmSKArray['session']['key'];
      // Get track info
      $trackInfoRaw = file_get_contents("http://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=" . $lastFMConfig['APIKey'] . "&artist=".urlencode($lastFMConfig['artistName'])."&album=".urlencode($lastFMConfig['albumName']));
      $trackInfoXML = simplexml_load_string($trackInfoRaw);
      $trackInfoArray = objectsIntoArray($trackInfoXML);
      // The duration of the tracks scrobbled - this makes the scrobble times believable
      $durationSoFar = 0;
      // Number scrobbled so far and total number of tracks, for checking that we were successful
      $numScrobbled = 0;
      $totalTracks = 0;
      // For all the tracks
      foreach ($trackInfoArray['album']['tracks']['track'] as $track)
      {  
        // Get track duration
        $duration = $track['duration'];
        // Set the timestamp based on the duration
        $timestamp = time() - $duration - $durationSoFar;
        // Increment the duration of tracks scrobbled so far
        $durationSoFar += $duration;
        // Song name
        $song = $track['name'];
        // Scrobble the track
        $method = 'track.scrobble';
        $api_sig = lastfmSig(array('album', 'api_key', 'artist', 'duration', 'method', 'sk', 'timestamp', 'track' ), array($lastFMConfig['albumName'], $lastFMConfig['APIKey'], $lastFMConfig['artistName'], $duration, $method, $lastfmSK, $timestamp, $song), $lastFMConfig['secret']);
        $scrobbleURL = "http://ws.audioscrobbler.com/2.0/";
        $scrobblePOST = "method=$method&timestamp=$timestamp&album=" . $lastFMConfig['albumName'] . "&duration=$duration&track=$song&artist=". $lastFMConfig['artistName'] . "&api_key=" . $lastFMConfig['APIKey'] . "&sk=$lastfmSK&api_sig=$api_sig";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $scrobbleURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $scrobblePOST);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $lastfmScrobbleRaw = curl_exec($ch);
        curl_close($ch);
        $lastfmScrobbleXML = simplexml_load_string($lastfmScrobbleRaw);
        $lastfmScrobbleArray = objectsIntoArray($lastfmScrobbleXML);
        // If the scrobbling was successful, increment the number scrobbled
        if ($lastfmScrobbleArray['scrobbles']['@attributes']['accepted'] == 1)
        {
          $numScrobbled++;
        }      
        $totalTracks++;
      }
      
      if ($numScrobbled == $totalTracks)
      {
        // Love the track
        $method = 'track.love';
        $song = $_COOKIE['lastfmTrack'];
        $api_sig = lastfmSig(array('api_key', 'artist', 'method', 'sk', 'track' ), array($lastFMConfig['APIKey'], $lastFMConfig['artistName'], $method, $lastfmSK, $song), $lastFMConfig['secret']);
        $scrobbleURL = "http://ws.audioscrobbler.com/2.0/";
        $scrobblePOST = "method=$method&track=$song&artist=" . $lastFMConfig['artistName'] . "&api_key=" . $lastFMConfig['APIKey'] . "&sk=$lastfmSK&api_sig=$api_sig";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $scrobbleURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $scrobblePOST);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $lastfmScrobbleRaw = curl_exec($ch);
        curl_close($ch);
        $lastfmScrobbleXML = simplexml_load_string($lastfmScrobbleRaw);
        $lastfmScrobbleArray = objectsIntoArray($lastfmScrobbleXML);
        if ( !strcmp($lastfmScrobbleArray['@attributes']['status'], 'ok') )
        {
          return True;
        }
        else
        {
          return "Couldn't love the track: " . $lastfmScrobbleArray['@attributes']['status'];
        }
      }
      else
      {
        return "Couldn't scrobble tracks.";
      }
    }
  }
  function option()
  {
    return "Scrobble the entire album and love a track with your last.fm account";
  }
  function description()
  {
    global $lastFMConfig;
    global $config;
    // Get the tracks
    $trackInfoRaw = file_get_contents("http://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=" . $lastFMConfig['APIKey'] . "&artist=".urlencode($lastFMConfig['artistName'])."&album=".urlencode($lastFMConfig['albumName']));
    $trackInfoXML = simplexml_load_string($trackInfoRaw);
    $trackInfoArray = objectsIntoArray($trackInfoXML);
    $tracks = $trackInfoArray['album']['tracks']['track'];
    // If the album is length-1, don't give any choice
    if (count($tracks) == 1)
    {
      $description = '<a href="' . $config['url'] . '?plugin=lastfm&lastfmTrack=' . urlencode($tracks[0]['name']) . '">Click here</a> to log into Last.fm and love and scrobble "' . $tracks[0]['name'] . '".';
    }
    else
    {
    
      $description = <<<EOD
Please select the song you want to love on last.fm and hit "submit" to love it and scrobble the rest of the tracks from {$lastFMConfig['albumName']}.<br /><br />
</small>
<form action="{$config['url']}" method="get">
<input type="hidden" name="plugin" value="lastfm" />
<select name="lastfmTrack">
EOD;
      foreach( $tracks as $track)
      {
        $description .= '<option value="' . $track['name'] . '">' . $track['name'] . '</option>';
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
    global $lastFMConfig;
    return "scrobbling and loving tracks from " . $lastFMConfig['albumName'];
  }
  function shouldRun()
  {
    return (strcmp( $_GET['plugin'], 'lastfm') == 0);
  }
  function name()
  {
    return "Lastfm";
  }

}

$lastfm = new Lastfm;

$plugins[] = $lastfm;

?>