<?php
/*
Plugin Name: Gab Captcha 2
Plugin URI: http://www.gabsoftware.com/products/scripts/gabcaptcha2/
Description: Efficient and simple captcha plugin for Wordpress comments.
Author: Gabriel Hautclocq
Version: 1.0.13
Author URI: http://www.gabsoftware.com
Tags: comments, spam, captcha, turing, test, challenge
*/


//error_reporting(E_ALL);

// security check
if ( !defined( 'WP_PLUGIN_DIR') )
{
	die("There is nothing to see here.");
}


/* global variables */
$gabcaptcha2_plugin_dir = WP_PLUGIN_DIR .'/' .plugin_basename(dirname(__FILE__));
$gabcaptcha2_plugin_url = WP_PLUGIN_URL .'/' .plugin_basename(dirname(__FILE__));

$gabcaptcha2_version_maj = 1;
$gabcaptcha2_version_min = 0;
$gabcaptcha2_version_rev = 13;
$gabcaptcha2_version = "{$gabcaptcha2_version_maj}.{$gabcaptcha2_version_min}.{$gabcaptcha2_version_rev}";


// Returns the value of the specified option
function gabcaptcha2_get_option($name)
{
	$options = get_option('gabcaptcha2_options');
	if ( isset( $options[$name] ) )
	{
		return $options[$name];
	}
	else
	{
		return FALSE;
	}
}

// Sets the value of the specified option
function gabcaptcha2_set_option($name, $value)
{
	$options = get_option('gabcaptcha2_options');
	$options[$name] = $value;

	return update_option('gabcaptcha2_options', $options );
}

//get all or part of the version of GabCaptcha2
function gabcaptcha2_get_version($what = 'all')
{
	global $gabcaptcha2_version;

	$version = get_option('gabcaptcha2_version');

	if ( $version === FALSE || !isset($version) || empty($version) )
	{
		$version = '1.0.11';
	}

	switch( $what )
	{
		case 'all':
			return $version;
			break;
		case 'major':
			$version_array = explode('.', $version);
			return $version_array[0];
			break;
		case 'minor':
			$version_array = explode('.', $version);
			return $version_array[1];
			break;
		case 'revision':
			$version_array = explode('.', $version);
			return $version_array[2];
			break;
		default:
			return $version;
	}
}


/*
 * Set the Language
 */
function gabcaptcha2_setlang()
{
	global $gabcaptcha2_plugin_dir;
	load_plugin_textdomain( 'gabcaptcha2', $gabcaptcha2_plugin_dir . "/lang", plugin_basename(dirname(__FILE__)) . '/lang' );
}

/*
 * Escape a string so that it can be used in Javascript code
 */
function gabcaptcha2_escapestringjs($str)
{
	return strtr($str, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
}



/*
 * Return a random string composed of alphabet characters
 */
function gabcaptcha2_str_rand()
{
	$seeds = 'abcdefghijklmnopqrstuvwqyz';
	$length = 8;
	// Seed generator
	list($usec, $sec) = explode(' ', microtime());
	$seed = (float) $sec + ((float) $usec * 100000);
	mt_srand($seed);

	// Generate
	$str = '';
	$seeds_count = strlen($seeds);

	for ($i = 0; $length > $i; $i++)
	{
		$str .= $seeds{mt_rand(0, $seeds_count - 1)};
	}

	return $str;
}


/*
 *  Start session
 */
session_start();
if (!isset($_SESSION['gabcaptcha2_id']) or !isset($_SESSION['gabcaptcha2_session']))
{
	$_SESSION['gabcaptcha2_id'] = gabcaptcha2_str_rand();
	$_SESSION['gabcaptcha2_session'] = gabcaptcha2_str_rand();
}

if (!isset($_SESSION['gabcaptcha2_comment_status']))
{
	$_SESSION['gabcaptcha2_comment_status'] = "normal";
}




/*
 * Instanciate a new instance of GabCaptcha2
 */
new GabCaptcha2();



class GabCaptcha2
{

	private $letters;
	private $captchalength;
	private $captchatopick;
	private $captcha;
	private $validkeys;
	private $validanswer;
	private $gabcaptchaoutput;
	private $gabcaptchaoutput2;
	private $gabcaptchaoutput3;
	private $keylist64;
	private $failedturing;
	private $inserted;
	private $gabcaptcha2_options;



	function GabCaptcha2()
	{
		$this->__construct();
	} // function

	function __construct()
	{
		// Place your add_actions and add_filters here

		gabcaptcha2_setlang();


		$this->letters = Array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
		$this->captchalength     = gabcaptcha2_get_option( 'captcha_length' );
		$this->captchatopick     = gabcaptcha2_get_option( 'captcha_solution_length' );
		$this->captcha           = $this->gabcaptcha2_generate($this->letters, $this->captchalength);
		$this->validkeys         = $this->gabcaptcha2_pickvalid($this->captcha, $this->captchatopick);
		$this->validanswer       = $this->gabcaptcha2_getanswer($this->captcha, $this->validkeys);
		$this->gabcaptchaoutput  = $this->gabcaptcha2_display($this->captcha, $this->validkeys);
		$this->gabcaptchaoutput2 = $this->gabcaptcha2_display2($this->captcha, $this->validkeys);
		$this->gabcaptchaoutput3 = $this->gabcaptcha2_display3($this->captcha, $this->validkeys);
		$this->keylist64         = $this->gabcaptcha2_keylist($this->captcha, $this->validkeys);
		$this->failedturing      = true;
		$this->inserted          = FALSE;

		$this->should_install();


		if( is_admin() )
		{
			//include admin-related files
			require_once("gabcaptcha2_admin.php");

			$gabcaptcha2_options = new GabCaptcha2_Options();
		}


		add_action('init',              array( &$this, 'gabcaptcha2_init_callback') );
		add_action('wp_insert_comment', array( &$this, 'gabcaptcha2_insert_comment_callback'), 10, 2 );
		add_action('comment_form',      array( &$this, 'gabcaptcha2_comment_form_callback') );

		//add_action('wp_print_styles',   array( &$this, 'gabcaptcha2_add_stylesheet_callback') );
		add_action('preprocess_comment',   array( &$this, 'gabcaptcha2_preprocess_comment'), 10, 1 );

	} // function


	public function gabcaptcha2_init_callback()
	{
		global $gabcaptcha2_plugin_dir;

		if(function_exists('load_plugin_textdomain'))
		{
			load_plugin_textdomain( 'gabcaptcha2', $gabcaptcha2_plugin_dir, plugin_basename(dirname(__FILE__)));
		}

		add_action( 'wp_print_styles', array( &$this, 'gabcaptcha2_add_stylesheet_callback' ) );

	} // function




	//check is gabcaptcha2 should be installed or upgraded
	function should_install()
	{
		global $gabcaptcha2_version_maj;
		global $gabcaptcha2_version_min;
		global $gabcaptcha2_version_rev;

		$majver = gabcaptcha2_get_version('major');
		$minver = gabcaptcha2_get_version('minor');
		$revver = gabcaptcha2_get_version('revision');


		if ($majver != $gabcaptcha2_version_maj || $minver != $gabcaptcha2_version_min || $revver != $gabcaptcha2_version_rev)
		{
			$this->install( $gabcaptcha2_version_maj, $gabcaptcha2_version_min, $gabcaptcha2_version_rev );
		}
	}



	function install($vermajor, $verminor, $verrevision)
	{
		global $gabcaptcha2_version;


		$majver = gabcaptcha2_get_version('major');
		$minver = gabcaptcha2_get_version('minor');
		$revver = gabcaptcha2_get_version('revision');

		global $wpdb;

		$table_name = $wpdb->prefix . "gabcaptchasecret";

		if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name)
		{

			$sql = "CREATE TABLE " . $table_name . " (
				  id mediumint(9) NOT NULL AUTO_INCREMENT,
				  SECRET varchar(64) NOT NULL,
				  UNIQUE KEY id (id)
				);";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);

			$rows_affected = $wpdb->insert( $table_name, array( 'secret' => "TABLE CREATED ON DATE : " . current_time('mysql') ) );
		}

		if( $majver == 1 )
		{
			if ( $minver == 0 )
			{
				if ( $revver < 12 )
				{
					//set the new options array
					$gabcaptcha2_options['display_credits']         = get_option( 'gc_show_credit' );
					$gabcaptcha2_options['automatically_approve']   = ( get_option( 'gc_automatically_approve' ) == 'yes' ? 'on' : 'off' );
					$gabcaptcha2_options['captcha_label']           = get_option( 'gc_captcha_text' );
					$gabcaptcha2_options['captcha_length']          = get_option( 'gc_captcha_length' );
					$gabcaptcha2_options['captcha_solution_length'] = get_option( 'gc_captcha_to_pick' );
					$gabcaptcha2_options['output_method']           = get_option( 'gc_method' );

					//add the new options array
					add_option( 'gabcaptcha2_options', $gabcaptcha2_options );
					add_option( 'gabcaptcha2_version', $gabcaptcha2_version );

					//delete the old options
					delete_option( 'gc_show_credit' );
					delete_option( 'gc_automatically_approve' );
					delete_option( 'gc_captcha_text' );
					delete_option( 'gc_captcha_length' );
					delete_option( 'gc_captcha_to_pick' );
					delete_option( 'gc_method' );

					//delete options found to be unused
					delete_option( 'gc_css_only' );
					delete_option( 'gc_lang' );
				}
			}
		}

	} //function









	function gabcaptcha2_generate($letters, $captchalength)
	{
		$res = "";
		for ($i=0; $i<$captchalength; $i++)
		{
			$rand_key = array_rand($letters);
			$res .= $letters[$rand_key];
		}
		return $res;
	} //function

	function gabcaptcha2_pickvalid($captcha, $captchatopick)
	{
		$res = Array();
		$input = str_split($captcha);
		for ($i=0; $i<$captchatopick; $i++)
		{
			if ($i>0)
			{
				$found = true;
				while ($found)
				{
					$found = false;
					$key = array_rand($input);
					for ($j=0; $j<=$i; $j++)
					{
						if ( isset( $res[$j] ) )
						{
							if ($key == $res[$j])
							{
								$found = true;
								break;
							}
						}
					}
				}
			}
			else
			{
				$key = array_rand($input);
			}
			$res[$i] = $key;
		}
		sort($res);
		return $res;
	} //function

	function gabcaptcha2_getanswer($captcha, $validkeys)
	{
		$answer = "";
		for ($i=0; $i<count($validkeys); $i++)
		{
			$answer .= $captcha[$validkeys[$i]];
		}
		return $answer;
	} //function

	function gabcaptcha2_display($captcha, $validkeys)
	{
		$res = "";
		for ($i=0; $i<strlen($captcha); $i++)
		{
			$validkey = false;
			for ($j=0; $j<count($validkeys); $j++)
			{
				if ($validkeys[$j] == $i)
				{
					$validkey = true;
					break;
				}
			}
			if ($validkey)
			{
				$res .= "<strong class=\"gabcaptchav\">{$captcha[$i]}</strong>";
			}
			else
			{
				$res .= "<span class=\"gabcaptchai\">{$captcha[$i]}</span>";
			}
		}
		return $res;
	} //function

	function gabcaptcha2_display2($captcha, $validkeys)
	{
		$res = "";
		for ($i=0; $i<strlen($captcha); $i++)
		{
			$res .= "<span class=\"gc2_{$i}\">{$captcha[$i]}</span>";
		}
		return $res;
	} //function

	function gabcaptcha2_display3($captcha, $validkeys)
	{
		$res = "";
		for ($i=0; $i<strlen($captcha); $i++)
		{
			$res .= "<span>{$captcha[$i]}</span>";
		}
		return $res;
	} //function

	function gabcaptcha2_keylist($captcha, $validkeys)
	{
		$res = "";
		for ($i=0; $i<strlen($captcha); $i++)
		{
			$validkey = false;
			for ($j=0; $j<count($validkeys); $j++)
			{
				if ($validkeys[$j] == $i)
				{
					$validkey = true;
					break;
				}
			}
			if ($validkey)
			{
				$res .= $i . ",";
			}
		}
		return base64_encode($res);
	} //function



	/*
	 * Add CSS into the head
	 */
	public function gabcaptcha2_add_stylesheet_callback()
	{
		global $gabcaptcha2_plugin_dir;
		global $gabcaptcha2_plugin_url;

		$gabcaptcha2_style_url = $gabcaptcha2_plugin_url . '/style.css';
		$gabcaptcha2_style_file = $gabcaptcha2_plugin_dir . '/style.css';
		if ( file_exists($gabcaptcha2_style_file) ) {
			wp_register_style('gabcaptcha2_stylesheet_std', $gabcaptcha2_style_url);
			wp_enqueue_style( 'gabcaptcha2_stylesheet_std');
		}
		else
		{
			printf( __("%s does not exist", 'gabcaptcha2'), $gabcaptcha2_style_file);
		}

		//$gc_method = get_option('gc_method');
		$gc_method = gabcaptcha2_get_option( 'output_method' );
		if ($gc_method == 'css')
		{
			$gabcaptcha2_style_url = $gabcaptcha2_plugin_url . '/emphasis.php?set=' . $this->keylist64;
			$gabcaptcha2_style_file = $gabcaptcha2_plugin_dir . '/emphasis.php';
			if ( file_exists($gabcaptcha2_style_file) ) {
				wp_register_style('gabcaptcha2_stylesheet_css', $gabcaptcha2_style_url);
				wp_enqueue_style( 'gabcaptcha2_stylesheet_css');
			}
			else
			{
				printf( __("%s does not exist", 'gabcaptcha2'), $gabcaptcha2_style_file);
			}
		}
		else if ($gc_method == 'css3')
		{
			$gabcaptcha2_style_url = $gabcaptcha2_plugin_url . '/emphasis.php?method=css3&amp;set=' . $this->keylist64;
			$gabcaptcha2_style_file = $gabcaptcha2_plugin_dir . '/emphasis.php';
			if ( file_exists($gabcaptcha2_style_file) ) {
				wp_register_style('gabcaptcha2_stylesheet_css3', $gabcaptcha2_style_url);
				wp_enqueue_style( 'gabcaptcha2_stylesheet_css3');
			}
			else
			{
				printf( __("%s does not exist", 'gabcaptcha2'), $gabcaptcha2_style_file);
			}
		}

	} //function












	//check if a valid solution was given
	function gabcaptcha2_check_valid()
	{
		global $wpdb;

		if( !empty($_POST) )
		{
			// was there a GabCaptcha response ?
			if ( !empty( $_POST["CommentTuring"]) && !empty( $_POST["CommentSecret"] ) )
			{
				if (md5(strtoupper($_POST["CommentTuring"])) == base64_decode($_POST["CommentSecret"]))
				{
					$secret = base64_decode($_POST["CommentSecret"]);

					$table_name = $wpdb->prefix . "gabcaptchasecret";
					$reqcnt = $wpdb->prepare("SELECT COUNT(SECRET) AS NB FROM " . $table_name . " WHERE SECRET='%s'", $secret);
					$numrows = 0;
					$cntrow = $wpdb->get_row($reqcnt);
					$numrows = $cntrow->NB;

					//s'il y a 0 résultat, on peut ajouter le notre
					if ($numrows == 0)
					{
						$this->inserted = $wpdb->insert( $table_name, array('secret' => $secret));
						$this->failedturing = false;
					}
					else
					{
						$this->failedturing = true;
					}
				}
				else
				{
					// Failed... Sorry
					$this->failedturing = true;
				}
			}
			else
			{
				// Failed... Sorry
				$this->failedturing = true;
			}
		}

		if ($this->failedturing == true)
		{
			$_SESSION['gabcaptcha2_comment_status'] = "failed";
		}
		else
		{
			$_SESSION['gabcaptcha2_comment_status'] = "passed";
		}

		return $this->failedturing;
	} //function



















	function gabcaptcha2_preprocess_comment( $commentdata )
	{
		//check if a valid solution was given
		$this->gabcaptcha2_check_valid();

		if ($_SESSION['gabcaptcha2_comment_status'] == "passed")
		{
			//remove the flood check if a valid solution has been provided
			remove_filter('check_comment_flood', 'check_comment_flood_db');
			remove_filter('comment_flood_filter', 'wp_throttle_comment_flood');
		}

		return $commentdata;
	}
















	public function gabcaptcha2_insert_comment_callback( $id, $comment )
	{
		global $user_ID;
		global $gabcaptcha2_plugin_dir;

		if ($user_ID)
		{
			return $id;
		}

		if ($_SESSION['gabcaptcha2_comment_status'] == "failed")
		{

			//wp_set_comment_status( $id, "spam" );

			// use a file as marker for later use
			$failedfile = $gabcaptcha2_plugin_dir . "/failed.txt";
			$fh = fopen($failedfile, 'a');
			$stringData = $_SESSION['gabcaptcha2_session'] . "-<(SEPARATOR)>-" . $_POST['comment'];
			fwrite($fh, $stringData);
			fclose($fh);

			//delete the comment to avoid a "you already said that" message.
			wp_delete_comment( $id );

		}
		else
		{
			//if (get_option('gc_automatically_approve') == 'yes')
			if (gabcaptcha2_get_option('automatically_approve') == 'on')
			{
				wp_set_comment_status( $id, "approve" );
			}
		}



	} //function



	public function gabcaptcha2_comment_form_callback($id)
	{
		global $user_ID;
		global $gabcaptcha2_plugin_dir;
		global $gabcaptcha2_version;

		if ($user_ID)
		{
			return $id;
		}

		$gc_method = gabcaptcha2_get_option( 'output_method' );
		if ($gc_method == 'css')
		{
			$gc_final_output = $this->gabcaptchaoutput2;
		}
		else if ($gc_method == 'css3')
		{
			$gc_final_output = $this->gabcaptchaoutput3;
		}
		else
		{
			$gc_final_output = $this->gabcaptchaoutput;
		}

		$failedfile = $gabcaptcha2_plugin_dir . "/failed.txt";
		$failedprevious = file_exists( $failedfile );
		$failedcommentdata = "";
		$gabcaptcha2_session = "";
		if ($failedprevious)
		{
			$failedfiledata = explode("-<(SEPARATOR)>-", file_get_contents($failedfile), 2);
			$gabcaptcha2_session = $failedfiledata[0];
			$failedcommentdata   = $failedfiledata[1];

			if ($gabcaptcha2_session != $_SESSION['gabcaptcha2_session'])
			{
				$failedcommentdata = "";
			}

			unlink($failedfile);
		}

		$show_credit = gabcaptcha2_get_option( 'display_credits' );
		$gc_captcha_text = gabcaptcha2_get_option( 'captcha_label' );
		$gc_captcha_length = gabcaptcha2_get_option( 'captcha_length' );

		?>

		<fieldset id="<?php echo $_SESSION['gabcaptcha2_id'];?>" class="gabcaptchafs"></fieldset>
		<noscript><p class="gabcaptchajd"><?php _e("Our antispam protection requires that you enable JavaScript in your browser to be able to comment!", 'gabcaptcha2'); ?></p></noscript>
		<script type="text/javascript">
		/* <![CDATA[ */

		function gabcaptcha2_getElementByIdUniversal( id )
		{
			var elem = null;
			if(document.getElementById)
			{
				elem = document.getElementById( id );
			}
			else
			{
				elem = document.all[ id ];
			}
			return elem;
		}

		var commentField = gabcaptcha2_getElementByIdUniversal("url");
		if(commentField==null)
		{
			//maybe we disabled the url field
			commentField = gabcaptcha2_getElementByIdUniversal("email");
		}
		if(commentField==null)
		{
			//maybe we disabled the email field also
			commentField = gabcaptcha2_getElementByIdUniversal("author");
		}
		if(commentField==null)
		{
			//we try with the tag names...
			fields = document.getElementsByTagName("url");
			if (fields.length > 0)
			{
				commentField = fields[0];
			}
			else
			{
				fields = document.getElementsByTagName("email");
				if (fields.length > 0)
				{
					commentField = fields[0];
				}
				else
				{
					fields = document.getElementsByTagName("author");
					if (fields.length > 0)
					{
						commentField = fields[0];
					}
				}
			}
		}

		var submitp = commentField.parentNode;
		var answerDiv = document.getElementById("<?php echo $_SESSION['gabcaptcha2_id']; ?>");
		answerDiv.innerHTML = '<legend><?php echo gabcaptcha2_escapestringjs( __("Anti-spam protection", 'gabcaptcha2') ); ?></legend>'
		+ '<!-- Turing test using Gab Captcha 2 v<?php echo $gabcaptcha2_version; ?> (http://www.gabsoftware.com/products/scripts/gabcaptcha2/) -->'
		+ '<p><?php echo gabcaptcha2_escapestringjs($gc_captcha_text); ?></p>'
		+ '<label for="commentturing"><?php echo $gc_final_output; ?></label>'
		+ '<input type="text" id="commentturing" name="CommentTuring" maxlength="<?php echo $gc_captcha_length; ?>" class="textField" /><br />'
		+ '<input type="hidden" id="commentsecret" name="CommentSecret" value="<?php echo base64_encode(md5($this->validanswer)) ?>" />'
		+ '<?php if ($failedprevious && $failedcommentdata != "" ): ?>'
		+ '<p class="gabcaptchaer"><?php echo gabcaptcha2_escapestringjs(__("You failed the test. Try again!", 'gabcaptcha2')); ?></p>'
		+ '<?php endif; ?>'
		+ '<?php if($show_credit == 1):?><br />'
		+ '<a class="gabcaptchalc" title="<?php echo gabcaptcha2_escapestringjs(sprintf(__("Gab Captcha 2 v%s", 'gabcaptcha2'), $gabcaptcha2_version)); ?>" href="<?php _e("http://www.gabsoftware.com/products/scripts/gabcaptcha2/", 'gabcaptcha2'); ?>"><?php echo gabcaptcha2_escapestringjs(__("Gab Captcha 2 &copy; GabSoftware", 'gabcaptcha2')); ?></a>'
		+ '<?php elseif ($show_credit == 2):?><br />'
		+ '<span class="gabcaptchalc" title="<?php echo gabcaptcha2_escapestringjs(sprintf(__("Gab Captcha 2 v%s", 'gabcaptcha2'), $gabcaptcha2_version)); ?>"><?php echo gabcaptcha2_escapestringjs(__("Protected by <strong>Gab Captcha 2</strong>")); ?></span>'
		+ '<?php endif;?>';
		submitp.appendChild(answerDiv, commentField);
		<?php
		if ($failedprevious && $failedcommentdata != "")
		{
ECHO <<<END

	var commentArea = document.getElementById("comment");
	if(commentArea==null)
	{
		commentArea = document.getElementsByName("comment");
	}

END;
			echo "	commentArea.innerHTML = '" . gabcaptcha2_escapestringjs($failedcommentdata) . "';\n";
			echo "	window.location.hash = '#" . $_SESSION['gabcaptcha2_id'] . "';\n";
		}
		?>

		/* ]]> */
		</script>
		<?php
	} //function

} //class

?>
