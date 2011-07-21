<?

global $config;
// The URL of the download site.  Eg, http://yoursite.com/snowballliveshere
$config['url'] = "http://yoursite.com/snowballliveshere";
// The "product" being offered/downloaded
$config['product'] = "My great new thing";
// Your email, for contacting you.  Feel free to leave as "" if you don't want to include it
// and feel free to use any spambot obfuscation techniques (this just gets echo'd)
// See, eg, http://csarven.ca/hiding-email-addresses
$config['email'] = '<a href="mailto:your@email.com">your@email.com</a>';
// The filename (residing in files) which is to be downloaded.
// Say you uploaded mygreatthing.zip to files, then this should be mygreatthing.zip
// Note that this MUST reside in the files directory!
$config['file'] = "file.zip";
// The Content-type of the file - eg, for a .zip it would be application/zip
// For a list, see here: http://www.w3schools.com/media/media_mimeref.asp
$config['contentType'] = "application/zip";
// The filename you want the file to be downloaded as
// This can certainly be the same as $config['file'],
// but you may want to store it on the server as file.zip and have it download as My Great New THING.zip
$config['downloadName'] = "My Great New THING.zip";
// The host for the mysql database
$config['dbHost'] = "localhost";
// The database name
$config['db'] = "";
// The user to log into the database with
$config['dbUser'] = "";
// The database user's password
$config['dbPass'] = "";
// The domain snowball lives at.  Should be something like "google.com", not "www.google.com".
$config['domain'] = "colinraffel.com";

?>