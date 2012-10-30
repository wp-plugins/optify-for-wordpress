<?php
function optify_message(){
   global $wpdb;
   $table_name = $wpdb->prefix . "optify_form";
   $res = $wpdb->get_row("SELECT * FROM $table_name ");
   echo '<div class="wrap">';
   echo $res->optify_email.'&URL=http://'.$_SERVER['HTTP_HOST'].'&first_name=';
   echo $res->optify_fname.'&last_name=';
   echo $res->optify_lname.'&phone=';
   echo $res->optify_phone.'&password=';
   echo $res->optify_setpwd.'=wordpress';
   echo '</div>';
   exit();
}
optify_message();

