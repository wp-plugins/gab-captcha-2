<?php header( 'Content-type: text/css' ) ?>label[for='commentturing'] span {
	font-size: 0.6em;
}
<?php

$usecss3 = false;
if( isset($_GET['method'] ) )
{
	if( $_GET['method'] == 'css3' )
	{
		$usecss3 = true;
	}
}

$css = '';
if( isset( $_GET['set'] ) )
{
	//uncomment next line for debugging purposes
	//echo "/* " . base64_decode(strip_tags($_GET['set'])) . "*/\n";

	$keylist = explode( ',', base64_decode( strip_tags( $_GET['set'] ) ) );
	foreach ($keylist as $value)
	{
		if( $value !== '' )
		{
			if( $usecss3 )
			{
				$realvalue = intval( $value ) + 1;
				$css .= "label[for='commentturing'] span:nth-child({$realvalue}) {\n";
			}
			else
			{
				$css .= "label[for='commentturing'] .gc2_{$value} {\n";
			}
			$css .= "\tcolor: #f00;\n";
			$css .= "\tfont-weight: bold;\n";
			$css .= "\tfont-size: 1.4em;\n";
			$css .= "}\n";
		}
	}
}
echo $css;

?>