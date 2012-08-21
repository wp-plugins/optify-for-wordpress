<?php
/*
Plugin Name: Optify for Wordpress
Plugin URI: http://www.optify.net/
Description: The Optify CMS plugin will allow website managers (they do not need to have technical expertise) to quickly, easily, and seamlessly track traffic and leads to their website using the combination of the plugin and the Optify Application.
Version: 1.0
Author: Optify Development
Author URI: http://www.optify.net/
License: GPL
*/

global $optify_db_version;
$optify_db_version = "1.0";

// call when plugin is activated by admin
register_activation_hook(__FILE__,'optify_install');

//call when plugin is deactivated
register_deactivation_hook(__FILE__,'optify_uninstall');

//call the css
add_action('admin_print_styles', 'optify_css');

//call the javascript
add_action( 'admin_enqueue_scripts', 'optify_script' );

// call to footer function
add_action('wp_footer', 'optify_script_footer');

// register the plugin functions with wordpress
if ( is_admin() )
{
        /* Call the html code */
        add_action('admin_menu', 'optify_for_Wordpress');
        
        // Add a global admin message if Optify is installed but not registered.
        optify_admin_warnings();
        
		// add a settings link on the plugin list page.
        add_filter("plugin_action_links_" . plugin_basename(__FILE__), 'optify_plugin_settings_link' );
        
        // if there is a form post with the optify_email attribute, then let's do the "data insert".
        if(!empty($_POST["optify_email"]))
        {
        	optify_data_insert();
        }
}

// A global error message variable to be displayed after form submission.
$optify_error_message = "";

function optify_install()
{
  global $wpdb;
  global $optify_db_version;

  $table_name = $wpdb->prefix . "optify_form";

  // changed created_at to a timestamp data type.
  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  optify_fname VARCHAR(55) NOT NULL,
  optify_lname VARCHAR(55) NOT NULL,
  optify_phone int(20) NOT NULL,
  optify_email VARCHAR(55) DEFAULT '' NOT NULL,
  optify_setpwd int(20) NOT NULL,
  optify_token VARCHAR(55) DEFAULT NULL,
  created_at timestamp,
  UNIQUE KEY id (id)
  );";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);

  add_option("optify_db_version", $optify_db_version);
  
  // At install time, see if we already have a token for this URL.
  $existing_token = optify_status_check();
  if(!empty($existing_token))
  {
  	// if we do have a token, add it to the existing config table.
  	$table_name = $wpdb->prefix . "optify_form";
  	$wpdb->insert($table_name, array('optify_fname' => '', 'optify_lname' => '', 'optify_phone' => ''
  						, 'optify_email' => '', 'optify_setpwd' => '', 'optify_token' => $existing_token));
  
  }
}


// Function to check whether an account is already created for this URL ( $_SERVER['HTTP_HOST'] )
function optify_status_check()
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://aspen.optify.net/register-cms.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	// Only post the URL parameter.  This will never create an acccount and only checks for existence.
	$data = 'URL=http://'.$_SERVER['HTTP_HOST'];
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$output = curl_exec($ch);
	curl_close($ch);
	$js = json_decode($output);
	if(!empty($js->status) && $js->status == "success")
	{
		return $js->token;
	}
	return "";
	
}


function optify_uninstall()
{
  global $wpdb;
  $table_name = $wpdb->prefix . "optify_form";
  // remove the config table.
  $wpdb->query("DROP TABLE {$table_name}");
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
}


function optify_for_Wordpress() {
  add_options_page('Optify for Wordpress', 'Optify for Wordpress', 'administrator','Optify', 'optify_for_Wordpress_html_page');
}

function optify_for_Wordpress_html_page() {
  optify_data_fetch();
}

function optify_data_fetch()
{
   global $wpdb;
   $table_name = $wpdb->prefix . "optify_form";
   $res        = $wpdb->get_row("SELECT optify_token, created_at FROM $table_name ");
   // If data exists in table then display a friendly page inviting the user to head over to Optify.
   if($res)
   {
        if($res->optify_token != NULL){
            echo "<div style='margin-top: 20px; margin-bottom: 20px; font-size: 16px;'>Your Optify for Wordpress plugin is active since: " . date("Y-m-d", strtotime($res->created_at)) . "</div>";
		    echo "<div style='margin-bottom: 20px; font-size: 16px;'><a target='_new' href='http://dashboard.optify.net/'>Click to go to your Optify Dashboard</a></div>";
		    echo "<a target='_new' href='http://dashboard.optify.net/'><img style='padding: 10px; border:4px solid #21759B' src='" . plugins_url('optify-screenshot-resize.png', __FILE__) . "' /></a><br /><br />";
			echo "Please <a href='http://help.optify.net/requests/anonymous/new'>contact Optify Support</a> if you have any issues or questions regarding Optify or our Wordpress Plugin.</a>";
        }
   } else {
   	// Show the default registration form.
        include('optify_form.php');
   }
}


function optify_data_insert(){
   global $wpdb;
   global $optify_error_message;
   
   if(isset($_POST['submit'])){
        $f_name     =  ( isset($_POST['optify_fname']) ) ? trim($_POST['optify_fname']) : null;
        $l_name     = ( isset($_POST['optify_lname']) ) ? trim($_POST['optify_lname']) : null;
        $phone      = ( isset($_POST['optify_phone']) ) ? trim($_POST['optify_phone']) : null;
        $email      = ( isset($_POST['optify_email']) ) ? trim($_POST['optify_email']) : null;
        $set_psw    = ( isset($_POST['optify_setpwd']) ) ? trim($_POST['optify_setpwd']) : null;
        //$token      = ( isset($_POST['optify_token']) ) ? trim($_POST['optify_token']) : null;


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://aspen.optify.net/register-cms.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        $data = 'email='.$email.'&URL=http://'.$_SERVER['HTTP_HOST'].'&first_name='.$f_name.'&last_name='.$l_name.'&phone='.$phone.'&password='.$set_psw.'&cms=Wordpress';
		
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $result = json_decode($output);
        $token = $result->token;
        if(!empty($result->message))
        	$optify_error_message = '<span style="color:red">'.$result->message.'</span>';
       	else
       		$optify_error_message = '<span style="color:red">Unable to connect to Optify registration service</span>';
       	
        if($result->status == 'success' || !empty($result->token)){
        	 // Account successfully created or account already exists in Optify
             $table_name = $wpdb->prefix . "optify_form";
             $wpdb->insert($table_name, array('optify_fname' => $f_name, 'optify_lname' => $l_name, 'optify_phone' => $phone
                                         , 'optify_email' => $email, 'optify_setpwd' => $set_psw, 'optify_token' => $token));
        }

    }
}
//css script
function optify_css() {
    wp_enqueue_style('optify-css-all',plugins_url('css/optify_style.css', __FILE__));
}
//javascript
function optify_script() {
    wp_enqueue_script( 'optify_custom_script', plugins_url('js/optify_script.js', __FILE__) );
 }

//javascript add to footer in frontend
function optify_script_footer()
{
   global $wpdb;
   $table_name = $wpdb->prefix . "optify_form";
   $res        = $wpdb->get_row("SELECT optify_token FROM $table_name ");
   // If data exists in table
   if($res)
   {
            if($res->optify_token != NULL)
            {
                 $token = $res->optify_token;
            }?>
  <script type="text/javascript">
       // Optify Wordpress Plugin
       var _opt = _opt || [];
       _opt.push([ 'view', '<?php echo $token;?>']);
       (function() {
             var scr = document.createElement('script'); scr.type = 'text/javascript'; scr.async = true;
             scr.src = '//service.optify.net/opt-v2.js';
             var other = document.getElementsByTagName('script')[0]; other.parentNode.insertBefore(scr, other);
       })();
  </script>
	<?php
	} 
}





// function to check table configuration for optify token.
function optify_get_token_from_db()
{
	global $wpdb;
	$token = "";
	$table_name = $wpdb->prefix . "optify_form";
	$res        = $wpdb->get_row("SELECT optify_token FROM $table_name ");
	// If data exists in table
	if($res) {
		if($res->optify_token != NULL){
			$token = $res->optify_token;
		}
		return $token;

	}
}


// display a global admin message if Optify is activated but not configured.
function optify_admin_warnings()
{
	$db_token = optify_get_token_from_db();
	
	// don't display the message if we're on on "optify" page.
	if ( empty($db_token) && !preg_match("#optify#", strtolower($_SERVER["REQUEST_URI"])))
	{
		function optify_warning()
		{
			echo "<div id='optify-warning' class='updated fade'><p><strong>".__('You\'re almost up and running with Optify.')."</strong> ";
			echo sprintf(__('<a href="%1$s">Create your Optify Account here</a>!'), "options-general.php?page=Optify")."</p></div>";
		}
		add_action('admin_notices', 'optify_warning');
		return;
	}
}


// add a settings link on the plugin list page.
function optify_plugin_settings_link($links)
{
	$settings_link = '<a href="options-general.php?page=Optify">Settings</a>';
	array_push($links, $settings_link);
	return $links;
}
