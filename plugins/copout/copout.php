<?

// This is just a simple way to display something if they don't want to download

class Copout extends Plugin
{
  function callback()
  {
    return False;
  }
  function option()
  {
    return "The cop-out";
  }
  function description()
  {
    $description = <<<EOD
Put some HTML copout here.
EOD;
    return $description;
  }
  function success()
  {
    return '';
  }
  function shouldRun()
  {
    return False;
  }
  function name()
  {
    return '';
  }
}

$copout = new Copout;

$plugins[] = $copout;


?>