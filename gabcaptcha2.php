<?php
/*
Plugin Name: Gab Captcha 2
Plugin URI: http://www.gabsoftware.com/products/scripts/gabcaptcha2/
Description: Simple captcha plugin for Wordpress comments.
Author: Gabriel Hautclocq
Version: 1.0.20
Author URI: http://www.gabsoftware.com
Tags: comments, spam, bot, captcha, turing, test, challenge, protection, antispam
*/


//error_reporting(E_ALL);

// security check
if( ! defined( 'WP_PLUGIN_DIR' ) )
{
	die( 'There is nothing to see here.' );
}

/* constants */
define( 'GABCAPTCHA2_TEXTDOMAIN', 'gabcaptcha2' );

/* global variables */
$gabcaptcha2_plugin_dir = WP_PLUGIN_DIR . '/' . plugin_basename( dirname( __FILE__ ) );
$gabcaptcha2_plugin_url = WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) );

$gabcaptcha2_version_maj = 1;
$gabcaptcha2_version_min = 0;
$gabcaptcha2_version_rev = 20;
$gabcaptcha2_version = "{$gabcaptcha2_version_maj}.{$gabcaptcha2_version_min}.{$gabcaptcha2_version_rev}";


/*
 * Instanciates a new instance of GabCaptcha2
 */
new GabCaptcha2();


/*
 * Gab Captcha 2 plugin class
 */
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


	/*
	 * Plugin constructor, called automatically
	 */
	function __construct()
	{
		/*
		 *  Start session
		 */
		if( ! isset( $_SESSION ) )
		{
			session_start();
		}
		if( ! isset( $_SESSION['gabcaptcha2_id'] ) || ! isset( $_SESSION['gabcaptcha2_session'] ) )
		{
			$_SESSION['gabcaptcha2_id']      = $this->gabcaptcha2_str_rand();
			$_SESSION['gabcaptcha2_session'] = $this->gabcaptcha2_str_rand();
		}

		if( !isset( $_SESSION['gabcaptcha2_comment_status'] ) )
		{
			$_SESSION['gabcaptcha2_comment_status'] = 'normal';
		}

		$this->letters = Array ( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' );
		$this->captchalength     = $this->gabcaptcha2_get_option( 'captcha_length' );
		$this->captchatopick     = $this->gabcaptcha2_get_option( 'captcha_solution_length' );
		$this->captcha           = $this->gabcaptcha2_generate( $this->letters, $this->captchalength );
		$this->validkeys         = $this->gabcaptcha2_pickvalid( $this->captcha, $this->captchatopick );
		$this->validanswer       = $this->gabcaptcha2_getanswer( $this->captcha, $this->validkeys );
		$this->gabcaptchaoutput  = $this->gabcaptcha2_display( $this->captcha, $this->validkeys );
		$this->gabcaptchaoutput2 = $this->gabcaptcha2_display2( $this->captcha, $this->validkeys );
		$this->gabcaptchaoutput3 = $this->gabcaptcha2_display3( $this->captcha, $this->validkeys );
		$this->keylist64         = $this->gabcaptcha2_keylist( $this->captcha, $this->validkeys );
		$this->failedturing      = true;
		$this->inserted          = false;

		$this->should_install();


		if( is_admin() )
		{
			//include admin-related files
			require_once( 'gabcaptcha2_admin.php' );

			$this->gabcaptcha2_options = new GabCaptcha2_Options();
		}

		// Place your add_actions and add_filters here

		add_action( 'init',                       array( &$this, 'gabcaptcha2_init_callback' ) );
		add_action( 'wp_insert_comment',          array( &$this, 'gabcaptcha2_insert_comment_callback' ), 10, 2 );
		add_action( 'comment_form',               array( &$this, 'gabcaptcha2_comment_form_callback' ) );
		add_action( 'comment_form_after_fields',  array( &$this, 'gabcaptcha2_comment_form_after_fields_callback' ) );
		add_action( 'pre_comment_on_post',        array( &$this, 'gabcaptcha2_pre_comment_on_post_callback' ), 10, 1 );
		add_action( 'preprocess_comment',         array( &$this, 'gabcaptcha2_preprocess_comment_callback' ), 10, 1 );
		add_filter( 'comment_form_field_comment', array( &$this, 'gabcaptcha2_comment_form_field_comment_callback' ), 10, 1 );

	} // function


	/*
	 * Returns the value of the specified option
	 */
	public function gabcaptcha2_get_option( $name )
	{
		$options = get_option( 'gabcaptcha2_options' );
		if( isset( $options[$name] ) )
		{
			return $options[$name];
		}
		else
		{
			return false;
		}
	}

	/*
	 * Sets the value of the specified option
	 */
	public function gabcaptcha2_set_option( $name, $value )
	{
		$options = get_option( 'gabcaptcha2_options' );
		$options[ $name ] = $value;

		return update_option( 'gabcaptcha2_options', $options );
	}



	/*
	 * Gets all or part of the version of GabCaptcha2
	 */
	public function gabcaptcha2_get_version( $what = 'all' )
	{
		global $gabcaptcha2_version;

		$version = get_option( 'gabcaptcha2_version' );

		if( empty( $version ) )
		{
			$version = '1.0.11'; //because this option exist since version 1.0.11
		}

		switch( $what )
		{
			case 'major':
				$version_array = explode( '.', $version );
				return $version_array[0];
				break;

			case 'minor':
				$version_array = explode( '.', $version );
				return $version_array[1];
				break;

			case 'revision':
				$version_array = explode( '.', $version );
				return $version_array[2];
				break;

			case 'all':
			default:
				return $version;
		}
	}


	/*
	 * Sets the Language
	 */
	public function gabcaptcha2_setlang()
	{
		global $gabcaptcha2_plugin_dir;
		if( function_exists( 'load_plugin_textdomain' ) )
		{
			load_plugin_textdomain( GABCAPTCHA2_TEXTDOMAIN, false, $gabcaptcha2_plugin_dir . '/lang' );
		}
	} //function

	public function gabcaptcha2_init_callback()
	{
		$this->gabcaptcha2_setlang();

		add_action( 'wp_print_styles', array( &$this, 'gabcaptcha2_add_stylesheet_callback' ) );

	} // function


	/*
	 * Returns a random string composed of alphabet characters
	 */
	public function gabcaptcha2_str_rand()
	{
		$seeds = 'abcdefghijklmnopqrstuvwqyz';
		$length = 8;
		// Seed generator
		list( $usec, $sec) = explode( ' ', microtime() );
		$seed = (float) $sec + ( (float) $usec * 100000 );
		mt_srand( $seed );

		// Generate
		$str = '';
		$seeds_count = strlen( $seeds );

		for( $i = 0; $length > $i; $i++ )
		{
			$str .= $seeds{mt_rand( 0, $seeds_count - 1 )};
		}

		return $str;
	}


	/*
	 * Checks if Gab Captcha 2 should be installed or upgraded
	 */
	public function should_install()
	{
		global $gabcaptcha2_version_maj;
		global $gabcaptcha2_version_min;
		global $gabcaptcha2_version_rev;

		$majver = $this->gabcaptcha2_get_version( 'major' );
		$minver = $this->gabcaptcha2_get_version( 'minor' );
		$revver = $this->gabcaptcha2_get_version( 'revision' );


		if( $majver != $gabcaptcha2_version_maj || $minver != $gabcaptcha2_version_min || $revver != $gabcaptcha2_version_rev )
		{
			$this->install( $gabcaptcha2_version_maj, $gabcaptcha2_version_min, $gabcaptcha2_version_rev );
		}
	}


	/*
	 * Installation and upgrade routine of the plugin
	 */
	public function install( $vermajor, $verminor, $verrevision )
	{
		global $gabcaptcha2_version;


		$majver = $this->gabcaptcha2_get_version( 'major' );
		$minver = $this->gabcaptcha2_get_version( 'minor' );
		$revver = $this->gabcaptcha2_get_version( 'revision' );

		global $wpdb;

		/* begin installation routine */
		$table_name = $wpdb->prefix . "gabcaptchasecret";

		if( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name )
		{

			$sql = "CREATE TABLE " . $table_name . " (
				  id mediumint(9) NOT NULL AUTO_INCREMENT,
				  SECRET varchar(64) NOT NULL,
				  UNIQUE KEY id (id)
				);";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql);

			$rows_affected = $wpdb->insert( $table_name, array( 'secret' => "TABLE CREATED ON DATE : " . current_time( 'mysql' ) ) );
		}
		/* end installation routine */

		/* begin upgrade routine */
		if( $majver == 1 )
		{
			if( $minver == 0 )
			{
				if( $revver < 12 )
				{
					//set the new options array
					$this->gabcaptcha2_options['display_credits']         = get_option( 'gc_show_credit' );
					$this->gabcaptcha2_options['automatically_approve']   = ( get_option( 'gc_automatically_approve' ) == 'yes' ? 'on' : 'off' );
					$this->gabcaptcha2_options['captcha_label']           = get_option( 'gc_captcha_text' );
					$this->gabcaptcha2_options['captcha_length']          = get_option( 'gc_captcha_length' );
					$this->gabcaptcha2_options['captcha_solution_length'] = get_option( 'gc_captcha_to_pick' );
					$this->gabcaptcha2_options['output_method']           = get_option( 'gc_method' );

					//add the new options array
					add_option( 'gabcaptcha2_options', $this->gabcaptcha2_options );
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
				if( $revver < 16 )
				{
					$this->gabcaptcha2_set_option( 'insert_comment', 'on' );
				}
				if( $revver < 18 )
				{
					$this->gabcaptcha2_set_option( 'use_js', 'on' );
				}
				if( $revver < 19 )
				{
					$this->gabcaptcha2_set_option( 'legacy_theme', 'off' );
				}
			}
		}
		update_option( 'gabcaptcha2_version', $gabcaptcha2_version );
		/* end upgrade routine */
	} //function








	/*
	 * Generates a random string using the provided array of allowed characters and length
	 */
	public function gabcaptcha2_generate( $characters, $captchalength)
	{
		$res = '';
		for( $i = 0; $i < $captchalength; $i++ )
		{
			$rand_key = array_rand( $characters );
			$res .= $characters[$rand_key];
		}
		return $res;
	} //function

	/*
	 * Returns an array containing the indexes of each character of the solution
	 */
	public function gabcaptcha2_pickvalid( $captcha, $captchatopick )
	{
		$res = Array();
		$input = str_split( $captcha );
		for( $i = 0; $i < $captchatopick; $i++ )
		{
			if( $i > 0 )
			{
				$found = true;
				while ( $found )
				{
					$found = false;
					$key = array_rand( $input );
					for( $j = 0; $j <= $i; $j++ )
					{
						if( isset( $res[$j] ) )
						{
							if( $key == $res[$j] )
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
				$key = array_rand( $input );
			}
			$res[$i] = $key;
		}
		sort( $res );
		return $res;
	} //function

	/*
	 * Generates the string of the solution
	 */
	public function gabcaptcha2_getanswer( $captcha, $validkeys )
	{
		$answer = '';
		for( $i = 0, $n = count( $validkeys); $i < $n; $i++ )
		{
			$answer .= $captcha[ $validkeys[$i] ];
		}
		return $answer;
	} //function

	/*
	 * Renders the solution for the low security method
	 */
	public function gabcaptcha2_display( $captcha, $validkeys)
	{
		$res = '';
		for( $i = 0, $m = strlen( $captcha ); $i < $m; $i++ )
		{
			$validkey = false;
			for( $j = 0, $n = count( $validkeys ); $j < $n; $j++ )
			{
				if( $validkeys[$j] == $i )
				{
					$validkey = true;
					break;
				}
			}
			if( $validkey )
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

	/*
	 * Renders the solution for the medium security method
	 */
	public function gabcaptcha2_display2( $captcha, $validkeys )
	{
		$res = '';
		for( $i = 0, $n = strlen( $captcha ); $i < $n; $i++ )
		{
			$res .= "<span class=\"gc2_{$i}\">{$captcha[$i]}</span>";
		}
		return $res;
	} //function

	/*
	 * Renders the solution for the high security method
	 */
	public function gabcaptcha2_display3( $captcha, $validkeys )
	{
		$res = '';
		for( $i = 0, $n = strlen( $captcha ); $i < $n; $i++ )
		{
			$res .= "<span>{$captcha[$i]}</span>";
		}
		return $res;
	} //function

	/*
	 * Returns a base64 encoded comma-separated list of indexes that are part of the solution
	 */
	public function gabcaptcha2_keylist( $captcha, $validkeys )
	{
		$res = '';
		for( $i = 0, $n = strlen( $captcha ); $i < $n; $i++ )
		{
			$validkey = false;
			for( $j = 0, $m = count( $validkeys ); $j < $m; $j++ )
			{
				if( $validkeys[$j] == $i )
				{
					$validkey = true;
					break;
				}
			}
			if( $validkey )
			{
				$res .= $i . ',';
			}
		}
		return base64_encode( $res );
	} //function



	/*
	 * Adds CSS into the head
	 */
	public function gabcaptcha2_add_stylesheet_callback()
	{
		global $gabcaptcha2_plugin_dir;
		global $gabcaptcha2_plugin_url;

		$gabcaptcha2_style_url = $gabcaptcha2_plugin_url . '/style.css';
		$gabcaptcha2_style_file = $gabcaptcha2_plugin_dir . '/style.css';
		if( file_exists( $gabcaptcha2_style_file ) )
		{
			wp_register_style( 'gabcaptcha2_stylesheet_std', $gabcaptcha2_style_url );
			wp_enqueue_style( 'gabcaptcha2_stylesheet_std' );
		}
		else
		{
			printf( __( '%s does not exist', GABCAPTCHA2_TEXTDOMAIN ), $gabcaptcha2_style_file );
		}

		$gc_method = $this->gabcaptcha2_get_option( 'output_method' );
		if( $gc_method == 'css' )
		{
			$gabcaptcha2_style_url = $gabcaptcha2_plugin_url . '/emphasis.php?set=' . $this->keylist64;
			$gabcaptcha2_style_file = $gabcaptcha2_plugin_dir . '/emphasis.php';
			if( file_exists( $gabcaptcha2_style_file) )
			{
				wp_register_style( 'gabcaptcha2_stylesheet_css', $gabcaptcha2_style_url );
				wp_enqueue_style( 'gabcaptcha2_stylesheet_css' );
			}
			else
			{
				printf( __( '%s does not exist', GABCAPTCHA2_TEXTDOMAIN ), $gabcaptcha2_style_file );
			}
		}
		else if( $gc_method == 'css3' )
		{
			$gabcaptcha2_style_url = $gabcaptcha2_plugin_url . '/emphasis.php?method=css3&amp;set=' . $this->keylist64;
			$gabcaptcha2_style_file = $gabcaptcha2_plugin_dir . '/emphasis.php';
			if( file_exists( $gabcaptcha2_style_file) )
			{
				wp_register_style( 'gabcaptcha2_stylesheet_css3', $gabcaptcha2_style_url );
				wp_enqueue_style( 'gabcaptcha2_stylesheet_css3' );
			}
			else
			{
				printf( __( '%s does not exist', GABCAPTCHA2_TEXTDOMAIN ), $gabcaptcha2_style_file );
			}
		}

	} //function












	/*
	 * Checks if a valid solution was given and returns TRUE in case of failure
	 */
	public function gabcaptcha2_check_valid()
	{
		global $wpdb;

		//failed by default
		$this->failedturing == FALSE;

		//if $_POST array is empty, then no need to check
		if( ! empty( $_POST ) )
		{
			// Is it a GabCaptcha response ?
			if( ! empty( $_POST['CommentTuring'] ) && ! empty( $_POST['CommentSecret'] ) )
			{
				//Check the validity of posted fields
				$secret = base64_decode( $_POST['CommentSecret'] );
				if( md5( strtoupper( $_POST['CommentTuring'] ) ) == $secret )
				{
					//we check if the secret field already exists
					$table_name = $wpdb->prefix . 'gabcaptchasecret';
					$reqcnt = $wpdb->prepare( "SELECT COUNT(SECRET) AS NB FROM " . $table_name . " WHERE SECRET = %s", $secret );
					$numrows = 0;
					$cntrow = $wpdb->get_row( $reqcnt );
					$numrows = $cntrow->NB;

					//if not found, we can add the secret field into the database, and test is successful.
					if( $numrows == 0 )
					{
						$this->inserted = $wpdb->insert( $table_name, array( 'secret' => $secret ) );
						$this->failedturing = FALSE;
					}
					else
					{
						//probably a spam bot... failed
						$this->failedturing = TRUE;
					}
				}
				else
				{
					// Failed... Sorry
					$this->failedturing = TRUE;
				}
			}
			else
			{
				// Failed... Sorry
				$this->failedturing = TRUE;
			}
		}

		if( $this->failedturing == TRUE )
		{
			$_SESSION['gabcaptcha2_comment_status'] = 'failed';
		}
		else
		{
			$_SESSION['gabcaptcha2_comment_status'] = 'passed';
		}

		return $this->failedturing;
	} //function







	/*
	 * Checks if comment is spam JUST BEFORE insertion in the database
	 */
	public function gabcaptcha2_preprocess_comment_callback( $commentdata )
	{
		$insert_comment = $this->gabcaptcha2_get_option( 'insert_comment' );
		if( $insert_comment === 'on' )
		{
			//check if a valid solution was given
			$this->gabcaptcha2_check_valid();

			if( $_SESSION['gabcaptcha2_comment_status'] == 'passed' )
			{
				//remove the flood check if a valid solution has been provided
				remove_filter( 'check_comment_flood', 'check_comment_flood_db' );
				remove_filter( 'comment_flood_filter', 'wp_throttle_comment_flood' );
			}
		}

		return $commentdata;
	}



	/*
	 * Checks if comment is spam BEFORE insertion in the database and prevent it to be inserted if it is spam
	 */
	public function gabcaptcha2_pre_comment_on_post_callback( $comment_post_ID )
	{
		$insert_comment = $this->gabcaptcha2_get_option( 'insert_comment' );
		if( ! is_user_logged_in() )
		{
			if( $insert_comment !== 'on' )
			{
				//check if a valid solution was given
				$this->gabcaptcha2_check_valid();
				if( $_SESSION['gabcaptcha2_comment_status'] == 'passed' )
				{
					//remove the flood check if a valid solution has been provided
					remove_filter( 'check_comment_flood', 'check_comment_flood_db' );
					remove_filter( 'comment_flood_filter', 'wp_throttle_comment_flood' );
				}
				else
				{
					//we save the comment
					$_SESSION['gabcaptcha2_comment_data'] = htmlspecialchars( $_POST['comment'] );

					//we get the URL from where the user posted a comment
					$permalink = get_permalink( $comment_post_ID ) . '#' . $_SESSION['gabcaptcha2_id'];

					//we set up an automatic redirection to the previous page
					header( 'refresh: 10; url=' . $permalink );
					$message  = '<h1>' . __( 'Wrong code typed!', GABCAPTCHA2_TEXTDOMAIN ) . '</h1>';
					$message .= '<p>' . sprintf( __( 'You will be re-directed in 10 seconds to <a href="%1$s">%1$s</a> (the URL you come from).', GABCAPTCHA2_TEXTDOMAIN ), $permalink ) . '</p>';
					$message .= '<p>' . __( "If the redirection does not work, click on the link above.", GABCAPTCHA2_TEXTDOMAIN ) . '</p>';
					$message .= '<p>' . __( "If you are Human, don't worry, your comment is not lost. It will be displayed again on the next page.", GABCAPTCHA2_TEXTDOMAIN ) . '</p>';
					$message .= '<p>' . __( 'But double-check your code next time!', GABCAPTCHA2_TEXTDOMAIN ) . '</p>';
					$message .= '<p>' . __( "If you are a spam-bot, too bad for you.", GABCAPTCHA2_TEXTDOMAIN ) . '</p>';

					//stop the script before comment is inserted into the database
					wp_die( $message );
				}
			}
			//else we do not do anything for now
		}
		//else add the comment of the logged in user
		return $comment_post_ID;
	}







	/*
	 * Called after a comment has been inserted into the database
	 */
	public function gabcaptcha2_insert_comment_callback( $id, $comment )
	{
		global $user_ID;
		global $gabcaptcha2_plugin_dir;

		if( $user_ID )
		{
			return $id;
		}

		if( $_SESSION['gabcaptcha2_comment_status'] == 'failed' )
		{
			$_SESSION['gabcaptcha2_comment_data'] = htmlspecialchars( $_POST['comment'] );

			//delete the comment to avoid a "you already said that" message.
			wp_delete_comment( $id );

		}
		else
		{
			//if( get_option( 'gc_automatically_approve' ) == 'yes' )
			if( $this->gabcaptcha2_get_option( 'automatically_approve' ) === 'on' )
			{
				wp_set_comment_status( $id, 'approve' );
			}
		}
	} //function



	/*
	 * Called AFTER the fields of the comment form have been rendered
	 * but BEFORE the "comment" field is rendered
	 */
	public function gabcaptcha2_comment_form_after_fields_callback()
	{
		global $gabcaptcha2_plugin_dir;
		global $gabcaptcha2_version;

		//render the captcha depending on the method
		$gc_method = $this->gabcaptcha2_get_option( 'output_method' );
		if( $gc_method == 'css' )
		{
			$gc_final_output = $this->gabcaptchaoutput2;
		}
		else if( $gc_method == 'css3' )
		{
			$gc_final_output = $this->gabcaptchaoutput3;
		}
		else
		{
			$gc_final_output = $this->gabcaptchaoutput;
		}

		/* get the comment data back if failed the captcha */
		$failedprevious = isset( $_SESSION['gabcaptcha2_comment_data'] );
		$failedcommentdata = '';
		if( $failedprevious )
		{
			$failedcommentdata = $_SESSION['gabcaptcha2_comment_data'];
		}

		//get various options
		$show_credit       = $this->gabcaptcha2_get_option( 'display_credits' );
		$gc_captcha_text   = $this->gabcaptcha2_get_option( 'captcha_label' );
		$gc_captcha_length = $this->gabcaptcha2_get_option( 'captcha_length' );
		$use_js = ($this->gabcaptcha2_get_option( 'use_js' ) === 'on' );


		if( $use_js )
		{
			?>

			<fieldset id="<?php echo $_SESSION['gabcaptcha2_id']; ?>" class="gabcaptchafs"></fieldset>

			<?php
		}
		else
		{
			//adds the captcha without Javascript
			?>

			<fieldset id="<?php echo $_SESSION['gabcaptcha2_id']; ?>" class="gabcaptchafs">
				<legend><?php echo __( 'Anti-spam protection', GABCAPTCHA2_TEXTDOMAIN ); ?></legend>
				<!-- <?php echo sprintf( __( 'Turing test using Gab Captcha 2 v%s (http://www.gabsoftware.com/products/scripts/gabcaptcha2/)' ), $gabcaptcha2_version ); ?> -->
				<p><?php echo esc_js( $gc_captcha_text ); ?></p>
				<label for="commentturing"><?php echo $gc_final_output; ?></label>
				<input type="text" id="commentturing" name="CommentTuring" required="required" <?php if( $failedprevious && $failedcommentdata != '' ): ?>autofocus="autofocus" <?php endif; ?>maxlength="<?php echo $gc_captcha_length; ?>" />
				<br />
				<input type="hidden" id="commentsecret" name="CommentSecret" value="<?php echo base64_encode( md5( $this->validanswer ) ); ?>" );

				<?php if( $failedprevious && $failedcommentdata != '' ): ?>

					<p class="gabcaptchaer"><?php echo __( 'You failed the test. Try again!', GABCAPTCHA2_TEXTDOMAIN ); ?></p>

				<?php endif; ?>

				<?php if( $show_credit == 1 || $show_credit == 2 ): ?>

					<br />

					<?php if( $show_credit == 1 ): ?>

						<a href="<?php _e( 'http://www.gabsoftware.com/products/scripts/gabcaptcha2/', GABCAPTCHA2_TEXTDOMAIN ); ?>" title="<?php echo sprintf( __( 'Click here for more information about Gab Captcha 2 v%s', GABCAPTCHA2_TEXTDOMAIN ), $gabcaptcha2_version ); ?>" target="_blank" class="gabcaptchalc"><?php _e( 'Protected by ', GABCAPTCHA2_TEXTDOMAIN ); ?><strong><?php _e( 'Gab Captcha 2', GABCAPTCHA2_TEXTDOMAIN ); ?></strong></a>

					<?php elseif( $show_credit == 2 ): ?>

						<span class="gabcaptchalc" title="<?php echo sprintf( __( 'More information about Gab Captcha 2 v%s on http://www.gabsoftware.com/', GABCAPTCHA2_TEXTDOMAIN ), $gabcaptcha2_version ); ?>"><?php _e( 'Protected by ', GABCAPTCHA2_TEXTDOMAIN ); ?><strong><?php _e( 'Gab Captcha 2', GABCAPTCHA2_TEXTDOMAIN ); ?></strong></span>

					<?php endif;?>

				<?php endif;?>

			</fieldset>

			<?php
		} //if
	} //function

	/*
	 * Called AFTER all the fields of the comment form have been rendered.
	 * The javascript MUST be written after the comment field has been rendered.
	 */
	public function gabcaptcha2_comment_form_callback( $id )
	{

		global $user_ID;
		global $gabcaptcha2_plugin_dir;
		global $gabcaptcha2_version;

		if( $user_ID )
		{
			return $id;
		}

		//render the captcha depending on the method
		$gc_method = $this->gabcaptcha2_get_option( 'output_method' );
		if( $gc_method == 'css' )
		{
			$gc_final_output = $this->gabcaptchaoutput2;
		}
		else if( $gc_method == 'css3' )
		{
			$gc_final_output = $this->gabcaptchaoutput3;
		}
		else
		{
			$gc_final_output = $this->gabcaptchaoutput;
		}

		/* get the comment data back if failed the captcha */
		$failedprevious = isset( $_SESSION['gabcaptcha2_comment_data'] );
		$failedcommentdata = '';
		if( $failedprevious )
		{
			$failedcommentdata = $_SESSION['gabcaptcha2_comment_data'];
			unset( $_SESSION['gabcaptcha2_comment_data'] );
		}

		//get various options
		$show_credit       = $this->gabcaptcha2_get_option( 'display_credits' );
		$gc_captcha_text   = $this->gabcaptcha2_get_option( 'captcha_label' );
		$gc_captcha_length = $this->gabcaptcha2_get_option( 'captcha_length' );
		$use_js = ($this->gabcaptcha2_get_option( 'use_js' ) === 'on' );
		$legacy_theme = ($this->gabcaptcha2_get_option( 'legacy_theme' ) === 'on' );


		if( $use_js || $legacy_theme )
		{
			//if legacy theme, we have to output the fieldset after the comment form, then use JavaScript to replace it before the comment textarea.
			if( $legacy_theme )
			{
				?>

					<fieldset id="<?php echo $_SESSION['gabcaptcha2_id']; ?>" class="gabcaptchafs"></fieldset>

				<?php
			}

			//adds the captcha using Javascript
			?>

			<noscript><p class="gabcaptchajd"><?php _e( 'Our anti-spam protection requires that you enable JavaScript in your browser to be able to comment!', GABCAPTCHA2_TEXTDOMAIN ); ?></p></noscript>
			<script type="text/javascript">
			/* <![CDATA[ */

			//return the element specified by id
			function gabcaptcha2_getElementByIdUniversal( id )
			{
				var elem = null;
				if( document.getElementById )
				{
					elem = document.getElementById( id );
				}
				else
				{
					elem = document.all[ id ];
				}
				return elem;
			}

			//load xml from string
			function loadXMLString( txt )
			{
				if (window.DOMParser)
				{
					parser=new DOMParser();
					xmlDoc=parser.parseFromString( txt, "text/xml" );
				}
				else // Internet Explorer
				{
					xmlDoc=new ActiveXObject( "Microsoft.XMLDOM" );
					xmlDoc.async = "false";
					xmlDoc.loadXML( txt );
				}
				return xmlDoc;
			}


			// IE HACK: Define _importNode for IE since it doesnt support importNode
			if( ! document.importNode )
			{
				document._importNode = function( oNode, bImportChildren )
				{
					var oNew;
					if( oNode.nodeType == 1 )
					{
						oNew = document.createElement( oNode.nodeName );
						for( var i = 0; i < oNode.attributes.length; i++ )
						{
							if( oNode.attributes[i].nodeValue != null && oNode.attributes[i].nodeValue != '' )
							{
								var attrName = oNode.attributes[i].name;
								if( attrName == "class" )
								{
									oNew.setAttribute( "className", oNode.attributes[i].value );
								}
								//else
								//{
									oNew.setAttribute( attrName, oNode.attributes[i].value );
								//}
							}
						}
						if( oNode.style != null && oNode.style.cssText != null )
						{
							oNew.style.cssText = oNode.style.cssText;
						}
					}
					else if( oNode.nodeType == 3 )
					{
						oNew = document.createTextNode( oNode.nodeValue );
					}
					else if( oNode.nodeType == 8 )
					{
						oNew = document.createComment( oNode.nodeValue );
					}
					else
					{
						oNew = document.createTextNode(''); // Skip anything else and prepare to return an empty text node
					}
					if( bImportChildren && oNode.hasChildNodes() )
					{
						for( var oChild = oNode.firstChild; oChild; oChild = oChild.nextSibling )
						{
							oNew.appendChild( document._importNode( oChild, true ) );
						}
					}
					return oNew;
				}
			}
			// IE HACK (end)


			var captchatarget = gabcaptcha2_getElementByIdUniversal( '<?php echo $_SESSION['gabcaptcha2_id']; ?>' );

			//legend
			var node = document.createElement( "legend" );
			var nodetext = document.createTextNode( "<?php echo esc_js( __( 'Anti-spam protection', GABCAPTCHA2_TEXTDOMAIN ) ); ?>" );
			node.appendChild(nodetext);
			captchatarget.appendChild( node );

			//a comment
			node = document.createComment("Turing test using Gab Captcha 2 v<?php echo $gabcaptcha2_version; ?> (http://www.gabsoftware.com/products/scripts/gabcaptcha2/)");
			captchatarget.appendChild( node );

			//a paragraph
			node = document.createElement( "p" );
			nodetext = document.createTextNode( "<?php echo esc_js( $gc_captcha_text ); ?>" );
			node.appendChild(nodetext);
			captchatarget.appendChild( node );

			//a label
			node = document.createElement( "label" );
			node.setAttribute( "for", "commentturing" );
			var xml = loadXMLString( '<root xmlns="http://www.w3.org/1999/xhtml"><?php echo $gc_final_output; ?></root>' );
			var nodes = xml.documentElement.childNodes;
			var external_node = null;
			var local_node = null;
			for( var i = 0, n = nodes.length; i < n; i++ )
			{
				/* import the node from the xml document */
				external_node = nodes[i].cloneNode( true );
				if( document.importNode )
				{
					local_node = document.importNode( external_node, true);
					node.appendChild( local_node );
				}
				else
				{
					local_node = document._importNode( external_node, true);
					node.appendChild( local_node );
					node.innerHTML = node.innerHTML;
				}
			}
			captchatarget.appendChild( node );

			//input type=text
			node = document.createElement( "input" );
			node.setAttribute( "type", "text" );
			node.setAttribute( "id", "commentturing" );
			node.setAttribute( "name", "CommentTuring" );
			node.setAttribute( "required", "required" );
			node.setAttribute( "maxlength", "<?php echo $gc_captcha_length; ?>" );
			node.setAttribute( "class", "textField" );
			<?php if( $failedprevious && $failedcommentdata != '' ): ?>
				node.setAttribute( "autofocus", "autofocus" );
			<?php endif; ?>

			captchatarget.appendChild( node );

			//br
			node = document.createElement( "br" );
			captchatarget.appendChild( node );

			//input type=hidden
			node = document.createElement( "input" );
			node.setAttribute( "type", "hidden" );
			node.setAttribute( "id", "commentsecret" );
			node.setAttribute( "name", "CommentSecret" );
			node.setAttribute( "value", "<?php echo base64_encode( md5( $this->validanswer ) ); ?>" );
			captchatarget.appendChild( node );

			<?php if( $failedprevious && $failedcommentdata != '' ): ?>

				//a paragraph
				node = document.createElement( "p" );
				node.setAttribute( "class", "gabcaptchaer" );
				nodetext = document.createTextNode( "<?php echo esc_js( __( 'You failed the test. Try again!', GABCAPTCHA2_TEXTDOMAIN ) ); ?>" );
				node.appendChild( nodetext );
				captchatarget.appendChild( node );

			<?php endif; ?>

			<?php if( $show_credit == 1 || $show_credit == 2 ): ?>

				//br
				node = document.createElement( "br" );
				captchatarget.appendChild( node );

				<?php if( $show_credit == 1 ): ?>

					//a link
					node = document.createElement( "a" );
					node.setAttribute( "href", "<?php _e( 'http://www.gabsoftware.com/products/scripts/gabcaptcha2/', GABCAPTCHA2_TEXTDOMAIN ); ?>" );
					node.setAttribute( "title", "<?php echo esc_js( sprintf( __( 'Click here for more information about Gab Captcha 2 v%s', GABCAPTCHA2_TEXTDOMAIN ), $gabcaptcha2_version ) ); ?>" );
					node.setAttribute( "target", "_blank" );
					nodetext = document.createTextNode( "<?php echo esc_js( __( 'Protected by ', GABCAPTCHA2_TEXTDOMAIN ) ); ?>" );
					node.appendChild( nodetext );
					var node2 = document.createElement( "strong" );
					nodetext = document.createTextNode( "<?php echo esc_js( __( 'Gab Captcha 2', GABCAPTCHA2_TEXTDOMAIN ) ); ?>" );
					node2.appendChild( nodetext );
					node.appendChild( node2 );

				<?php elseif( $show_credit == 2 ): ?>

					// a span
					node = document.createElement( "span" );
					node.setAttribute( "title", "<?php echo esc_js( sprintf( __( 'More information about Gab Captcha 2 v%s on http://www.gabsoftware.com/', GABCAPTCHA2_TEXTDOMAIN ), $gabcaptcha2_version ) ); ?>" );
					nodetext = document.createTextNode( "<?php echo esc_js( __( 'Protected by ', GABCAPTCHA2_TEXTDOMAIN ) ); ?>" );
					node.appendChild( nodetext );
					var node2 = document.createElement( "strong" );
					nodetext = document.createTextNode( "<?php echo esc_js( __( 'Gab Captcha 2', GABCAPTCHA2_TEXTDOMAIN ) ); ?>" );
					node2.appendChild( nodetext );
					node.appendChild( node2 );

				<?php endif;?>

				//common instructions for link and span
				node.setAttribute( "class", "gabcaptchalc" );
				captchatarget.appendChild( node );

				<?php if( $legacy_theme ): ?>

					//This is a legacy theme. We try to find the comment textarea and insert the captcha just before.
					var commentField = gabcaptcha2_getElementByIdUniversal( 'comment' );
					if( commentField == null )
					{
						//Try with the name attribute
						var fields = document.getElementsByTagName( 'comment' );
						if( fields.length > 0 )
						{
							commentField = fields[0];
						}
					}
					if( commentField != null )
					{
						//we found the comment text area, so we insert the captcha right before it
						var parentNode = commentField.parentNode;
						parentNode.insertBefore( captchatarget, commentField );

						<?php if( $failedprevious && $failedcommentdata != '' ): ?>

							//we fill the comment text area with the comment data
							commentField.innerHTML = '<?php echo esc_js( $failedcommentdata ); ?>';

						<?php endif; ?>
					}
					else
					{
						//The comment text area wasn't found... The captcha should appear under the comment area.
						//Not very pretty, but eh, shouldn't your theme be more up-to-date?
					}

				<?php endif; ?>

			<?php endif;?>

			<?php if( $failedprevious && $failedcommentdata != '' ): ?>

				window.location.hash = "#<?php echo $_SESSION['gabcaptcha2_id']; ?>";

			<?php endif; ?>

			/* ]]> */
			</script>

			<?php
		} //if
	} //function

	/*
	 * Called RIGHT BEFORE the comment field is rendered
	 * It will insert previous comment data (sanitized) if necessary
	 */
	public function gabcaptcha2_comment_form_field_comment_callback( $comment_field )
	{
		$failedprevious = isset( $_SESSION['gabcaptcha2_comment_data'] );
		$failedcommentdata = '';
		if( $failedprevious )
		{
			$failedcommentdata = $_SESSION['gabcaptcha2_comment_data'];
			$comment_field = preg_replace( '#(.+)(>)(</textarea>)(.*)#i', '${1}${2}' . wp_kses_data( $failedcommentdata ) . '${3}${4}', $comment_field );
		}
		return $comment_field;
	}
} //class

?>
