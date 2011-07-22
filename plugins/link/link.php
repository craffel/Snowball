<?

/*********************
 * MAKE SURE YOU RUN THIS PLUGIN'S install.php FIRST BEFORE USING!
 * IT IS LOCATED AT http://yoursnowball.com/install/location/plugins/link/install.php
 * Run it from your browser, then delete the install.php file.
 **********************/

global $linkConfig;
// List the URLs that are acceptable links.
// Be sure to include URLs with and without www.
// Don't worry about anything at the end of the URL, like http://www.me.com/you vs. http://www.me.com/you/
// If you're feeling lazy, you could just use everything after the subdomain, like just me.com/you/
// But this would allow people to post bogus links like http://fakesubdomain.me.com/you
// Here's an example:
$linkConfig['urls'] = array('http://yoursite.com/download', 'http://www.yoursite.com/download', 'http://bit.ly/shortlink', 'http://www.bit.ly/shortlink');
// Now, list the URLs you actually want to display to the user as an option.
// The first one will be the one used in the example HTML
// These must be valid links.
$linkConfig['urlsToShow'] = array('http://www.yoursite.com/download', 'http://bit.ly/shortlink');
// This is the "name" of the links above, should be able to fill in this blank: "Please post a link to ____________"
$linkConfig['linkName'] = "this download page";

global $plugins;

// Filesize for URLs
function getSizeFile($url) { 
  if (substr($url,0,4)=='http') 
  { 
    if (@get_headers($url, 1))
    {
      $x = array_change_key_case(get_headers($url, 1),CASE_LOWER); 
      if ( strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0 )
      {
        $x = $x['content-length'][1];
      } 
      else
      {
        $x = $x['content-length'];
      } 
    }
    else
    {
      $x = 1000000000000000000;
    }
  } 
  else
  {
    $x = @filesize($url);
  } 
  return $x; 
} 

class Link extends Plugin
{
  function callback()
  {
    global $linkConfig;
    global $config;
    // Get submitted URL
    $postedURL = $_GET['postedURL'];
    // See if how many times any of a number of URLs appear the page
    $numLinks = 0;
    // Get the contents - fail if it's bigger than 1 mb or doesn't exist
    if (getSizeFile($postedURL) > 1000000)
    {
      $numLinks = -1;
    }
    if ($numLinks == 0)
    {
    	$pageSource = file_get_contents($postedURL, $maxlen=1000000);
    	if (!$pageSource)
    	{
        $numLinks = -1;
    	}
    	// Count the number of matches for all possible URLs
      if ($numLinks == 0)
      {
      	foreach ($linkConfig['urls'] as $url)
      	{
          $numLinks += substr_count($pageSource, $url);
      	}
      }
    }
    if( $numLinks < 0)
    {
      return "There was something wrong with the URL (<a href=\"$postedURL\">$postedURL</a>) you submitted!  Is it really a web page?  Did you enter it correctly?";
    }
    elseif( $numLinks == 0)
    {
      $returnMe = 'I couldn\'t find any links to <a href="' . $linkConfig['urlsToShow'][0] . '">' . $linkConfig['urlsToShow'][0] . '</a>';
      for ($n = 1; $n < count($linkConfig['urlsToShow']); $n++ )
      {
        $returnMe .= ' or <a href="' . $linkConfig['urlsToShow'][$n] . '">' . $linkConfig['urlsToShow'][$n] . '</a>';
      }
      $returnMe .= " at <a href=\"$postedURL\">$postedURL</a>!  Did you really post a link there?  Are you sure it's 100% public?  Maybe try posting again somewhere else!";
      return $returnMe;
    }
    else
    {
      // A download link is given when the URL provided is NEW and the source hash does NOT already exist in the table
      // Here is a list of vulnerabilities, assuming someone first submits http://example.com and it has some number of links:
      // 1) Each time source of http://example.com is modified in any way, someone can submit http://www.example.com/##?id=10### etc.
      // 2) If a link is added to http://example.com, someone can submit it as http://www.example.com/##? and then someone else can re-submit as http://example.com
      $link = mysql_connect($config['dbHost'], $config['dbUser'], $config['dbPass']) or die("Database connect failed, you probably need to set up db.php and your mysql database.");
      mysql_select_db($config['db'], $link) or die ("Couldn't select the database.  You probably need to set up config.php and your mysql database.");
      $pageSourceHash = md5($pageSource);
      // Get number of times this site appears in the DB, and the ID if there is an appearance
      $query = 'select id, count(*) from links where url="' . mysql_real_escape_string(htmlspecialchars($postedURL)) . '" or hash="' . $pageSourceHash . '"';
      $result = mysql_fetch_assoc(mysql_query($query));
      $numSites = $result['count(*)'];
      $id = $result['id'];
      if($numSites == 0)
      {
        // New site, new link(s) - add the site into the database
        $query = "insert into links(url,numLinks,hash) values (\"" . mysql_real_escape_string(htmlspecialchars($postedURL)) . "\",$numLinks,\"$pageSourceHash\")";
        mysql_query($query);
        $returnMe = True;
      }
      else
      {
        $query = "select numLinks from links where id=" . $result['id'];
        $result = mysql_fetch_assoc(mysql_query($query));
        if ($result['numLinks'] < $numLinks)
        {
          $returnMe = True;
        }
        else
        {
          $returnMe = 'It looks like all of the links to <a href="' . $linkConfig['urlsToShow'][0] . '">' . $linkConfig['urlsToShow'][0] . '</a>';
          for ($n = 1; $n < count($linkConfig['urlsToShow']); $n++ )
          {
            $returnMe .= ' or <a href="' . $linkConfig['urlsToShow'][$n] . '">' . $linkConfig['urlsToShow'][$n] . '</a>';
          }
          $returnMe .= " at <a href=\"$postedURL\">$postedURL</a> have been used to download the album already...  maybe try posting it somewhere else?";
        }
        $query = "update links set numLinks=$numLinks,hash=\"$pageSourceHash\" where id=$id";
        mysql_query($query);
      }
      mysql_close($link);
      return $returnMe;
    }    
  }
  function option()
  {
    global $linkConfig;
    return "Post a link to " . $linkConfig['linkName'] . " in some publicly accessible location";
  }
  function description()
  {
    global $linkConfig;
    global $config;
    $URLsPlural = (count($linkConfig['urlsToShow']) > 1) ? 'one of the URLs' : 'the url';
    $description = '1) Copy this URL: <a href="' . $linkConfig['urlsToShow'][0] . '">' . $linkConfig['urlsToShow'][0] . '</a>';
    for ($n = 1; $n < count($linkConfig['urlsToShow']); $n++ )
    {
      $description .= ' or this URL: <a href="' . $linkConfig['urlsToShow'][$n] . '">' . $linkConfig['urlsToShow'][$n] . '</a>';
    }
    $description .= <<<EOD
<br />
2) Post it ANYWHERE that is accessible by EVERYONE - on your blog, twitter, message board, etc.  It has to be visible by someone who is not logged in or following you or your friend!  (so if you have your tweets or facebook statuses hidden, and you post it there, it won't work.)<br />
3) You can spice it up by writing something like "I'm about to download {$config['product']} from <a href="{$linkConfig['urlsToShow'][0]}">{$linkConfig['urlsToShow'][0]}</a>!"<br />
4) Post the address of whatever site you posted it on below!<br /><br />
</small>
<form action="" method="get">
<input type="text" name="postedURL" value="http://www.example.com" style="width: 400px" />
<input type="hidden" name="plugin" value="link" />
<input type="submit" value="Submit" style="float: right" /><br /><br />
</form>
<small>
EOD;
    return $description;
  }
  function success()
  {
    global $linkConfig;
    return "posting a link to " . $linkConfig['linkName'];
  }
  function shouldRun()
  {
    return (strcmp( $_GET['plugin'], 'link') == 0);
  }
  function name()
  {
    return "Link";
  }

}

$link = new Link;

$plugins[] = $link;

?>