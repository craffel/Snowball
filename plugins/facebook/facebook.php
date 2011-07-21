<?

// Configuration - put your facebook app ID and app secret here
global $facebookConfig;
$facebookConfig['appID'] = '';
$facebookConfig['appSecret'] = '';
// The name of the page to be liked
$facebookConfig['pageName'] = "Google";
// The page's id
$facebookConfig['pageID'] = "104958162837";
// Default status update
$facebookConfig['defaultStatus'] = "I'm about to download something";
// What should the status update link to?
$facebookConfig['statusLink'] = "http://www.google.com";
// Source for the like box.  Get it here: http://developers.facebook.com/docs/reference/plugins/like-box/
$facebookConfig['likeboxSource'] = '';

include('src/facebook.php');

class FacebookPlugin extends Plugin
{
  function callback()
  {
    global $facebookConfig;
    global $config;
    // Create our application instance.
    $facebook = new Facebook(array( 'appId' => $facebookConfig['appID'], 'secret' => $facebookConfig['appSecret']));
    // Get user object to test if user is logged in or not
    $user = $facebook->getUser();
    // First, store the status update as a cookie (indicated by the user not being logged in yet)
    if (!isset($_GET['code']))
    {
      // Get the status they entered - or if it's empty, just use the default status
      $status = empty($_GET['status']) ? $facebookConfig['defaultStatus'] : $_GET['status'];
      // Get the facebook login
      $loginURL = $facebook->getLoginUrl(array('scope' => 'publish_stream' ));
      // Redirect to that location
      header('Location: ' . $loginURL);
    }
    else
    {
      // Check if fan
      try
      {
        $isFan = $facebook->api(array("method" => "pages.isFan", "page_id" => $facebookConfig['pageID'], "uid" => $user ));
      }
      catch (FacebookApiException $e)
      {
        return "Couldn't check if you were a fan: " . print_r($e, True);
      }
      if($isFan === TRUE)
      {
        // Message to post to facebook
        $status = empty($_GET['status']) ? $facebookConfig['defaultStatus'] : $_GET['status'];
        $status = stripslashes($status);
        // Update status
        try
        {
          $publishStream = $facebook->api("/$user/feed", 'post', array( 'message' => $status, 'link' => $facebookConfig['statusLink'] ));
        } 
        catch (FacebookApiException $e)
        {
          return "Couldn't post status update: " . print_r($e, True);
        }
        return True;
      }
      else
      {
        return "It looks like you haven't liked " . $facebookConfig['pageName'];
      }
    }
  }
  function option()
  {
    global $config;
    global $facebookConfig;
    return 'Like ' . $facebookConfig['pageName'] . ' on Facebook and post a status update about "' . $config['product'] . '"';
  }
  function description()
  {
    global $facebookConfig;
    global $config;
    $description = <<<EOD
First, click the "Like" button to like {$facebookConfig['pageName']} on Facebook.
{$facebookConfig['likeboxSource']}
<br />
Now, please enter what you'd like to post as your status update and hit "Submit".  A link will be included at the end, so don't worry about adding one.<br /><br />
</small>
<form action="{$config['url']}" method="get">
<input type="hidden" name="plugin" value="facebook" />
<input type="text" name="status" value="{$facebookConfig['defaultStatus']}" style="width: 400px" />
<input type="submit" value="Submit" style="float: right;" /><br /><br />
</form>
<small>
EOD;
    return $description;
  }
  function success()
  {
    global $config;
    global $facebookConfig;
    return 'posting about "' . $config['product'] . '" and liking ' . $facebookConfig['pageName'] . ' on Facebook';
  }
  function shouldRun()
  {
    return (strcmp( $_GET['plugin'], 'facebook') == 0);
  }
  function name()
  {
    return "Facebook";
  }
}

$facebook = new FacebookPlugin;

$plugins[] = $facebook;


?>