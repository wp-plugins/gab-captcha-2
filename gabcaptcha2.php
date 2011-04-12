<?php
/*
Plugin Name: Gab Captcha 2
Plugin URI: http://www.gabsoftware.com/products/scripts/gabcaptcha2/
Description: Efficient and simple captcha plugin for Wordpress comments.
Author: Gabriel Hautclocq
Version: 1.0.1
Author URI: http://www.gabsoftware.com
*/

register_activation_hook(__FILE__, 'gabcaptcha2_install');

//add_action('preprocess_comment', "gabcaptcha2_preprocess_comment", 1);
add_action('wp_insert_comment', "gabcaptcha2_insert_comment", 10, 2 );
//add_action('comment_post', "gabcaptcha2_comment_post");
add_action('comment_form', "gabcaptcha2_init");
add_action('admin_menu', 'gabcaptcha2_add_menu');
add_action('wp_head', 'gabcaptcha2_head');

add_option('gc_lang', '1', '', 'yes');
add_option('gc_show_credit', '1', '', 'yes');
add_option('gc_captcha_text', 'Prove that you are Human by typing the emphasized characters:', '', 'yes');
add_option('gc_captcha_length', 16, '', 'yes');
add_option('gc_captcha_to_pick', 4, '', 'yes');
add_option('gc_automatically_approve', 'no', '', 'yes');
add_option('gc_css_only', 'no', '', 'yes');


$langs = array(
	'1'=>array(
	'title' => 'English',
	'pref_title' => 'Gab Captcha settings',
	'pref_descr' => 'Now you can laugh at the bots!',
	'pref_lang' => 'Select language:',
	'pref_captcha_text_desc' => 'Captcha label',
	'pref_captcha_text_legend' => 'Captcha options',
	'pref_captcha_text_length' => 'Captcha length (2 to 64):',
	'pref_captcha_text_topick' => 'Captcha to pick (1 to 24):',
	'pref_automatically_approve_text' => 'Automatically approve comments who passed the test:',
	'pref_css_only_text' => 'Use CSS 3 to display Captcha (improves security, but reduce compatibility to CSS3-compliant browsers):',
	'pref_yes' => 'yes',
	'pref_no' => 'no',
	'pref_show_credit' => 'Display credits :',
 	'pref_show_credit_link' => 'as link',
	'pref_show_credit_text' => 'as plain text',
	'pref_show_credit_none' => 'off',
	'pref_apply' => 'Apply',
	'pref_update_message' => 'Settings was successfully updated!',
	'error' => 'You failed the test. Try again!',
	'link_url' => 'http://www.gabsoftware.com/products/scripts/gabcaptcha2/',
	'link_text' => 'Gab Captcha 2 &copy; Gabriel Hautclocq',
	'subtext' => 'powered by <strong>gabcaptcha</strong>',
	'js_disabled' => 'Our antispam protection requires that you enable JavaScript in your browser to be able to comment!',
	'translator' => 'Gabriel Hautclocq',
	'translator_url' => 'http://www.gabsoftware.com/'
	),

	'2'=>array(
	'title' => 'Français',
	'pref_title' => 'Préférences de Gab Captcha 2',
	'pref_descr' => 'Dorénavant vous pouvez vous moquer des bots !',
	'pref_lang' => 'Langage&nbsp;:',
	'pref_captcha_text_desc' => 'Label du captcha',
	'pref_captcha_text_legend' => 'Options du captcha',
	'pref_captcha_text_length' => 'Longueur du captcha (entre 2 et 64)&nbsp;:',
	'pref_captcha_text_topick' => 'Longueur de la solution (entre 1 et 24)&nbsp;:',
	'pref_automatically_approve_text' => 'Approuver automatiquement les commentaires ayants passé le test&nbsp;:',
	'pref_css_only_text' => 'Utiliser le CSS 3 pour afficher le Captcha (améliore la sécurité, mais restreint la compatibilité aux navigateurs gérant le CSS 3)&nbsp;:',
	'pref_yes' => 'oui',
	'pref_no' => 'non',
	'pref_show_credit' => 'Afficher les crédits&nbsp;:',
	'pref_show_credit_link' => 'en tant que lien',
	'pref_show_credit_text' => 'en tant que texte',
	'pref_show_credit_none' => 'Ne pas afficher les crédits',
	'pref_apply' => 'Appliquer',
	'pref_update_message' => 'Préférences enregistrées avec succès.',
	'error' => 'Vous n\'avez pas passé le test. Recommencez&nbsp;!',
	'link_url' => 'http://www.gabsoftware.com/products/scripts/gabcaptcha2/',
	'link_text' => 'Gab Captcha 2 &copy; Gabriel Hautclocq',
	'subtext' => 'protégé par <strong>gabcaptcha</strong>',
	'js_disabled' => 'Notre protection antispam exige d\'activer Javascript dans votre navigateur pour pouvoir poster un commentaire.',
	'translator' => 'Gabriel Hautclocq',
	'translator_url' => 'http://www.gabsoftware.com/'
	)
);



$letters = Array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
$captchalength = get_option('gc_captcha_length');
$captchatopick = get_option('gc_captcha_to_pick');
$captcha = gabcaptcha2_generate($letters, $captchalength);
$validkeys = gabcaptcha2_pickvalid($captcha, $captchatopick);
$validanswer = gabcaptcha2_getanswer($captcha, $validkeys);
$gabcaptchaoutput = gabcaptcha2_display($captcha, $validkeys);
$gabcaptchaoutput2 = gabcaptcha2_display2($captcha, $validkeys);
$keylist64 = gabcaptcha2_keylist($captcha, $validkeys);
$failedturing = false;
$inserted = FALSE;

function gabcaptcha2_generate($letters, $captchalength)
{
	$res = "";
	for ($i=0; $i<$captchalength; $i++)
	{
		$rand_key = array_rand($letters);
		$res .= $letters[$rand_key];
	}
	return $res;
}

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
}

function gabcaptcha2_getanswer($captcha, $validkeys)
{
	$answer = "";
	for ($i=0; $i<count($validkeys); $i++)
	{
		$answer .= $captcha[$validkeys[$i]];
	}
	return $answer;
}

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
}

function gabcaptcha2_display2($captcha, $validkeys)
{
	$res = "";
	for ($i=0; $i<strlen($captcha); $i++)
	{
		$res .= "<span>{$captcha[$i]}</span>";
	}
	return $res;
}

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
			$res .= $i . ",";//($i < (strlen($captcha) - 1) ? "," : "");
		}
	}
	return base64_encode($res);
}

function gabcaptcha2_check()
{
	global $failedturing;
	global $inserted;
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
					$inserted = $wpdb->insert( $table_name, array('secret' => $secret));
				}
			}
			else
			{
				// Failed... Sorry
				$failedturing = true;
			}
		}
		else
		{
			// Failed... Sorry
			$failedturing = true;
		}
	}
	return $failedturing;
}



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
}


function escapestringjs($str)
{
	return strtr($str, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
}

function gabcaptcha2_options_page() {
	global $langs;
	$texts = $langs[get_option('gc_lang')];

	if($_POST['Submit'])
	{
		if (get_option('gc_lang') != $_POST['gc_lang'])
		{
			$_POST['gc_captcha_text'] = ''; // reset captcha text to default for selected language
		}

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

		if ($_POST['gc_css_only'] == "yes" || $_POST['gc_css_only'] == "no")
		{
			update_option('gc_css_only', $_POST['gc_css_only']);
		}

		update_option('gc_lang', escapestringjs($_POST['gc_lang']));
		update_option('gc_show_credit', escapestringjs($_POST['gc_show_credit']));
		update_option('gc_captcha_text', escapestringjs($_POST['gc_captcha_text']));
		$texts = $langs[get_option('gc_lang')];
		echo '<div class="updated"><p>'.$texts['pref_update_message'].'</p></div>';
	}
?>
<div class="wrap">
	<h2><?php echo $texts['pref_title']; ?></h2>
	<p style="font-style: italic;"><?php echo $texts['pref_descr']; ?></p>

	<form method="post">
		<fieldset class="options">
			<legend style="font-weight: bold;">Options</legend>
			<?php
			$gc_show_credit = get_option('gc_show_credit');
			$gc_lang = get_option('gc_lang');
			$gc_captcha_text = htmlspecialchars(stripslashes(get_option('gc_captcha_text')), ENT_QUOTES);
			$gc_captcha_length = get_option('gc_captcha_length');
			$gc_captcha_to_pick = get_option('gc_captcha_to_pick');
			$gc_automatically_approve = get_option('gc_automatically_approve');
			$gc_css_only = get_option('gc_css_only');
			?>
			<p>
				<label for="gc_lang"><?php echo $texts['pref_lang']; ?></label>
				<select id="gc_lang" name="gc_lang" />
					<?foreach ($langs as $lang_id=>$value):?>
					<option value="<?php echo $lang_id; ?>" <?php echo $lang_id==$gc_lang ? 'selected' : ''; ?>><?php echo $value['title']; ?></option>
					<?endforeach;?>
				</select>
			</p>

			<p>
				<label for="gc_show_credit"><?php echo $texts['pref_show_credit']; ?></label>
				<select id="gc_show_credit" name="gc_show_credit" />
					<option value="1" <?php echo $gc_show_credit==1 ? 'selected' : ''; ?>><?php echo $texts['pref_show_credit_link']; ?></option>
					<option value="2" <?php echo $gc_show_credit==2 ? 'selected' : ''; ?>><?php echo $texts['pref_show_credit_text']; ?></option>
					<option value="3" <?php echo $gc_show_credit==3 ? 'selected' : ''; ?>><?php echo $texts['pref_show_credit_none']; ?></option>
				</select>
			</p>

			<fieldset style="margin-top: 20px;">
				<legend style="font-weight: bold;"><?php echo $texts['pref_captcha_text_desc']; ?></legend>
				<input type="text" name="gc_captcha_text" style="width: 600px;" value="<?php echo empty($gc_captcha_text) ? $texts['text'] : $gc_captcha_text; ?>" />
			</fieldset>

			<fieldset style="margin-top: 20px;">
				<legend style="font-weight: bold;"><?php echo $texts['pref_captcha_text_legend']; ?></legend>

				<label for="gc_captcha_length" /><?php echo $texts['pref_captcha_text_length']; ?></label>
				<input type="text" id="gc_captcha_length" name="gc_captcha_length" style="width: 100px;" value="<?php echo empty($gc_captcha_length) ? $texts['text'] : $gc_captcha_length; ?>" />

				<label for="gc_captcha_to_pick" /><?php echo $texts['pref_captcha_text_topick']; ?></label>
				<input type="text" id="gc_captcha_to_pick" name="gc_captcha_to_pick" style="width: 100px;" value="<?php echo empty($gc_captcha_to_pick) ? $texts['text'] : $gc_captcha_to_pick; ?>" />

				<br />
				<label for="gc_automatically_approve"><?php echo $texts['pref_automatically_approve_text']; ?></label>
				<select id="gc_automatically_approve" name="gc_automatically_approve" />
					<option value="yes"<?php echo $gc_automatically_approve=='yes' ? ' selected' : ''; ?>><?php echo $texts['pref_yes']; ?></option>
					<option value="no"<?php echo $gc_automatically_approve=='no' ? ' selected' : ''; ?>><?php echo $texts['pref_no']; ?></option>
				</select>

				<br />
				<label for="gc_css_only"><?php echo $texts['pref_css_only_text']; ?></label>
				<select id="gc_css_only" name="gc_css_only" />
					<option value="yes"<?php echo $gc_css_only=='yes' ? ' selected' : ''; ?>><?php echo $texts['pref_yes']; ?></option>
					<option value="no"<?php echo $gc_css_only=='no' ? ' selected' : ''; ?>><?php echo $texts['pref_no']; ?></option>
				</select>

			</fieldset>

			<p>
				<input type="submit" name="Submit" value="<?=$texts['pref_apply']?> " />
			</p>
		</fieldset>
	</form>

	<p>Translated by <a href="<?=$texts['translator_url']?>"><?=$texts['translator']?></a></p>
</div>
<?php
}

function gabcaptcha2_add_menu()
{
	add_options_page('Gab Captcha 2', 'Gab Captcha 2', 8, __FILE__, 'gabcaptcha2_options_page');
}

/**
 *	Add CSS/Js into the head
 **/
function gabcaptcha2_head()
{
	global $keylist64;
	$gabcaptcha_plugindir = WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__));
	$gabcaptcha_css = $gabcaptcha_plugindir . '/style.css';

?>

		<link rel="stylesheet" href="<?php echo $gabcaptcha_css; ?>" type="text/css" media="screen" />

<?php
	$gc_css_only = get_option('gc_css_only');
	if ($gc_css_only == 'yes')
	{

		$gabcaptcha_css2 = $gabcaptcha_plugindir . '/emphasis.php?set=' . $keylist64;
?>

		<link rel="stylesheet" href="<?php echo $gabcaptcha_css2; ?>" type="text/css" media="screen" />

<?php
	}
}

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

session_start();

if (!isset($_SESSION['gabcaptcha2_id']) or !isset($_SESSION['gabcaptcha2_session']))
{
	$_SESSION['gabcaptcha2_id'] = str_rand();
	$_SESSION['gabcaptcha2_session'] = str_rand();
}

function gabcaptcha2_insert_comment( $id, $comment )
{
	global $user_ID;
	global $failedturing;

	if ($user_ID)
	{
		return $id;
	}

	gabcaptcha2_check();

	if ($failedturing == true)
	{

		wp_set_comment_status( $id, "spam" );

		// use a file as marker for later use
		$gabcaptcha_plugindir = WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__));
		$failedfile = $gabcaptcha_plugindir . "/failed.txt";
		$fh = fopen($failedfile, 'a');
		$stringData = $_SESSION['gabcaptcha2_session'] . "-<(SEPARATOR)>-" . $_POST['comment'];
		fwrite($fh, $stringData);
		fclose($fh);

	}
	else
	{
		if (get_option('gc_automatically_approve') == 'yes')
		{
			wp_set_comment_status( $id, "approve" );
		}
	}
}

function gabcaptcha2_init($id)
{
	global $user_ID;
	global $langs;
	global $validanswer;
	global $gabcaptchaoutput;
	global $gabcaptchaoutput2;

	if ($user_ID)
	{
		return $id;
	}

	$gc_css_only = get_option('gc_css_only');
	if ($gc_css_only == 'yes')
	{
		$gc_final_output = $gabcaptchaoutput2;
	}
	else
	{
		$gc_final_output = $gabcaptchaoutput;
	}

	$gabcaptcha_plugindir = WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__));
	$failedfile = $gabcaptcha_plugindir . "/failed.txt";
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

	$texts = $langs[get_option('gc_lang')];
	$show_credit = get_option('gc_show_credit');
	$gc_captcha_text = get_option('gc_captcha_text');
	?>

	<fieldset id="<?php echo $_SESSION['gabcaptcha2_id'];?>" class="gabcaptchafs"></fieldset>
	<noscript><p class="gabcaptchajd"><?php echo $texts['js_disabled']; ?></p></noscript>
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
	+ '<input type="hidden" id="commentsecret" name="CommentSecret" value="<?php echo base64_encode(md5($validanswer)) ?>" />'
	+ '<?php if ($failedprevious && $failedcommentdata != "" ): ?>'
	+ '<p class="gabcaptchaer"><?php echo escapestringjs($texts['error']); ?></p>'
	+ '<?php endif; ?>'
	+ '<?php if($show_credit==1):?><br />'
	+ '<a class="gabcaptchalc" href="<?php echo $texts['link_url']; ?>"><?php echo escapestringjs($texts['link_text']); ?></a>'
	+ '<?php elseif ($show_credit==2):?><br />'
	+ '<span class="gabcaptchalc"><?php echo escapestringjs($texts['subtext']); ?></span>'
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
		echo "	commentArea.innerHTML = '" . escapestringjs($failedcommentdata) . "';";
	}
	?>

	/* ]]> */
	</script>
	<?php
}
?>