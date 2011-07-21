<?

require_once('config.php');

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
  $query = "create database if not exists " . $config['db'];
  if( mysql_query($query) )
  {
    echo "Created database...<br />";
  }
  else
  {
    echo "Error creating the database.  You can try doing this manually with the query \"$query;\".  The error was " . mysql_error();
    exit();
  }
  mysql_select_db($config['db'], $link) or die ("Couldn't select the database. This is a problem - please try creating the database manually.");
  $query = "create table if not exists codes (id int(50) not null auto_increment primary key, code varchar(6), used int(1), createTime datetime, pluginUsed varchar(255))";
  if (mysql_query($query))
  {
    echo "Created table...<br />";
    echo "Snowball should be ready to go, please delete this file (install.php) IMMEDIATELY.";
  }
  else
  {
    echo "Error creating the table.  You can try doing this manually with the query \"$query;\".  The error was " . mysql_error();
    exit();
  }
}


?>