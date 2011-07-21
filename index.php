<?

require_once('config.php');
require_once('display.php');
require_once('utility.php');

if (isset($_GET['download']))
{
  handleDownload();
}
else
{
  if(!pluginCallbacks())
  {
    displayHeader();
    showOptions();
  }
}
displayFooter();

?>