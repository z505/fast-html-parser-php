<?php
require("parser.php");

/* Testing */
$parser = new htmlparser_class;
$parser->LoadHTML("test.html");
$parser->Parse();
$result=$parser->GetElements(&$htmlcode);
if ($result)
{
  while (list($key, $code) = each ($htmlcode))
  {
    echo $key."-".htmlentities($code)."<BR>";
  }
} else
{
  echo "Error";
}
?>

