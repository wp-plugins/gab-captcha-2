<?php

// security check
if ( !defined( 'WP_PLUGIN_DIR') )
{
	die("There is nothing to see here.");
}

$gabcaptcha2_plugin_dir = WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__));
$gabcaptcha2_plugin_url = WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__));


class GabCaptcha2_Options
{

	/* Array of sections for the theme options page */
	private $sections;
	private $checkboxes;


	/* Initialize */
	function __construct()
	{
		$this->checkboxes = array();

		$this->sections['general']  = array( __("General options", "gabcaptcha2"), 'display_section_general_callback' );
		$this->sections['captcha']  = array( __("Captcha options", "gabcaptcha2"), 'display_section_captcha_callback' );
		$this->sections['security'] = array( __("Security"       , "gabcaptcha2"), 'display_section_security_callback' );

		add_action( 'admin_menu', array( &$this, 'add_pages_callback' ) );
		add_action( 'admin_init', array( &$this, 'register_settings_callback' ) );

		/*if ( get_option( 'gabcaptcha2_options' ) === FALSE )
		{
			$this->initialize_settings();
		}*/
	}

	/* Add page(s) to the admin menu */
	public function add_pages_callback()
	{
		//$admin_page = add_theme_page( 'Theme Options', 'Theme Options', 'manage_options', 'mytheme-options', array( &$this, 'display_page' ) );
		$admin_page = add_options_page(__('Gab Captcha 2', "gabcaptcha2"), __('Gab Captcha 2', "gabcaptcha2"), 'manage_options', 'gabcaptcha2_options_page_id', Array( &$this, 'gabcaptcha2_options_page' ) );
	}

	/* HTML to display the theme options page */
	public function gabcaptcha2_options_page()
	{

		echo '<div class="wrap">
		<div class="icon32" id="icon-options-general"></div>
		<h2>' . __( "Gab Captcha 2 Options", "gabcaptcha2" ) . '</h2>
		<form action="options.php" method="post">
			';
			settings_fields( 'gabcaptcha2_options_group' );
			do_settings_sections( 'gabcaptcha2_options_page_id' );

			echo '<p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . __( "Save Changes", "gabcaptcha2" ) . '" /></p>

		</form>

		<p>Translated by <a href="' . __("http://www.gabsoftware.com/", 'gabcaptcha2') . '">' . __("Gabriel Hautclocq", 'gabcaptcha2') . '</a></p>'
		;
	}

	/* Create settings field */
	public function create_setting( $args = array() )
	{
		$defaults = array(
			'id'      => 'default_field',
			'title'   => __('Default Field', "gabcaptcha2"),
			'desc'    => __('This is a default description.', "gabcaptcha2"),
			'std'     => '',
			'type'    => 'text',
			'section' => 'general',
			'choices' => array(),
			'class'   => '',
			'size'    => ''
		);

		extract( wp_parse_args( $args, $defaults ) );

		$field_args = array(
			'type'      => $type,
			'id'        => $id,
			'desc'      => $desc,
			'std'       => $std,
			'choices'   => $choices,
			'label_for' => $id,
			'class'     => $class,
			'size'      => $size
		);

		if ( $type == 'checkbox' )
		{
			$this->checkboxes[] = $id;
		}

		add_settings_field( $id, $title, array( $this, 'display_setting_callback' ), 'gabcaptcha2_options_page_id', $section, $field_args );
	}

	/* Register settings via the WP Settings API */
	public function register_settings_callback()
	{
		register_setting( 'gabcaptcha2_options_group', 'gabcaptcha2_options', array ( &$this, 'gabcaptcha2_options_validate_callback' ) );

		foreach ( $this->sections as $slug => $section )
		{
			$title    = $section[0];
			$callback = $section[1];
			add_settings_section( $slug, $title, array( &$this, $callback ), 'gabcaptcha2_options_page_id' );
		}

		/* General options
		===========================================*/

		$this->create_setting( array(
			'id'      => 'display_credits',
			'title'   => __( "Display credits:", "gabcaptcha2"),
			'desc'    => __( "Credits will be displayed on the bottom of the Captcha section.", "gabcaptcha2"),
			'std'     => '',
			'type'    => 'radio',
			'section' => 'general',
			'choices' => array(
				'1' => __("As link", "gabcaptcha2"),
				'2' => __("As text", "gabcaptcha2"),
				'3' => __("Off", "gabcaptcha2")
			)
		) );

		$this->create_setting( array(
			'id'      => 'automatically_approve',
			'title'   => __( "Automatically approve comments who passed the test", "gabcaptcha2"),
			'desc'    => __( "If checked, your comment will be immediately approved and spam comments will be automatically placed in the trash.", "gabcaptcha2"),
			'std'     => 'on',
			'type'    => 'checkbox',
			'section' => 'general'
		) );


		/* Captcha options
		===========================================*/

		$this->create_setting( array(
			'id'      => 'captcha_label',
			'title'   => __("Captcha label:", "gabcaptcha2"),
			'desc'    => __( "This label will be shown to the unregistered visitors", "gabcaptcha2" ),
			'std'     => __( "Prove that you are Human by typing the emphasized characters:", "gabcaptcha2" ),
			'type'    => 'text',
			'section' => 'captcha',
			'size'    => 60
		) );

		$this->create_setting( array(
			'id'      => 'captcha_length',
			'title'   => __("Captcha length:", "gabcaptcha2"),
			'desc'    => __( "How many characters are displayed in the captcha (2 to 64). 24 should be enough.", "gabcaptcha2" ),
			'std'     => 24,
			'type'    => 'text',
			'section' => 'captcha'
		) );

		$this->create_setting( array(
			'id'      => 'captcha_solution_length',
			'title'   => __("Solution length:", "gabcaptcha2"),
			'desc'    => __( "How many characters the users will have to write (1 to 24). Must be less than the captcha length set previously. Do not set to a too high value!", "gabcaptcha2" ),
			'std'     => 4,
			'type'    => 'text',
			'section' => 'captcha'
		) );


		/* Security options
		===========================================*/

		$this->create_setting( array(
			'id'      => 'output_method',
			'title'   => __( "Method to output the Captcha:", "gabcaptcha2"),
			'desc'    => __( "This is a compromise between better compatibility and better security.", "gabcaptcha2"),
			'std'     => '',
			'type'    => 'radio',
			'section' => 'security',
			'choices' => array(
				'std' => __("Standard: medium security, high compatibility", "gabcaptcha2"),
				'css' => __("CSS: improved security, compatible with CSS-capable browsers", "gabcaptcha2"),
				'css3' => __("CSS 3: better security, but reduces compatibility to CSS3-compliant browsers", "gabcaptcha2")
			)
		) );

	}

	/* Description for section */
	public function display_section_callback()
	{
		// code
	}

	//display the General options section
	public function display_section_general_callback()
	{
		echo '<p>' . __('This section concerns the general options of Gab Captcha 2.', 'gabcaptcha2') . '</p>';
	}

	//display the Captcha options section
	public function display_section_captcha_callback()
	{
		echo '<p>' . __('This section proposes settings related to the captcha.', 'gabcaptcha2') . '</p>';
	}

	//display the Security section
	public function display_section_security_callback()
	{
		echo '<p>' . __('This section contains security settings.', 'gabcaptcha2') . '</p>';
	}

	//validate the input data
	public function gabcaptcha2_options_validate_callback($input)
	{
		//load the current options
		$newinput = get_option('gabcaptcha2_options');

		foreach ( $this->checkboxes as $id )
		{
			if ( isset( $newinput[$id] ) && ! isset( $input[$id] ) )
			{
				unset( $newinput[$id] );
			}
		}

		if( isset( $input['display_credits'] ) )
		{
			$newinput['display_credits'] = intval($input['display_credits']);
			if( $newinput['display_credits'] < 1 || $newinput['display_credits'] > 3 )
			{
				$newinput['display_credits'] = 1;
			}
		}

		if( isset( $input['automatically_approve'] ) )
		{
			$newinput['automatically_approve'] = trim($input['automatically_approve']);
			if(!preg_match('/^(on|off)$/i', $newinput['automatically_approve']))
			{
				$newinput['automatically_approve'] = 'off';
			}
		}

		if( isset( $input['captcha_label'] ) )
		{
			$newinput['captcha_label'] = trim($input['captcha_label']);
			if( $newinput['captcha_label'] == '' )
			{
				$newinput['captcha_label'] = 'Prove that you are Human by typing the emphasized characters:';
			}
		}

		if( isset( $input['captcha_label'] ) )
		{
			$newinput['captcha_length'] = intval($input['captcha_length']);
			if( $newinput['captcha_length'] < 2 )
			{
				$newinput['captcha_length'] = 2;
			}
			if( $newinput['captcha_length'] > 64 )
			{
				$newinput['captcha_length'] = 64;
			}
		}

		if( isset( $input['captcha_solution_length'] ) )
		{
			$newinput['captcha_solution_length'] = intval($input['captcha_solution_length']);
			if( $newinput['captcha_solution_length'] < 1 )
			{
				$newinput['captcha_solution_length'] = 1;
			}
			if( $newinput['captcha_solution_length'] > 24 )
			{
				$newinput['captcha_solution_length'] = 24;
			}
		}

		if( isset( $input['output_method'] ) )
		{
			$newinput['output_method'] = trim($input['output_method']);
			if(!preg_match('/^(std|css|css3)$/i', $newinput['output_method']))
			{
				$newinput['output_method'] = 'std';
			}
		}

		return $newinput;
	}

	/* HTML output for individual settings */
	public function display_setting_callback( $args = array() )
	{

		extract( $args );

		$options = get_option( 'gabcaptcha2_options' );

		if ( !isset( $options[$id] ) && 'type' != 'checkbox' )
		{
			$options[$id] = $std;
		}

		$field_class = '';
		if ( $class != '' )
		{
			$field_class = ' class="' . $class . '"';
		}
		switch ( $type )
		{
			case 'heading':
				echo '</td></tr><tr valign="top"><td colspan="2">' . $desc;
				break;

			case 'checkbox':
				$checked = '';
				if ( isset( $options[$id] ) && $options[$id] == 'on' )
				{
					$checked = ' checked="checked"';
				}

				echo '<input' . $field_class . ' type="checkbox" id="' . $id . '" name="gabcaptcha2_options[' . $id . ']" value="on"' . $checked . ' />
				<label for="' . $id . '">' . $desc . '</label>';

				break;

			case 'select':
				echo '<select' . $field_class . ' name="gabcaptcha2_options[' . $id . ']">';

				foreach ( $choices as $value => $label )
				{
					$selected = '';
					if ( $options[$id] == $value )
					{
						$selected = ' selected="selected"';
					}
					echo '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
				}

				echo '</select>';

				if ( $desc != '' )
				{
					echo '<br /><small>' . $desc . '</small>';
				}

				break;

			case 'radio':
				$i = 0;
				foreach ( $choices as $value => $label )
				{
					$checked = '';
					if ( $options[$id] == $value )
					{
						$checked = ' checked="checked"';
					}
					echo '
					<input' . $field_class . ' type="radio" name="gabcaptcha2_options[' . $id . ']" id="' . $id . $i . '" value="' . $value . '"' . $checked .' />
					<label for="' . $id . $i . '">' . $label . '</label>
					';
					//if ( $i < count( $options ) - 1 )
					//{
					echo '<br />';
					//}
					$i++;
				}

				if ( $desc != '' )
				{
					echo '<br /><small>' . $desc . '</small>';
				}

				break;

			case 'textarea':
				echo '<textarea' . $field_class . ' id="' . $id . '" name="gabcaptcha2_options[' . $id . ']" placeholder="' . $std . '">' . $options[$id] . '</textarea>';

				if ( $desc != '' )
				{
					echo '<br /><small>' . $desc . '</small>';
				}

				break;

			case 'password':
				echo '<input' . $field_class . ' type="password" id="' . $id . '" name="gabcaptcha2_options[' . $id . ']" value="' . $options[$id] . '" />';

				if ( $desc != '' )
				{
					echo '<br /><small>' . $desc . '</small>';
				}

				break;

			case 'text':
			default:
				$sizeattribute = '';
				if ( isset ($size) )
				{
					$sizeattribute = ' size="' . $size . '"';
				}
				echo '<input' . $field_class . ' type="text" id="' . $id . '" name="gabcaptcha2_options[' . $id . ']" placeholder="' . $std . '" value="' . $options[$id] . '"' . $sizeattribute . ' />';
				if ( $desc != '' )
				{
					echo '<br /><small>' . $desc . '</small>';
				}
				break;
		}
	}

}

?>