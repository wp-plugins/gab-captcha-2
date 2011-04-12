<?php header("Content-type: text/css") ?>label[for='commentturing'] span  {
	font-size: 0.6em;
}
<?php

$css = "";
if (isset($_GET['set']))
{
	echo "/* " . base64_decode(strip_tags($_GET['set'])) . "*/";
	$keylist = explode(",", base64_decode(strip_tags($_GET['set'])));
	foreach ($keylist as $value)
	{
		if ($value != "")
		{
			$css .= "label[for='commentturing'] span:nth-child({$value}) {\n";
			$css .= "\tcolor: #f00;\n";
			$css .= "\tfont-weight: bold;\n";
			$css .= "\tfont-size: 1.4em;\n";
			$css .= "}\n";
		}
	}
}
echo $css;

?>