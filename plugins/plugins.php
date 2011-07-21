<?

// The class for plugins - all plugins should extend this class.
abstract class Plugin
{
  // The callback - this is where most of the functionality should be, it is run whenever the plugin shouldRun
  // If it returns True, a download link will be generated
  // If it returns ANYTHING OTHER THAN TRUE (including 1, "It worked", etc), the return value will be printed to the user as an error message.
  abstract function callback();
  // This is the "option" that gets displayed on the download page, eg "Like our page on facebook"
  abstract function option();
  // This is the description that gets displayed on the download page, eg "Click here to log into facebook and like our page"
  abstract function description();
  // This is the action that the user carried out on a successful call back, eg "liking our page on facebook"
  // It's displayed in the context of "Thanks for ___________!"
  abstract function success();
  // This function determines whether the callback should be run at all. (True = run, False = don't run)
  // Should probably not always evaluate to True!
  abstract function shouldRun();
  // The name of the plugin.  Should probably be the same as the class name.
  abstract function name();
}

// The global plugins array
global $plugins;

// Include all your plugins here.
include("facebook/facebook.php");
include("twitter/twitter.php");
include("lastfm/lastfm.php");
include("soundcloud/soundcloud.php");
include("link/link.php");
include("copout/copout.php");

?>