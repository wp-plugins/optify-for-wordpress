<?php
/*
Plugin Name: Optify for Wordpress
Plugin URI: http://www.optify.net/
Description: The Optify CMS plugin will allow website managers (they do not need to have technical expertise) to quickly, easily, and seamlessly track traffic and leads to their website using the combination of the plugin and the Optify Application.
Version: 1.1.8
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
  if(!empty($_POST["optify_email"]) || !empty($_POST["optify_token"]))
  {
    optify_data_insert();
  }
  else if(!empty($_POST["new_optify_token"]) && strlen($_POST["new_optify_token"]) == 8)
  {
    $post_forms = "on";
    if(empty($_POST["post_forms"]))
      $post_forms = "";
    
    global $wpdb;
    $table_name = $wpdb->prefix . "optify_form";
    $wpdb->query("UPDATE $table_name SET optify_token='" . strtoupper($_POST["new_optify_token"]) . "', post_forms='{$post_forms}'");
  }
}else{
// not in admin.  will submit forms if setting is yes.
  if(!empty($_POST))
  {
    if(preg_match("#@[^.]+[.]#", print_r($_POST, true)))
    {
      global $wpdb;
      $table_name = $wpdb->prefix . "optify_form";
      $row = $wpdb->get_row("SELECT optify_token FROM {$table_name} WHERE post_forms = 'on'");
      if(!empty($row->optify_token))
        post_to_optify($row->optify_token);
    }
  }
}

function flatten(array $d){
  $r = array(); $s = array(); $p = null; reset($d);
  while(!empty($d)){$k = key($d); $el = $d[$k]; unset($d[$k]);  
    if(is_array($el)){
      if (!empty($d)) $s[] = array($d, $p);
      $d = $el;
      $p .= $k . '.';
    }else $res[$p . $k] = $el;
    if (empty($d) && !empty($s)) list($d, $p) = array_pop($s);
  }
  return $res;
}

function post_to_optify($token)
{
  $token = strtoupper($token);
  $url = 'http://service.optify.net/v2/form';
  $fields = array();
    
  // Add the extra fields required to proccess a form by Optify
  $opt_POST = flatten($_POST);
  $opt_POST["_opt_cid"] = $token;
  if(empty($opt_POST["_opt_paget"]))
    $opt_POST["_opt_paget"] = preg_replace("#(http[s]*://[^/]+|[?&\#].*)#", "", $_SERVER["HTTP_REFERER"]);
  $opt_POST["_opt_url"] = $_SERVER["HTTP_REFERER"];
  $opt_POST["_opt_vid"] = $_COOKIE["_opt_vi_" . $opt_POST["_opt_cid"] ];
  $opt_POST["_opt_visit"] = $_COOKIE["_opt_vt_" . $opt_POST["_opt_cid"] ];
  foreach($opt_POST as $key=>$value) {
      $fields[] = "{$key}=" . urlencode($value);
  }
  
  if(function_exists('curl_init')){
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_POST,count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS,implode('&', $fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if( ! $result = curl_exec($ch))
    {
        echo curl_error($ch);
    }
    curl_close($ch);
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
  optify_fname VARCHAR(55) DEFAULT '' NOT NULL,
  optify_lname VARCHAR(55) DEFAULT '' NOT NULL,
  optify_phone int(20) DEFAULT '0' NOT NULL,
  optify_email VARCHAR(55) DEFAULT '' NOT NULL,
  optify_setpwd int(20) DEFAULT '0' NOT NULL,
  optify_token VARCHAR(55) DEFAULT NULL,
  post_forms varchar(16) DEFAULT 'on',
  created_at timestamp,
  UNIQUE KEY id (id)
  );";

  $wpdb->query($sql);

  add_option("optify_db_version", $optify_db_version);
  
  global $optify_existing_token;
  // At install time, see if we already have a token for this URL.
  if(!empty($optify_existing_token))
    $existing_token = $optify_existing_token;
  else
    $existing_token = optify_status_check();
  if(!empty($existing_token))
  {
  	// if we do have a token, add it to the existing config table.
    $row = $wpdb->get_row("SELECT id, optify_token FROM {$table_name}");
    if(empty($row->id)){
      $sql = "insert into {$table_name} (optify_token)values('{$existing_token}')";
      $wpdb->query($sql);
    }else{
      $wpdb->query("update {$table_name} set optify_token = '{$existing_token}' WHERE id={$row->id}");
    }
  
  }
}


// Function to check whether an account is already created for this URL ( $_SERVER['HTTP_HOST'] )
function optify_status_check($verify_schema = false)
{
  global $wpdb;
  if($verify_schema){
    $plugin_data = get_plugin_data(ABSPATH . "wp-content/plugins/optify-for-wordpress/optify_for_wordpress.php");
    $table_name = $wpdb->prefix . "optify_form";
    $cols        = $wpdb->get_results("describe $table_name");
    $post_forms_exists = false;
    foreach($cols as $col_obj)
      if($col_obj->Field == "post_forms")
        $post_forms_exists = true;
    if(! $post_forms_exists)
    {
      optify_uninstall();
      optify_install();
    }
  }
  
  if(function_exists('curl_init')){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://aspen.optify.net/register-test.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);           
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
  }
	return "";
	
}


function optify_uninstall()
{
  global $wpdb, $optify_existing_token;
  $table_name = $wpdb->prefix . "optify_form";
  $optify_existing_token = optify_get_token_from_db();
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
  global $optify_existing_token;
  $table_name = $wpdb->prefix . "optify_form";
  $res        = $wpdb->get_row("SELECT optify_token, created_at, post_forms FROM $table_name ");
  
  if(empty($res->optify_token)){
    if(empty($optify_existing_token))
      $optify_existing_token = optify_status_check(true);

    if(!empty($optify_existing_token))
    {
      $row = $wpdb->get_row("SELECT id, optify_token FROM {$table_name}");
      if(empty($row->id)){
        $sql = "insert into {$table_name} (optify_token)values('{$existing_token}')";
        $wpdb->query($sql);
      }else{
        $wpdb->query("update {$table_name} set optify_token = '{$existing_token}' WHERE id={$row->id}");
      }
        
      $res = $wpdb->get_row("SELECT optify_token, created_at, post_forms FROM {$table_name} ");
    }
  }
   
   
   // If data exists in table then display a friendly page inviting the user to head over to Optify.
   if($res)
   {
     if($res->optify_token != NULL){
        echo "<br /><a style='font-size:1.4em;' href='http://dashboard.optify.net/'>Visit the Optify Application</a><br /><br />";
		    echo "<iframe width='99%' height='800px' src='http://dashboard.optify.net/'></iframe><br /><br />";
        echo "<div style='margin-top: 20px; margin-bottom: 20px; font-size: 16px;'>Your Optify for Wordpress plugin is active since: " . date("Y-m-d", strtotime($res->created_at));
        echo " <a target='_new' href='http://dashboard.optify.net/'>Click to go to your Optify Dashboard</a><br /><br />";
            
        $token = $res->optify_token;
        $post_forms = $res->post_forms;
        echo "<form method='post' style='border: 1px solid #ddd; padding: 20px; width: 180px;'>
            <b>Optify Settings</b><br /><br />
            <label>Optify token</label><br />
            <input type='text' name='new_optify_token' value='" . $token . "' size='9' /><br /><br />
            <label>Post forms to Optify</label><br />
            <input type='checkbox' name='post_forms' " . ($post_forms=="on"?"checked='checked' value='on'":"") . " /><br /><br />
            <input style='background-color:#ddd;' type='submit' name='update_token' value='Update Optify Plugin' />
            </form>";
        echo "</div>";
      }
   } else {
   	// Show the default registration form.
        include('optify_form.php');
   }
   echo "<br />";
   echo "<br />";
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
        $token      = ( isset($_POST['optify_token']) ) ? trim($_POST['optify_token']) : null;
        $token = strtoupper($token);

        $table_name = $wpdb->prefix . "optify_form";
        
        if(!empty($token)){
          $row = $wpdb->get_row("SELECT id, optify_token FROM {$table_name}");
          if(empty($row->id))
            $wpdb->insert($table_name, array('optify_fname' => $f_name, 'optify_lname' => $l_name, 'optify_phone' => $phone
                                     , 'optify_email' => $email, 'optify_setpwd' => $set_psw, 'optify_token' => $token));
          else
            $wpdb->query("update {$table_name} set optify_token = '{$existing_token}' WHERE id={$row->id}");
        }

        if(function_exists('curl_init')){
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, "https://aspen.optify.net/register-test.php");
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_POST, true);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);           
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
              $row = $wpdb->get_row("SELECT id, optify_token FROM {$table_name}");
              if(empty($row->id)){
                $sql = "insert into {$table_name} (optify_token)values('{$token}')";
                $wpdb->query($sql);
              }
          }
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
  // Optify Wordpress Plugin version 1.1.8
  var _opt = _opt || [];
  _opt.push([ 'view', '<?php echo $token;?>']);
  (function() {
    var scr = document.createElement('script'); scr.type = 'text/javascript'; scr.async = true;
    scr.src = '//service.optify.net/opt-v2.js';
    var other = document.getElementsByTagName('script')[0]; other.parentNode.insertBefore(scr, other);
  })();
  setTimeout(function(){
    for(var fi = 0; fi < document.forms.length; fi++){
      if(document.forms[fi].innerHTML.indexOf("_opt_paget") < 0)
        var opt_title = document.createElement('input');
        opt_title.setAttribute('type', 'hidden');
        opt_title.setAttribute('name', '_opt_paget');
        opt_title.setAttribute('value', document.title);
        document.forms[fi].appendChild(opt_title);
    }
  }, 2000);
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
      if(!function_exists('curl_init')){
        echo "<div id='optify-warning' class='updated fade'><p><strong>PHP curl is disabled by your ISP (internet service provider).  If you wish to use the Optify plugin, please contact your website system administrator to enable this feature.</strong></p></div>";
      }else{
        echo "<div id='optify-warning' class='updated fade'><p><strong>".__('You\'re almost up and running with Optify.')."</strong> ";
        echo sprintf(__('<a href="%1$s">Create your Optify Account here</a>!'), "options-general.php?page=Optify")."</p></div>";
      }
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
