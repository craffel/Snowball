<?

// Configuration - put your twitter consumer key and secret here:
global $twitterConfig;
$twitterConfig['consumerKey'] = '';
$twitterConfig['consumerSecret'] = '';
// What should the link be to?  Could be the download page URL, homepage URL, bit.ly URL, etc...
$twitterConfig['link'] = 'http://bit.ly/shortlink';
// The default tweet for them to post (can be changed)
$twitterConfig['defaultTweet'] = "I'm about to download this cool new thing!";
// The twitter username you want them to follow
$twitterConfig['followUser'] = "shaq";
// ..and that username's ID (you can get it here: http://www.idfromuser.com/)
$twitterConfig['followID'] = "17461978";

include("EpiCurl.php");
include("EpiOAuth.php");
include("EpiTwitter.php");

class Twitter extends Plugin
{
  function callback()
  {
    global $twitterConfig;
    global $config;
    // First, store the tweet as a cookie (this is indicated when there's no oauth token yet)
    if (!isset($_GET['oauth_token']))
    {
      // Get the tweet they entered - or if it's empty, just use the default tweet
      $tweet = empty($_GET['tweet']) ? $twitterConfig['defaultTweet'] : $_GET['tweet'];
      // Truncate - this shouldn't apply because of the input maxlen, but let's be safe
      $tweet = substr($tweet, 0, 138 - strlen($twitterConfig['link']));
      // Add the link
      $tweet .= ' ' . $twitterConfig['link'];
      // Set the cookie
      setcookie('tweet', $tweet, time() + 60*5, '/', "." . $config['domain']);
      // Get the twitter authorization URL
      $twitterObj = new EpiTwitter($twitterConfig['consumerKey'], $twitterConfig['consumerSecret']); 
      $twitterURL = $twitterObj->getAuthorizationUrl();
      // Redirect to that location
      header('Location: ' . $twitterURL);
    }
    else
    {
      // Create twitter object
      $twitterObj = new EpiTwitter($twitterConfig['consumerKey'], $twitterConfig['consumerSecret']);
      $twitterObj->setToken($_GET['oauth_token']);
      $token = $twitterObj->getAccessToken();
      $twitterObj->setToken($token->oauth_token, $token->oauth_token_secret);
      $twitterInfo = $twitterObj->get_accountVerify_credentials();
      $twitterInfo->response;
      $twitterObj->setToken($token->oauth_token, $token->oauth_token_secret);
      // Message to post to twitter
      $msg = $_COOKIE['tweet'];
      // Truncate it just in case
      $msg = substr($msg, 0, 139);
      // Also strip slashes
      $msg = stripslashes(stripslashes($msg));
      // Update twitter status
      $update_status = $twitterObj->post_statusesUpdate(array('status' => $msg));
      $temp = $update_status->response;
      if ($temp && $temp['text'])
      {
        // Now try following
        $followUser = $twitterObj->post_friendshipsCreate(array('screen_name' => $twitterConfig['followUser'], 'user_id' => $twitterConfig['followID'], ));
        $temp = $followUser->response;
        if ($temp)
        {
          return True;  
        }
        else
        {
          return "Couldn't follow " . $twitterConfig['followUser'] . ".";
        }
      }
      else
      {
        return "Couldn't update status.";
      }
    }
  }
  function option()
  {
    global $config;
    global $twitterConfig;
    return 'Post on twitter about "' . $config['product'] . '" and follow ' . $twitterConfig['followUser'];
  }
  function description()
  {
    global $twitterConfig;
    global $config;
    $maxLength = 139 - strlen($twitterConfig['link']);
    $description = <<<EOD
Please enter what you'd like to tweet below and hit "Submit".  A link will be included at the end, so don't worry about adding one.  You'll also automatically start following <a href="http://www.twitter.com/{$twitterConfig['followUser']}">{$twitterConfig['followUser']}</a>.<br /><br />
</small>
<form action="{$config['url']}" method="get">
<input type="hidden" name="plugin" value="twitter" />
<input type="text" maxlength="{$maxLength}" name="tweet" value="{$twitterConfig['defaultTweet']}" style="width: 400px" />
<input type="submit" value="Submit" style="float: right;"/><br /><br />
</form>
<small>
EOD;
    return $description;
  }
  function success()
  {
    global $config;
    global $twitterConfig;
    return 'tweeting about "' . $config['product'] . '" and following <a href="http://www.twitter.com/' . $twitterConfig['followUser'] . '">' . $twitterConfig['followUser'] . '</a>';
  }
  function shouldRun()
  {
    return (strcmp( $_GET['plugin'], 'twitter') == 0);
  }
  function name()
  {
    return "Twitter";
  }
}

$twitter = new Twitter;

$plugins[] = $twitter;


?>