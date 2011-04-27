<?php
/*
Plugin Name: Gab Captcha 2
Plugin URI: http://www.gabsoftware.com/products/scripts/gabcaptcha2/
Description: Efficient and simple captcha plugin for Wordpress comments.
Author: Gabriel Hautclocq
Version: 1.0.7
Author URI: http://www.gabsoftware.com
Tags: comments, spam, captcha, turing, test
*/

/* global variables */
$gabcaptcha2_plugin_dir = WP_PLUGIN_DIR .'/' .plugin_basename(dirname(__FILE__));
$gabcaptcha2_plugin_url = WP_PLUGIN_URL .'/' .plugin_basename(dirname(__FILE__));




/*
 * Set the Language
 */
function gabcaptcha2_setlang()
{
	global $gabcaptcha2_plugin_dir;
	load_plugin_textdomain( 'gabcaptcha2', $gabcaptcha2_plugin_dir . "/lang", plugin_basename(dirname(__FILE__)).'/lang' );
}

/*
 * Escape a string so that it can be used in Javascript code
 */
function escapestringjs($str)
{
	return strtr($str, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
}



/*
 * Return a random string composed of alphabet characters
 */
function str_rand()
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
	$_SESSION['gabcaptcha2_id'] = str_rand();
	$_SESSION['gabcaptcha2_session'] = str_rand();
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



	function GabCaptcha2()
	{
		$this->__construct();
	} // function

	function __construct()
	{
		# Place your add_actions and add_filters here

		gabcaptcha2_setlang();


		$this->letters = Array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
		$this->captchalength     = get_option('gc_captcha_length');
		$this->captchatopick     = get_option('gc_captcha_to_pick');
		$this->captcha           = $this->gabcaptcha2_generate($this->letters, $this->captchalength);
		$this->validkeys         = $this->gabcaptcha2_pickvalid($this->captcha, $this->captchatopick);
		$this->validanswer       = $this->gabcaptcha2_getanswer($this->captcha, $this->validkeys);
		$this->gabcaptchaoutput  = $this->gabcaptcha2_display($this->captcha, $this->validkeys);
		$this->gabcaptchaoutput2 = $this->gabcaptcha2_display2($this->captcha, $this->validkeys);
		$this->gabcaptchaoutput3 = $this->gabcaptcha2_display3($this->captcha, $this->validkeys);
		$this->keylist64         = $this->gabcaptcha2_keylist($this->captcha, $this->validkeys);
		$this->failedturing      = true;
		$this->inserted          = FALSE;


		register_activation_hook(__FILE__, array( &$this, 'gabcaptcha2_install') );

		add_action('admin_init',        array( &$this, 'admin_init') );
		add_action('init',              array( &$this, 'init') );
		add_action('wp_insert_comment', array( &$this, 'gabcaptcha2_insert_comment'), 10, 2 );
		add_action('comment_form',      array( &$this, 'gabcaptcha2_comment_form') );
		add_action('admin_menu',        array( &$this, 'gabcaptcha2_add_menu') );
		add_action('wp_print_styles',   array( &$this, 'gabcaptcha2_add_stylesheet') );
		//add_action('comment_duplicate_trigger',   array( &$this, 'gabcaptcha2_comment_duplicate_trigger'), 10, 1 );
		//add_action('check_comment_flood',   array( &$this, 'gabcaptcha2_check_comment_flood'), 10, 3 );
		add_action('preprocess_comment',   array( &$this, 'gabcaptcha2_preprocess_comment'), 10, 1 );
		//add_action('wp_allow_comment',   array( &$this, 'gabcaptcha2_wp_allow_comment'), 10, 1 );


		//add_option('gc_lang', '1', '', 'yes');
		add_option('gc_show_credit',           '1', '', 'yes');
		add_option('gc_captcha_text',          __('Prove that you are Human by typing the emphasized characters:', 'gabcaptcha2'), '', 'yes');
		add_option('gc_captcha_length',        16, '', 'yes');
		add_option('gc_captcha_to_pick',       4, '', 'yes');
		add_option('gc_automatically_approve', 'no', '', 'yes');
		add_option('gc_method',                'std', '', 'yes');

	} // function

	function admin_init()
	{
		# perform your code here
	} // function

	function init()
	{
		# perform your code here
	} // function








	function gabcaptcha2_install ()
	{
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
						if ($key == $res[$j])
						{
							$found = true;
							break;
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













	function gabcaptcha2_options_page() {

		if($_POST['Submit'])
		{

			if (is_numeric($_POST['gc_captcha_length']) && is_numeric($_POST['gc_captcha_to_pick']))
			{
				if ($_POST['gc_captcha_length'] >=2 && $_POST['gc_captcha_length'] > $_POST['gc_captcha_to_pick'] && $_POST['gc_captcha_length'] <= 64) //64 is already quite long!
				{
					update_option('gc_captcha_length', escapestringjs($_POST['gc_captcha_length']));
				}

				if ($_POST['gc_captcha_to_pick'] >=1 && $_POST['gc_captcha_to_pick'] < $_POST['gc_captcha_length'] && $_POST['gc_captcha_to_pick'] <= 24) //24 is already very boring to use...
				{
					update_option('gc_captcha_to_pick', escapestringjs($_POST['gc_captcha_to_pick']));
				}
			}

			if ($_POST['gc_automatically_approve'] == "yes" || $_POST['gc_automatically_approve'] == "no")
			{
				update_option('gc_automatically_approve', $_POST['gc_automatically_approve']);
			}

			if ($_POST['gc_method'] == "std" || $_POST['gc_method'] == "css" || $_POST['gc_method'] == "css3")
			{
				update_option('gc_method', $_POST['gc_method']);
			}

			update_option('gc_show_credit', escapestringjs($_POST['gc_show_credit']));
			update_option('gc_captcha_text', escapestringjs($_POST['gc_captcha_text']));

			echo '<div class="updated"><p>' . __("Settings was successfully updated!") . '</p></div>';
		}
	?>
	<div class="wrap">
		<h2><?php _e("Gab Captcha 2 settings"); ?></h2>
		<p style="font-style: italic;"><?php _e("Now you can laugh at the bots!"); ?></p>

		<form method="post">
			<fieldset class="options">
				<legend style="font-weight: bold;">Options</legend>
				<?php
				$gc_show_credit = get_option('gc_show_credit');
				//$gc_lang = get_option('gc_lang');
				$gc_captcha_text = htmlspecialchars(stripslashes(get_option('gc_captcha_text')), ENT_QUOTES);
				$gc_captcha_length = get_option('gc_captcha_length');
				$gc_captcha_to_pick = get_option('gc_captcha_to_pick');
				$gc_automatically_approve = get_option('gc_automatically_approve');
				$gc_method = get_option('gc_method');
				?>
				<p>
					<label for="gc_show_credit"><?php _e("Display credits:"); ?></label>
					<select id="gc_show_credit" name="gc_show_credit" />
						<option value="1" <?php echo $gc_show_credit==1 ? 'selected' : ''; ?>><?php _e("As link"); ?></option>
						<option value="2" <?php echo $gc_show_credit==2 ? 'selected' : ''; ?>><?php _e("As plain text"); ?></option>
						<option value="3" <?php echo $gc_show_credit==3 ? 'selected' : ''; ?>><?php _e("Off"); ?></option>
					</select>
				</p>

				<fieldset style="margin-top: 20px;">
					<legend style="font-weight: bold;"><?php _e("Captcha label"); ?></legend>
					<input type="text" name="gc_captcha_text" style="width: 600px;" value="<?php echo empty($gc_captcha_text) ? __("Prove that you are Human by typing the emphasized characters:") : $gc_captcha_text; ?>" />
				</fieldset>

				<fieldset style="margin-top: 20px;">
					<legend style="font-weight: bold;"><?php _e("Captcha options"); ?></legend>

					<label for="gc_captcha_length" /><?php _e("Captcha length (2 to 64):"); ?></label>
					<input type="text" id="gc_captcha_length" name="gc_captcha_length" style="width: 100px;" value="<?php echo empty($gc_captcha_length) ? __("Captcha length (2 to 64):") : $gc_captcha_length; ?>" />

					<label for="gc_captcha_to_pick" /><?php _e("Solution length (1 to 24):"); ?></label>
					<input type="text" id="gc_captcha_to_pick" name="gc_captcha_to_pick" style="width: 100px;" value="<?php echo empty($gc_captcha_to_pick) ? __("Solution length (1 to 24):") : $gc_captcha_to_pick; ?>" />

					<br />
					<label for="gc_automatically_approve"><?php _e("Automatically approve comments who passed the test:"); ?></label>
					<select id="gc_automatically_approve" name="gc_automatically_approve" />
						<option value="yes"<?php echo $gc_automatically_approve=='yes' ? ' selected' : ''; ?>><?php _e("Yes"); ?></option>
						<option value="no"<?php echo $gc_automatically_approve=='no' ? ' selected' : ''; ?>><?php _e("No"); ?></option>
					</select>

					<br /><br />
					<label for="gc_method">
					<?php echo __("Choose the method to generate the Captcha:")
						. "\n<ul style=\"margin-left: 50px;\">\n"
						. "<li style=\"list-style: disc;\">" . __("Standard: medium security, high compatibility") . "</li>\n"
						. "<li style=\"list-style: disc;\">" . __("CSS: improved security, compatible with CSS-capable browsers") . "</li>\n"
						. "<li style=\"list-style: disc;\">" . __("CSS 3: better security, but reduces compatibility to CSS3-compliant browsers") . "</li>\n"
						. "</ul>\n"; ?>
					</label>
					<select id="gc_method" name="gc_method" />
						<option value="std"<?php echo $gc_method=='std' ? ' selected' : ''; ?>><?php _e("Standard"); ?></option>
						<option value="css"<?php echo $gc_method=='css' ? ' selected' : ''; ?>>CSS</option>
						<option value="css3"<?php echo $gc_method=='css3' ? ' selected' : ''; ?>>CSS 3</option>
					</select>

				</fieldset>

				<p>
					<input type="submit" name="Submit" value="<?php _e("Apply"); ?> " />
				</p>
			</fieldset>
		</form>

		<p>Translated by <a href="<?php _e("http://www.gabsoftware.com/"); ?>"><?php _e("Gabriel Hautclocq"); ?></a></p>
	</div>
	<?php
	} //function

	function gabcaptcha2_add_menu()
	{
		add_options_page('Gab Captcha 2', 'Gab Captcha 2', 8, __FILE__, Array( &$this, 'gabcaptcha2_options_page' ) );
	} //function

	/*
	 * Add CSS into the head
	 */
	function gabcaptcha2_add_stylesheet()
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
			printf( __("%s does not exist"), $gabcaptcha2_style_file);
		}

		$gc_method = get_option('gc_method');
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
				printf( __("%s does not exist"), $gabcaptcha2_style_file);
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
				printf( __("%s does not exist"), $gabcaptcha2_style_file);
			}
		}

	} //function













	function gabcaptcha2_check()
	{
		global $wpdb;

		if( !empty($_POST) )
		{
			// was there a GabCaptcha response ?
			if ($_POST["CommentTuring"] && $_POST["CommentSecret"])
			{
				if (md5(strtoupper($_POST["CommentTuring"])) == base64_decode($_POST["CommentSecret"]))
				{
					$secret = base64_decode($_POST["CommentSecret"]);

					$table_name = $wpdb->prefix . "gabcaptchasecret";
					$reqcnt = $wpdb->prepare("SELECT COUNT(SECRET) AS NB FROM %s WHERE SECRET='%s'", $table_name, $secret);
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
		$this->gabcaptcha2_check();

		if ($_SESSION['gabcaptcha2_comment_status'] == "passed")
		{
			//remove the flood check if a valid solution has been provided
			remove_filter('check_comment_flood', 'check_comment_flood_db');
			remove_filter('comment_flood_filter', 'wp_throttle_comment_flood');
		}

		return $commentdata;
	}
















	function gabcaptcha2_insert_comment( $id, $comment )
	{
		global $user_ID;
		global $gabcaptcha2_plugin_dir;

		if ($user_ID)
		{
			return $id;
		}

		//$this->gabcaptcha2_check();

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
			if (get_option('gc_automatically_approve') == 'yes')
			{
				wp_set_comment_status( $id, "approve" );
			}
		}



	} //function



	function gabcaptcha2_comment_form($id)
	{
		global $user_ID;
		global $gabcaptcha2_plugin_dir;

		if ($user_ID)
		{
			return $id;
		}

		//$_SESSION['gabcaptcha2_comment_status'] = "normal";

		$gc_method = get_option('gc_method');
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

		$show_credit = get_option('gc_show_credit');
		$gc_captcha_text = get_option('gc_captcha_text');
		?>

		<fieldset id="<?php echo $_SESSION['gabcaptcha2_id'];?>" class="gabcaptchafs"></fieldset>
		<noscript><p class="gabcaptchajd"><?php _e("Our antispam protection requires that you enable JavaScript in your browser to be able to comment!"); ?></p></noscript>
		<script type="text/javascript">
		/* <![CDATA[ */

		var commentField = document.getElementById("url");
		if(commentField==null)
		{
			commentField = document.getElementsByName("url");
		}

		var submitp = commentField.parentNode;
		var answerDiv = document.getElementById("<?php echo $_SESSION['gabcaptcha2_id']; ?>");
		answerDiv.innerHTML = '<legend>Anti-spam</legend>'
		+ '<!-- Turing test -->'
		+ '<p><?php echo escapestringjs($gc_captcha_text); ?></p>'
		+ '<label for="commentturing"><?php echo $gc_final_output; ?></label>'
		+ '<input type="text" id="commentturing" name="CommentTuring" maxlength="4" class="textField" /><br />'
		+ '<input type="hidden" id="commentsecret" name="CommentSecret" value="<?php echo base64_encode(md5($this->validanswer)) ?>" />'
		+ '<?php if ($failedprevious && $failedcommentdata != "" ): ?>'
		+ '<p class="gabcaptchaer"><?php echo escapestringjs(__("You failed the test. Try again!")); ?></p>'
		+ '<?php endif; ?>'
		+ '<?php if($show_credit==1):?><br />'
		+ '<a class="gabcaptchalc" href="<?php _e("http://www.gabsoftware.com/products/scripts/gabcaptcha2/"); ?>"><?php echo escapestringjs(__("Gab Captcha 2 &copy; Gabriel Hautclocq")); ?></a>'
		+ '<?php elseif ($show_credit==2):?><br />'
		+ '<span class="gabcaptchalc"><?php echo escapestringjs(__("Protected by <strong>Gab Captcha 2</strong>")); ?></span>'
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
			echo "	commentArea.innerHTML = '" . escapestringjs($failedcommentdata) . "';\n";
			echo "	window.location.hash = '#" . $_SESSION['gabcaptcha2_id'] . "';\n";
		}
		?>

		/* ]]> */
		</script>
		<?php
	} //function

} //class

?>