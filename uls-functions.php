<?php
 /**
 * This file containg general functions to use in themes or other plugins.
 */

/**
 * Get the general options saved for the plugin.
 *
 * @return array associative array with the options for the plugin.
 */
function uls_get_options(){
  //get the general options
  $options = get_option('uls_settings');

  //default values
  $defaults = array(
    'default_backend_language' => null,
    'default_frontend_language' => null,
    'user_backend_configuration' => true,
    'user_frontend_configuration' => true,
    'backend_language_field_name' => 'uls_backend_language',
    'frontend_language_field_name' => 'uls_frontend_language',
    'url_type' => 'prefix',
  );

  //merge with default values
  return array_merge($defaults, $options);
}

/**
 * This function creates a form to update language selection of an user to display in the front end side. If user isn't logged in or can't change the language, then it returns null.
 *
 * @param $default_language string language code used as default value of the input selector. If it is null, then the language saved by the user is selected.
 * @param $label string label to use for the language field.
 * @param $submit_label string label to use in the button to submit the form.
 * @param $usccess_message string Message to display if language is saved successfully.
 * @param $error_message string Message to display if language isn't saved.
 *
 * @return mixed HTML code of the form as a string. If user isn't logged in or user can't choose a language(settings of the plugin) then null is returned.
 */
function uls_create_user_language_switch_form($default_language = null, $label = null, $submit_label = null, $success_message = null, $error_message = null){
   //check if user is logged in
   if( ! is_user_logged_in() )
      return null;

   //check if the user can change the language
   $options = get_option('uls_settings');
   $type = 'frontend';
   if( ! $options["user_{$type}_configuration"])
      return null;

   //get default values
   $label = empty($label) ? __('Language', 'user-language-switch') : $label;
   $submit_label = empty($submit_label) ? __('Save', 'user-language-switch') : $submit_label;
   $success_message = empty($success_message) ? __('Language saved', 'user-language-switch') : $success_message;
   $error_message = empty($error_message) ? __('Error saving language', 'user-language-switch') : $error_message;

   //get user's language
   $language = get_user_meta(get_current_user_id(), "uls_{$type}_language", true);
   //set the default language if the user doesn't have a preference
   if(empty($language))
      $language = $options["default_{$type}_language"];

   //available languages
   $available_languages = uls_get_available_languages();
   ob_start();

   //include some JS libraries
   wp_enqueue_script('jquery-form');
   ?>
   <div class="uls-user-language-form-div">
      <form id="uls_user_language_form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
         <input type="hidden" name="action" value="uls_user_language_switch" />
         <?php if(function_exists("wp_nonce_field")): ?>
            <?php wp_nonce_field('uls_user_language_switch','uls_wpnonce'); ?>
         <?php endif; ?>
         <label for="uls_language"><?php echo $label; ?></label>
         <select id="uls_language" name="<?php echo $options['frontend_language_field_name']; ?>">
         <?php foreach($available_languages as $langName => $langCode): ?>
            <?php if($langCode == $language): ?>
               <option value="<?php echo $langCode; ?>" selected="selected"><?php echo $langName; ?></option>
            <?php else: ?>
               <option value="<?php echo $langCode; ?>"><?php echo $langName; ?></option>
            <?php endif; ?>
         <?php endforeach; ?>
         </select>
         <div class="uls_submit_div">
            <input type="submit" class="btn" value="<?php echo $submit_label; ?>" />
         </div>
      </form>
      <div id="uls_user_language_message" class="uls_user_language_message"></div>
      <script>
      jQuery(document).ready(function(){
         jQuery("form#uls_user_language_form").ajaxForm({
            beforeSubmit: function(arr, $form, options) {
               jQuery("div#uls_user_language_message").html("");
            },
            success: function (responseText, statusText, xhr, $form){
               var $response = jQuery.parseJSON(responseText);
               if($response.success)
                  jQuery("div#uls_user_language_message").html("<p class='success'><?php echo $success_message; ?></p>");
               else
                  jQuery("div#uls_user_language_message").html("<p class='error'><?php echo $error_message; ?></p>");
            },
            error: function(){
               jQuery("div#uls_user_language_message").html("<p class='error'><?php echo $error_message; ?></p>");
            }
         });
      });
      </script>
   </div>
   <?php
   $res = ob_get_contents();
   ob_end_clean();
   return $res;
}

/**
 * This function save the selection of a language by an user. It gets parameter values from POST variables.
 */
add_action('wp_ajax_uls_user_language_switch', 'uls_save_user_language');
function uls_save_user_language(){
   //check parameters
   if(empty($_POST) || ( function_exists('wp_verify_nonce') && !wp_verify_nonce($_POST['uls_wpnonce'],'uls_user_language_switch') )){
      echo json_encode(array('success' => false));
      exit;
   }

   //save settings for the user
   $options = get_option('uls_settings');

   //if user can save settings and there is a value
   if($options["user_frontend_configuration"] && !empty($_POST[$options['frontend_language_field_name']]) && uls_valid_language($_POST[$options['frontend_language_field_name']]))
      update_user_meta(get_current_user_id(), $options['frontend_language_field_name'], $_POST[$options['frontend_language_field_name']]);

   echo json_encode(array('success' => true));
   exit;
}

/**
 * This function get the URL of the translation of an URL. It retrieve the URL from the mapping saved in the options page.
 *
 * @param $url string URL of the page to get the translation.
 * @param $language string language of translation. If it is null or invalid, current language loaded in the page is used.
 *
 * @return string it returns the URL of the translation or false if the URL isn't contained in the mapping.
 */
function uls_get_url_map_translation($url, $language = null){
  //get language
  if(!uls_valid_language($language))
    $language = uls_get_user_language();

  //get the mappging
  $options = get_option('uls_settings');
  if(isset($options['translation_mapping'][$language][$url]))
    return $options['translation_mapping'][$language][$url];

  return false;
}

?>
