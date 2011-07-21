<?

require_once("plugins/plugins.php");
require_once("display.php");
require_once("config.php");

// Run all plugin callbacks... returns True if a callback was successful and the download link was displayed
function pluginCallbacks()
{
  global $plugins;
  global $config;
  $success = False;
  // Cycle through plugins...
  if (count($plugins) > 0)
  {
    foreach ( $plugins as $plugin )
    {
      // Should it run?
      if ($plugin->shouldRun())
      {
        $success = True;
        $callbackResult = $plugin->callback();
        // If the plugin's callback returns true
        if ($callbackResult === True)
        {
          displayHeader();
          global $config;
          $code = addCode( $plugin->name() )
          ?>
            Thanks for <?=$plugin->success()?>!  To download <?=$config['product']?>, click <a href="<?=$config['url']?>?download=<?=$code?>">here</a>. (Please note - this download link will only work once!)<br />
          <?
        }
        else
        {
          displayHeader();
          ?>
            Sorry, an error occurred: <?=$callbackResult?><br /><br />
            <a href="<?=$config['url']?>">Try again</a><? if ( isset($config['email']) ) { ?> or report a bug to <?=$config['email']; }?>.<br />
          <?
        }
      }
    }
  }
  return $success;
}

// Show all the download options
function showOptions()
{
  global $config;
  global $plugins;
  if (count($plugins) > 0)
  {
    ?>TO DOWNLOAD "<?=$config['product']?>", you gotta do me a favor!  <?= count($plugins) > 1 ? "You can either:" : "" ?><br /><?
    // Keep track of the number of options displayed
    $option = 0;
    foreach ($plugins as $plugin)
    {
      displayOption( $option, $plugin->option(), $plugin->description() );
      $option++;
    }
  }
  else
  {
    ?>Sorry, no plugins are installed.<br /><?
  }
}

function addCode( $pluginUsed )
{
  global $config;
  $link = mysql_connect($config['dbHost'], $config['dbUser'], $config['dbPass']) or die("Database connect failed, you probably need to set up db.php and your mysql database.");
  mysql_select_db($config['db'], $link) or die ("Couldn't select the database.  You probably need to set up config.php and your mysql database.");
  // Characters to generate the code from
  $characters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  // Generate length-5 code from the characters above
  // 55^5 ~= 500,000,000
  // But we don't want any bad words
  $badWords = array('shit','piss','fuck','cunt','cock','suck','dick','bitch','anus','ass','butt','fag','dyke','boob'.'tit','peni','clit');
  // We want to keep generating codes until we make one which doesn't exist
  while (1)
  {
    // Generate the code randomly from the list of characters
    $code = '';
    for ($i = 0; $i < 5; $i++)
    {
      $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    foreach ($badWords as $badWord)
    {
      if (stristr($code, $badWord))
      {
        continue 2;
      }
    }
    // Check if it exists already
    $query = 'select COUNT(*) from codes where code = "' . $code . '"';
    $result = mysql_query($query);
    $total = mysql_fetch_array($result);
    $total = $total[0];
    if ($total == 0)
    {
      break;
    }
  }
  // Add new download code entry
  $query = 'insert into codes(code, used, createTime, pluginUsed) values("' . $code . '", 0, NOW(), "' . $pluginUsed . '")';
  mysql_query($query);
  mysql_close($link);
  return $code;
}

function handleDownload()
{
  global $config;
  $link = mysql_connect($config['dbHost'], $config['dbUser'], $config['dbPass']) or die("Database connect failed, you probably need to set up db.php and your mysql database.");
  mysql_select_db($config['db'], $link) or die ("Couldn't select the database.  You probably need to set up config.php and your mysql database.");
  $query = "select used from codes where code=\"" . mysql_real_escape_string($_GET['download']) . "\"";
  $result = mysql_query($query);
  if (mysql_num_rows($result) == 0)
  {
    displayHeader();
    echo 'Uh oh - that download code doesn\'t seem to exist...  <a href="' . $config['url'] .'">Try again?</a><br />';  
  }
  else
  {
    $used = mysql_fetch_assoc($result);
    $used = $used['used'];
    if ($used)
    {
      displayHeader();
      echo 'Uh oh - that download code has already been used. <a href="' . $config['url'] . '">Try again?</a><br />';        
    }
    else
    { 
      $query = "update codes set used=1 where code=\"" . mysql_real_escape_string($_GET['download']) . "\"";
      mysql_query($query);
      header("Cache-Control: public");
      header("Content-Description: File Transfer");
      header("Content-Disposition: attachment; filename=".preg_replace("/[^a-zA-Z0-9\. ]/", "",$config['downloadName']));
      header("Content-Type: " . $config['contentType']);
      header("Content-Transfer-Encoding: binary");
      readfile("files/" . $config['file']);
    }
  }
  mysql_close($link);
}


?>