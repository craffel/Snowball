<?

// Get the config file dir
$configDir = getcwd() . '/../../config.php';
include( $configDir );

global $config;


// Make sure it's been configured
if ( empty($config['db']) || empty($config['dbUser']) || empty($config['dbPass']) || empty($config['dbHost']) )
{
  ?>
    The config.php file has not been filled out.  Please fill in the db, dbUser, dbPass, and dbHost entries in config.php and try again.
  <?
}
else
{
  $link = mysql_connect($config['dbHost'], $config['dbUser'], $config['dbPass']) or die("Database connect failed, you probably need to set up db.php and your mysql database.");
  mysql_select_db($config['db'], $link) or die ("Couldn't select the database.");
  $query = "create table if not exists links (id int(50) not null auto_increment primary key, url text, numLinks int(11), hash varchar(32))";
  if (mysql_query($query))
  {
    echo "Created table...<br />";
    echo "The links plugin should be ready to go, please delete this file (install.php) IMMEDIATELY.";
  }
  else
  {
    echo "Error creating the table.  You can try doing this manually with the query \"$query;\".  The error was " . mysql_error();
    exit();
  }
}

?>