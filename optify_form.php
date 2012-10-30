<div class="wrap">
    <div class="optify-main-hedaer"><span class="optify-header-title"><?php echo '<h2>'._('Optify for Wordpress').'</h2>';?></span></div><div class="optify-error"><?php echo $optify_error_message; ?></div><div class="clear"></div>
      <div class="optify-subheader">
         <span class="optify-subheader-title"><?php echo '<h4>'._('Register for Optify to begin tracking your visitors, leads and SEO! ').'</h4>';?></span>
      </div>
      <div class="optify-form">
         <form name="optifyform" method="post" action="" onsubmit="return validation();">
            <div class="optify-label-input">
                <div class="optify-label"><?php _e("First Name" ); ?><span class="optif-star">*</span></div>
                <div class="optify-input"><input class="optify-inbox-box" id="fname" type="text" name="optify_fname" value="" size="20"></div>
                <div class="error123"><span class="error-msz" id="error-msz-fname">This is Required</span></div>
                <div class="clear"></div>
            </div>
            <div class="optify-label-input">
                <div class="optify-label"><?php _e("Last Name" ); ?><span class="optif-star">*</span></div>
                <div class="optify-input"><input class="optify-inbox-box" id="lname" type="text" name="optify_lname" value="" size="20"></div>
                <div class="error123"><span class="error-msz" id="error-msz-lname">This is Required</span></div>
                <div class="clear"></div>
            </div>
            <div class="optify-label-input">
                <div class="optify-label"><?php _e("Phone" ); ?><span class="optif-star">*</span></div>
                <div class="optify-input"><input class="optify-inbox-box" id="phone" type="text" name="optify_phone" value="" size="20"></div>
                <div class="error123"><span class="error-msz" id="error-msz-phone">This is Required</span></div>
                <div class="clear"></div>
            </div>
            <div class="optify-label-input">
                <div class="optify-label"><?php _e("Email" ); ?><span class="optif-star">*</span></div>
                <div class="optify-input"><input class="optify-inbox-box" id="email" type="text" name="optify_email" value="" size="20"></div>
                <div class="error123"><span class="error-msz" id="error-msz-email">This is Required</span></div>
                <div class="clear"></div>
            </div>
            <div class="optify-label-input">
                <div class="optify-label"><?php _e("Set your password" ); ?><span class="optif-star">*</span></div>
                <div class="optify-input"><input class="optify-inbox-box" id="set-pwd" type="password" name="optify_setpwd" value="" size="20"></div>
                <div class="error123"><span class="error-msz" id="error-msz-pwd">This is Required</span></div>
                <div class="clear"></div>
            </div>
             <div class="optify-submit">
                 <input class="optify-submit-button" type="submit" name="submit" value="<?php _e('Register for Optify!' ) ?>" />
             </div>
<div class="clear">&nbsp;</div>
<div class="clear">&nbsp;</div>
<div class="clear">&nbsp;</div>
<div class="clear">&nbsp;</div>
<div class="clear">&nbsp;</div>

      <div class="optify-subheader">
             <b style="font-size:1.4em; color: #1B66B8;">Already have an Optify account?</b>
      </div>
<div class="clear">&nbsp;</div>
             <div class="optify-label-input">
                <div class="optify-label"><?php _e("Optify tracking token (8 characters)" ); ?></div>
                <div class="optify-input"><input class="optify-inbox-box" id="optify_token" type="text" name="optify_token" value="" size="20"></div>
                <div class="error123"><span class="error-msz" id="error-msz-token">This is Required</span></div>
                <div class="clear"></div>
            </div>
             <div class="optify-submit">
                 <input class="optify-submit-button" type="submit" name="submit" value="Submit" />
             </div>



             </form>
     </div>
 </div>