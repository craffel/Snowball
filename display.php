<?

require_once('config.php');
global $product;

// Displays a download option, given the link and the description from the plugin
function displayOption( $name, $link, $description )
{
?>
  -&nbsp;<a href="#" onclick="enable('div<?=$name?>');"><?=$link?></a><br />
  <div id="div<?=$name?>" class="downloadOption" style="visibility:hidden; display:none;">
  <small>
  <?=$description?>
  </small>
  </div>
<?
}

function displayHeader()
{
global $config;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>Download <?=$config['product']?></title>
<link rel="stylesheet" type="text/css" href="style.css" />
<script type="text/javascript">
function enable(id)
{
  if (document.getElementById(id).style.visibility=="hidden")
  {
    document.getElementById(id).style.visibility="visible";
    document.getElementById(id).style.display="block";
  }
  else
  {
    document.getElementById(id).style.visibility="hidden";
    document.getElementById(id).style.display="none";
  }
}
</script>
</head>
<body>
<div id="main">
<div id="header">
  <!-- Default header photo by Code Porche, licensed under a CC BY-NC-ND 2.0 license.
  http://www.flickr.com/photos/ehcropydoc/3251524733/sizes/l/in/photostream/
  http://creativecommons.org/licenses/by-nc-nd/2.0/
  //-->
  <img src="images/header.jpg" style="width: 100%; height: 100%" alt="header"/>
</div>
<br />
<?
}

function displayFooter()
{
global $config;
?>
<br />
<? if ( isset($config['email']) ) { ?> <small>Please report any issues to <?=$config['email']; ?>.</small><? } ?>
</div>
<div id="footer">
  Powered by <a href="http://www.colinraffel.com/software/snowball">snowball</a>.
</div>
</body>
</html>
<?
}



?>