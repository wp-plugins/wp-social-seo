<?php
error_reporting(0);
/**
 * Plugin Name: Wp Social
 * Plugin URI: http://www.web9.co.uk/
 * Description: Use structured data markup embedded in your public website to specify your preferred social profiles. You can specify these types of social profiles: Facebook, Twitter, Google+, Instagram, YouTube, LinkedIn and Myspace.
 * Version: 4.02
 * Author: Jody Nesbitt (WebPlugins)
 * Author URI: http://webplugins.co.uk
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
if (!class_exists('Wps_Review_List_Table')) {
    require_once( plugin_dir_path(__FILE__) . 'class/class-wps-review-list-table.php' );
}
if (!class_exists('NMRichReviewsAdminHelper')) {
    require_once(plugin_dir_path(__FILE__) . 'class/admin-view-helper-functions.php');
}
if (!class_exists('reviews')) {
    require_once( plugin_dir_path(__FILE__) . 'class/class-reviews.php' );
}
if (!class_exists('fbpost')) {
    require_once( plugin_dir_path(__FILE__) . 'class/class-fb-post.php' );
}
add_action('widgets_init', 'wps_load_widget');

add_action('admin_menu', 'wps_admin_init');
add_action('admin_post_submit-wnp-settings', 'wpsSaveSettings');
add_action('admin_post_submit-wps-company', 'wpsSaveCompany');
add_action('admin_post_submit-facebook-review', 'wpsFacebookReview');
add_action('admin_post_submit-rich-snippets-review', 'wpsSaveRichSnippets');
add_action('admin_post_submit-color-picker', 'saveSocialSeoColorPicker');

add_shortcode('facebook-review-slider', 'bartag_func');
add_shortcode('wps-rich-snippets', 'display_rich_snippets');
add_shortcode('wps-rich-snippets-all', 'display_all_rich_snippets');

function wps_load_widget() {
    register_widget('reviews');
    register_widget('fbpost');
}

function wps_admin_init() {
    global $wpdb;
    $sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "rich_snippets_review" . "` (
            `id` bigint(20) unsigned NOT NULL auto_increment,
            `item_name` varchar(255) default NULL,
            `reviewer_name` varchar(255) default NULL,
            `date_reviewed` varchar(255) default NULL,
            `summary` TEXT DEFAULT NULL,            
            `description` TEXT DEFAULT NULL,
            `rating` int(10) NOT NULL,
            `status` int(10) DEFAULT 1,
            `dateCreated` timestamp NOT NULL,
            PRIMARY KEY (`id`))ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
    $wpdb->query($sql);
    $table_name = $wpdb->prefix . 'rich_snippets_review';
    $tableNameArray = array();
    foreach ($wpdb->get_col("DESC " . $table_name, 0) as $column_name) {
        $tableNameArray[] = $column_name;
    }
    if (!in_array('url', $tableNameArray)) {
        $wpdb->query("ALTER TABLE `" . $wpdb->prefix . "rich_snippets_review" . "` ADD url TEXT NOT NULL AFTER `description`");
    }
    add_menu_page(__('Structured Markup', 'wps'), __('Structured Markup', 'wps'), 'manage_options', 'wps-social-profile', 'wpscallWebNicePlc', '');
    //add_submenu_page('', __('Your company', 'wps'), __('Your company', 'wps'), 'manage_options', 'wps-manage-your-company', 'wpsmanageCompany');
    add_submenu_page('', __('Social seo', 'wps'), __('Social seo', 'wps'), 'manage_options', 'wps-manage-social-seo', 'wpsmanageSocialSeo');
    add_submenu_page('', __('Facebook review', 'wps'), __('Facebook review', 'wps'), 'manage_options', 'wps-facebook-review', 'wpsmanageFacebookReview');
    add_submenu_page('', __('Rich snippets review', 'wps'), __('Rich snippets review', 'wps'), 'manage_options', 'wps-rich-snippets-review', 'wpsmanageRichSnippets');
    add_submenu_page('', __('Rich snippets review', 'wps'), __('Rich snippets review', 'wps'), 'manage_options', 'wps-add-rich-snippets-review', 'wpsmanageAddRichSnippets');
    add_submenu_page('', __('Rich snippets review', 'wps'), __('Rich snippets review', 'wps'), 'manage_options', 'wps-delete-snipepts-review', 'wpsmanageDeleteRichSnippets');
    add_submenu_page('', __('Feeds', 'wps'), __('Feeds', 'wps'), 'manage_options', 'wps-feeds', 'wpsFeeds');
    add_submenu_page('', __('Custom Text', 'wps'), __('Custom Text', 'wps'), 'manage_options', 'wps-custom-text', 'wpsCustomText');
}

function wps_load_custom_wp_admin_style() {
    wp_enqueue_style('wpsadminstyle', plugins_url('css/wps-admin-style.css', __FILE__));
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-form');
    wp_register_style('colorpickcss', plugins_url('css/colpick.css', __FILE__), array(), '20120208', 'all');
    wp_enqueue_script('colorpickjs', plugins_url('js/colpick.js', __FILE__), array(), '1.0.0', true);
    wp_enqueue_style('colorpickcss');
}

add_action('admin_enqueue_scripts', 'wps_load_custom_wp_admin_style');

function wpscallWebNicePlc() {
    $pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
    wp_register_style('wp-social-css', $pluginDirectory . 'css/wp-social-seo.css');
    wp_enqueue_style('wp-social-css');
    $get_option_details = unserialize(get_option('wnp_your_company'));
    $my_plugin_tabs = array(
        'wps-social-profile' => 'Your Company',
        'wps-manage-social-seo' => 'Social',
        'wps-rich-snippets-review' => 'Onsite Reviews',
        'wps-facebook-review' => 'Facebook Reviews',
        'wps-feeds' => 'Feeds',
        'wps-custom-text' => 'Custom Text'
    );
    echo admin_tabs($my_plugin_tabs);
    ?>

    <script>
        jQuery(document).ready(function () {
            jQuery("body").addClass("wps-admin-page")
            // binds form submission and fields to the validation engine
            jQuery('#companyID').ajaxForm({
                beforeSubmit: wpsValidate,
                success: function (data) {
                    jQuery('.success').show();
                }
            });
            jQuery(".wps-postbox-container .handlediv, .wps-postbox-container .hndle").on("click", function (n) {
                return n.preventDefault(), jQuery(this).parent().toggleClass("closed");
            });
        });
        function wpsValidate() {
            var usernameValue = jQuery('select[name=type]').fieldValue();
            var urlValue = jQuery('input[name=url]').fieldValue();
            var nameValue = jQuery('input[name=name]').fieldValue();
            var telephone = jQuery('input[name=telephone]').fieldValue();
            var logourlValue = jQuery('input[name=logo-url]').fieldValue();
            // usernameValue and passwordValue are arrays but we can do simple
            // "not" tests to see if the arrays are empty
            if (!usernameValue[0]) {
                alert('Please enter a value for the Type');
                return false;
            }
            if (!nameValue[0]) {
                alert('Please enter a value for the Name');
                return false;
            }
            if (!urlValue[0]) {
                alert('Please enter a value for the Url');
                return false;
            }
            if (!logourlValue[0]) {
                alert('Please enter a value for the Logo url');
                return false;
            }
            if (!telephone[0]) {
                alert('Please enter telephone number');
                return false;
            }
            return true;
        }
    </script>        
    <div class="wrap">                    
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="left-side">
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Company Information');
                ?>
                <!--            <div class="postbox" id="ppw_global_postbox">               -->
                <div class="inside">                               
                    <form id="companyID" method="post" action="<?php echo get_admin_url() ?>admin-post.php">  
                        <fieldset>                            
                            <input type='hidden' name='action' value='submit-wps-company' />                            
                            <div>
                                <div class="alert-box success" style="display:none;"><span>Success : </span>Your company settings has been saved successfully</div>
                                <table cellpadding="0" cellspacing="0" border="0" width="600" class="form-table">
                                    <tr height="50">
                                        <td width="150">Type : </td>
                                        <td>    
                                            <select class="validate[required] text-input" id="type" name="type">
                                                <?php
                                                $org = '';
                                                $personal = '';
                                                if ($get_option_details['type'] == 'Organization')
                                                    $org = 'selected="selected"';
                                                if ($get_option_details['type'] == 'Personal')
                                                    $personal = 'selected="selected"';
                                                ?>
                                                <option value="Organization" <?php echo $org; ?> >Organization</option>
                                                <option value="Personal" <?php echo $personal; ?>>Personal</option>
                                            </select>                                            
                                        </td>
                                    </tr>     
                                    <tr height="50">
                                        <td>Name : </td>
                                        <td><input type="text" class="validate[required] text-input" id="name" name="name" value="<?php echo $get_option_details['name']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Url : </td>
                                        <td><input type="text" class="validate[required] text-input" id="url" name="url" value="<?php echo $get_option_details['url']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Logo Url : </td>
                                        <td><input type="text" class="validate[required] text-input" id="logo-url" name="logo-url" value="<?php echo $get_option_details['logo-url']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Telephone : </td>
                                        <td><input type="text" class="validate[required] text-input" id="telephone" name="telephone" value="<?php echo $get_option_details['telephone']; ?>" /> </td>
                                    </tr>
        <!--                                    <tr height="50">
                                        <td>Other telephone : </td>
                                        <td><input type="text" class="validate[required] text-input" id="other_telephone" name="other_telephone" value="<?php echo $get_option_details['other_telephone']; ?>" /> </td>
                                    </tr>-->
                                    <tr height="50">
                                        <td>Contact Type : </td>
                                        <td>
                                            <select class="validate[required] text-input" id="type" name="contact_type">
                                                <option value="">Select contact type</option>
                                                <?php
                                                $contact_types = array('customer support', 'technical support', 'billing support', 'bill payment', 'sales', 'reservations', 'credit card support', 'emergency', 'baggage tracking', 'roadside assistance', 'package tracking');
                                                foreach ($contact_types as $contact_type) {
                                                    if ($get_option_details['contact_type'] == $contact_type) {
                                                        $selected_contact_type = 'selected="selected"';
                                                    } else {
                                                        $selected_contact_type = '';
                                                    }
                                                    echo '<option value="' . $contact_type . '" ' . $selected_contact_type . '>' . ucfirst($contact_type) . '</option>';
                                                }
                                                ?>                                                                                                
                                            </select>                                            
                                        </td>
                                    </tr>
                                    <tr height="50">
                                        <td>Area served : </td>
                                        <td><input type="text" class="text-input" id="area_served" name="area_served" value="<?php echo $get_option_details['area_served']; ?>" />                                          
                                        </td>
                                    </tr>
        <!--                                    <tr height="50">
                                        <td>Contact option: </td>
                                        <td>
                                            <select class="validate[required] text-input" id="contact_option" name="contact_option[]" multiple="multiple">
                                                <option value="">Select contact option</option>
                                    <?php
                                    $explaoded_ct_options = explode(',', $get_option_details['contact_option']);
                                    foreach ($explaoded_ct_options as $explaoded_ct_option) {
                                        if ($explaoded_ct_option == 'TollFree')
                                            $tollfree = 'selected="selected"';
                                        if ($explaoded_ct_option == 'HearingImpairedSupported')
                                            $hearing = 'selected="selected"';
                                    }
                                    ?>
                                                <option value="TollFree" <?php echo $tollfree; ?> >TollFree</option>
                                                <option value="HearingImpairedSupported" <?php echo $hearing; ?>>HearingImpairedSupported</option>
                                            </select>
                                        </td>
                                    </tr>-->
                                    <tr height="50">
                                        <td>Available language : </td>
                                        <td>
                                            <input type="text" id="avail_language" name="avail_language" value="<?php echo $get_option_details['avail_language']; ?>" />                                            
                                        </td>
                                    </tr>                                                                        
                                </table>
                            </div>                         
                            <input class="button-primary" type="submit" value="Submit" name="submit" />    
                        </fieldset>
                    </form>
                </div>               
                <!--            </div>   -->
                <?php
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('About');
                render_rr_show_content();
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
            </div>            
        </div>    
        <?php displayRight(); ?>
    </div>
    <?php
}

function render_rr_show_content() {
    $output = '<p><strong>WP Social SEO</strong> gives you the ability to quick add your Social Profiles in a compliant way so that it shows up in a google search.</p>
               <p>Specify your social profiles to Google <a href="https://developers.google.com/webmasters/structured-data/customize/social-profiles" target="_blank">https://developers.google.com/webmasters/structured-data/customize/social-profiles</a></p>
               <p>Use mark-up on your official website to add your social profile information to the Google Knowledge panel in some searches. Knowledge panels can prominently display your social profile information.</p>
               <p>Our other free plugins can be found at <a href="https://profiles.wordpress.org/pigeonhut/" target="_blank">https://profiles.wordpress.org/pigeonhut/</a> </p>
               <p>To see more about us as a company, visit <a href="http://www.web9.co.uk" target="_blank">http://www.web9.co.uk</a></p>
               <p>Proudly made in Belfast, Northern Ireland.</p>';
    echo $output;
}

function wpsmanageSocialSeo() {
    $pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
    wp_register_style('wp-social-css', $pluginDirectory . 'css/wp-social-seo.css');
    wp_enqueue_style('wp-social-css');
    $get_option_details = unserialize(get_option('wnp_social_settings'));
    $my_plugin_tabs = array(
        'wps-social-profile' => 'Your Company',
        'wps-manage-social-seo' => 'Social',
        'wps-rich-snippets-review' => 'Onsite Reviews',
        'wps-facebook-review' => 'Facebook Reviews',
        'wps-feeds' => 'Feeds',
        'wps-custom-text' => 'Custom Text'
    );
    echo admin_tabs($my_plugin_tabs);
    ?>    
    <script>
        jQuery(document).ready(function () {
            jQuery("body").addClass("wps-admin-page")
            // binds form submission and fields to the validation engine
            jQuery('#settingsID').ajaxForm({
                success: function (data) {
                    jQuery('.success').show();
                }
            });
            jQuery(".wps-postbox-container .handlediv, .wps-postbox-container .hndle").on("click", function (n) {
                return n.preventDefault(), jQuery(this).parent().toggleClass("closed");
            });
        });
    </script>    
    <div class="wrap">        
        <h2><?php _e('Social profile settings', 'wnp'); ?></h2> 
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="left-side">
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Social');
                ?>
                <!--                <div class="postbox" id="ppw_global_postbox">               -->
                <div class="inside">                               
                    <form id="settingsID" method="post" action="<?php echo get_admin_url() ?>admin-post.php">  
                        <fieldset>                            
                            <input type='hidden' name='action' value='submit-wnp-settings' />
                            <input type='hidden' name='id' value='<?php echo $getId ?>' />
                            <input type='hidden' name='paged' value='<?php echo $_GET['paged']; ?>' />
                            <div>
                                <div class="alert-box success" style="display:none;"><span>Success : </span>Social profile settings has been saved successfully</div>
                                <table cellpadding="0" cellspacing="0" border="0" width="600" class="form-table">                                    
                                    <tr height="50">
                                        <td>Facebook : </td>
                                        <td><input type="text" class="validate[required] text-input" id="facebook" name="facebook" value="<?php echo $get_option_details['facebook']; ?>" />                                            
                                        </td>
                                    </tr>
                                    <tr height="50">
                                        <td>Twitter : </td>
                                        <td><input type="text" id="twitter" name="twitter" value="<?php echo $get_option_details['twitter']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Google+ : </td>
                                        <td><input type="text" class="text-input" id="googleplus" name="googleplus" value="<?php echo $get_option_details['googleplus']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Instagram : </td>
                                        <td><input type="text" id="instagram" name="instagram" value="<?php echo $get_option_details['instagram']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>YouTube : </td>
                                        <td><input type="text" id="youtube" name="youtube" value="<?php echo $get_option_details['youtube']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>LinkedIn : </td>
                                        <td><input type="text" id="linkedin" name="linkedin" value="<?php echo $get_option_details['linkedin']; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Myspace : </td>
                                        <td><input type="text" id="myspace" name="myspace" value="<?php echo $get_option_details['myspace']; ?>" /></td>
                                    </tr>

                                </table>
                            </div>                         
                            <input class="button-primary" type="submit" value="Submit" name="submit" />    
                        </fieldset>
                    </form>
                </div>
                <!--                </div> -->
                <?php
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('About');
                render_rr_show_content();
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
            </div>
        </div>
        <?php displayRightSocialSeo(); ?>
    </div>

    <?php
}

function wpsmanageFacebookReview() {
    $pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
    wp_register_style('wp-social-css', $pluginDirectory . 'css/wp-social-seo.css');
    wp_enqueue_style('wp-social-css');
    $get_option_details = unserialize(get_option('wnp_facebook_reviews'));
    $otpr = '';
    $i = 1;
    foreach ($get_option_details['name'] as $get_all_names) {
        $otpr .= '<div style="padding: 10px; display: block;" class="clonedInput" id="entry' . $i . '">';
        $otpr .='<fieldset>';
        $otpr .='<label for="ID' . $i . '_reviewer-name" class="label_fn">Reviewer\'s Name: </label>';
        $otpr .='<input type="text" value="' . $get_all_names . '" id="ID' . $i . '_reviewer-name" name="reviewer-name[]" class="input_fn">';
        $otpr .='<label for="ID' . $i . '_post_id" class="label_ln">Post Id: </label>';
        $otpr .='<input type="text" value="' . $get_option_details['id'][$i - 1] . '" id="ID' . $i . '_post_id" name="post_id[]" class="input_ln">';
        $otpr .='</fieldset>';
        $otpr .='</div>';
        $i++;
    }
    $my_plugin_tabs = array(
        'wps-social-profile' => 'Your Company',
        'wps-manage-social-seo' => 'Social',
        'wps-rich-snippets-review' => 'Onsite Reviews',
        'wps-facebook-review' => 'Facebook Reviews',
        'wps-feeds' => 'Feeds',
        'wps-custom-text' => 'Custom Text'
    );
    echo admin_tabs($my_plugin_tabs);
    ?>
    <script>
        jQuery(document).ready(function () {
            jQuery("body").addClass("wps-admin-page")
            // binds form submission and fields to the validation engine
            jQuery('#facebookReview').ajaxForm({
                success: function (data) {
                    jQuery('.success').show();
                }
            });
            jQuery(".wps-postbox-container .handlediv, .wps-postbox-container .hndle").on("click", function (n) {
                return n.preventDefault(), jQuery(this).parent().toggleClass("closed");
            });
            jQuery('#btnAdd').click(function () {
                var num = jQuery('.clonedInput').length, // Checks to see how many "duplicatable" input fields we currently have
                        newNum = new Number(num + 1), // The numeric ID of the new input field being added, increasing by 1 each time
                        newElem = jQuery('#entry' + num).clone().attr('id', 'entry' + newNum).fadeIn('fast'); // create the new element via clone(), and manipulate it's ID using newNum value
                // First name - text
                newElem.find('.label_fn').attr('for', 'ID' + newNum + '_reviewer-name');
                newElem.find('.input_fn').attr('id', 'ID' + newNum + '_reviewer-name').attr('name', 'reviewer-name[]').val('');

                // Last name - text
                newElem.find('.label_ln').attr('for', 'ID' + newNum + '_post_id');
                newElem.find('.input_ln').attr('id', 'ID' + newNum + '_post_id').attr('name', 'post_id[]').val('');

                // Insert the new element after the last "duplicatable" input field
                jQuery('#entry' + num).after(newElem);
                jQuery('#ID' + newNum + '_reviewer-name').focus();

                // Enable the "remove" button. This only shows once you have a duplicated section.
                jQuery('#btnDel').attr('disabled', false);

                // Right now you can only add 4 sections, for a total of 5. Change '5' below to the max number of sections you want to allow.
                //if (newNum == 5)
                //jQuery('#btnAdd').attr('disabled', true).prop('value', "You've reached the limit"); // value here updates the text in the 'add' button when the limit is reached 
            });
            jQuery('#btnDel').click(function () {
                // Confirmation dialog box. Works on all desktop browsers and iPhone.
                //                if (confirm("Are you sure you wish to remove this section? This cannot be undone."))
                //                {
                var num = jQuery('.clonedInput').length;
                // how many "duplicatable" input fields we currently have
                jQuery('#entry' + num).slideUp('fast', function () {
                    jQuery(this).remove();
                    // if only one element remains, disable the "remove" button
                    if (num - 1 === 1)
                        jQuery('#btnDel').attr('disabled', true);
                    // enable the "add" button
                    jQuery('#btnAdd').attr('disabled', false).prop('value', "add section");
                });
                //}
                return false; // Removes the last section you added
            });
            // Enable the "add" button
            jQuery('#btnAdd').attr('disabled', false);
    <?php if (empty($get_option_details)) { ?>
                // Disable the "remove" button
                jQuery('#btnDel').attr('disabled', true);
    <?php } ?>
        });
    </script>
    <div class="wrap">        
        <h2><?php _e('Facebook Reviews', 'wnp'); ?></h2> 
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="left-side">
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Facebook Reviews');
                ?>
                <!--            <div class="postbox" id="ppw_global_postbox">               -->
                <div class="inside">                               
                    <form id="facebookReview" method="post" action="<?php echo get_admin_url() ?>admin-post.php">  
                        <fieldset>                            
                            <input type='hidden' name='action' value='submit-facebook-review' />
                            <input type='hidden' name='id' value='<?php echo $getId ?>' />
                            <input type='hidden' name='paged' value='<?php echo $_GET['paged']; ?>' />
                            <div>
                                <div class="alert-box success" style="display:none;"><span>Success : </span>Facebook review settings has been saved successfully</div>
                                <div style='float:left;'>
                                    <?php if (empty($get_option_details)) { ?>
                                        <div id="entry1" class="clonedInput" style="padding: 10px ;">                              
                                            <fieldset>
                                                <label class="label_fn" for="reviewer-name">Reviewer's Name: </label>
                                                <input class="input_fn" type="text" name="reviewer-name[]" id="reviewer-name" value="">

                                                <label class="label_ln" for="post_id">Post Id: </label>
                                                <input class="input_ln" type="text" name="post_id[]" id="post_id" value="">
                                            </fieldset>            
                                        </div><!-- end #entry1 -->  
                                        <?php
                                    } else {
                                        echo $otpr;
                                    }
                                    ?>
                                </div>
                                <div id="addDelButtons" style='float:left;padding: 10px ;'>
                                    <input class='button-primary' type="button" id="btnAdd" value="add section"> 
                                    <input class='button-primary' type="button" id="btnDel" value="remove section above">
                                </div>
                            </div> 
                            <div style='float:none;clear:both;'>
                                <input class="button-primary" type="submit" value="Submit" name="submit" />    
                            </div>                            
                        </fieldset>
                    </form>
                </div>
                <?php
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('About');
                render_rr_show_content();
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
                <!--            </div>           -->
            </div>
        </div>
        <?php displayRightFacebookReviews(); ?>
    </div>

    <?php
}

function wpsmanageAddRichSnippets() {
    session_start();
    $pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
    wp_register_style('wp-social-css', $pluginDirectory . 'css/wp-social-seo.css');
    wp_enqueue_style('wp-social-css');
    global $wpdb;
    if ($_REQUEST['action'] == 'edit' && $_REQUEST['review'] != '') {
        $getItemname = '';
        $getReviewername = '';
        $getDate = '';
        $getSummary = '';
        $getDescription = '';
        $getStatus = '';
        $getRating = '';
        $getDetails = $wpdb->get_row('SELECT * FROM  ' . $wpdb->prefix . 'rich_snippets_review WHERE id=' . $_REQUEST['review']);
        if ($getDetails != NULL) {
            $getId = $getDetails->id;
            $getItemname = $getDetails->item_name;
            $getReviewername = $getDetails->reviewer_name;
            $getDate = $getDetails->date_reviewed;
            $getSummary = $getDetails->summary;
            $getDescription = $getDetails->description;
            $getStatus = $getDetails->status;
            $getRating = $getDetails->rating;
            $getUrl = $getDetails->url;
        }
    }
    $my_plugin_tabs = array(
        'wps-social-profile' => 'Your Company',
        'wps-manage-social-seo' => 'Social',
        'wps-rich-snippets-review' => 'Onsite Reviews',
        'wps-facebook-review' => 'Facebook Reviews',
        'wps-feeds' => 'Feeds',
        'wps-custom-text' => 'Custom Text'
    );
    echo admin_tabs($my_plugin_tabs);
    ?>
    <script>
        jQuery(document).ready(function () {
            jQuery("body").addClass("wps-admin-page")
            // binds form submission and fields to the validation engine
            jQuery('#reviewID').ajaxForm({
                beforeSubmit: wpsValidate,
                success: function (data) {
                    jQuery('.success').show();
                }
            });
            jQuery(".wps-postbox-container .handlediv, .wps-postbox-container .hndle").on("click", function (n) {
                return n.preventDefault(), jQuery(this).parent().toggleClass("closed");
            });
        });
        function wpsValidate() {
            var itemValue = jQuery('input[name=item-name]').fieldValue();
            var reviewerValue = jQuery('input[name=reviewer-name]').fieldValue();
            var dateValue = jQuery('input[name=date-reviewed]').fieldValue();
            var summary = jQuery('input[name=summary]').fieldValue();
            var descriptionValue = jQuery('textarea[name=description]').fieldValue();
            var ratingValue = jQuery('select[name=rating]').fieldValue();
            // usernameValue and passwordValue are arrays but we can do simple
            // "not" tests to see if the arrays are empty
            if (!itemValue[0]) {
                alert('Please enter a title in review of field');
                return false;
            }
            if (!reviewerValue[0]) {
                alert('Please enter reviewer name');
                return false;
            }
            if (!dateValue[0]) {
                alert('Please enter date');
                return false;
            }
            //            if (!summary[0]) {
            //                alert('Please enter summary');
            //                return false;
            //            }
            if (!descriptionValue[0]) {
                alert('Please enter description');
                return false;
            }
            if (!ratingValue[0]) {
                alert('Please enter rating');
                return false;
            }
            return true;
        }
    </script>    
    <div class="wrap">        
        <h2><?php _e('Rich snippets review', 'wnp'); ?></h2> 
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="left-side">
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Rich snippets reviews');
                ?>
                <!--            <div class="postbox" id="ppw_global_postbox">               -->
                <div class="inside">                               
                    <form id="reviewID" method="post" action="<?php echo get_admin_url() ?>admin-post.php">  
                        <fieldset>                            
                            <input type='hidden' name='action' value='submit-rich-snippets-review' />
                            <input type='hidden' name='id' value='<?php echo $getId ?>' />
                            <input type='hidden' name='paged' value='<?php echo $_GET['paged']; ?>' />
                            <div>
                                <div class="alert-box success" style="display:none;"><span>Success : </span>Your review has been added</div>
                                <table cellpadding="0" cellspacing="0" border="0" width="600" class="form-table">                                    
                                    <tr height="50">
                                        <td>Review of : </td>
                                        <td><input type="text" class="validate[required] text-input" id="item-name" name="item-name" value="<?php echo $getItemname; ?>" />                                           
                                        </td>
                                    </tr>
                                    <tr height="50">
                                        <td>Reviewer name : </td>
                                        <td><input type="text" id="reviewer-name" name="reviewer-name" value="<?php echo $getReviewername; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Date reviewed : </td>
                                        <td><input type="text"  id="date-reviewed" name="date-reviewed" value="<?php echo $getDate; ?>" /></td>
                                    </tr>
    <!--                                    <tr height="50">
                                        <td>Summary : </td>
                                        <td><input type="text" id="summary" name="summary" value="<?php echo $getSummary; ?>" /></td>
                                    </tr>-->
                                    <tr height="50">
                                        <td>Description : </td>
                                        <td><textarea type="text" id="description" name="description"><?php echo $getDescription; ?></textarea></td>
                                    </tr>
                                    <tr height="50">
                                        <td>URL : </td>
                                        <td><input type="text" id="url" name="url" value="<?php echo $getUrl; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Rating : </td>
                                        <td>
    <!--                                            <input type="text" id="rating" name="rating" value="<?php echo $getRating; ?>" />-->
                                            <select id="rating" name="rating"> 
                                                <option value=''>Select rating</option>
                                                <option value='1'>1</option>
                                                <option value='2'>2</option>
                                                <option value='3'>3</option>
                                                <option value='4'>4</option>
                                                <option value='5'>5</option>
                                            </select>
                                        </td>

                                    </tr> 
                                </table>
                            </div>                         
                            <input class="button-primary" type="submit" value="Submit" name="submit" />    
                        </fieldset>
                    </form>
                </div>
                <!--            </div>           -->
                <?php
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('About');
                render_rr_show_content();
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
            </div>
        </div>
        <?php displayRightRichSnippets(); ?>
    </div>
    <?php
}

function wpsmanageDeleteRichSnippets() {
    session_start();
    global $wpdb;
    $wpdb->delete($wpdb->prefix . "rich_snippets_review", array('id' => $_GET['review']));
    if ($wpdb->rows_affected > 0) {
        $_SESSION['area_status'] = 'deletesuccess';
    } else {
        $_SESSION['area_status'] = 'deletefailed';
    }
    if ($_GET['paged'] != '') {
        wp_redirect(admin_url('admin.php?page=wps-rich-snippets-review&paged="' . $_GET['paged'] . '"'));
        exit;
    }
    wp_redirect(admin_url('admin.php?page=wps-rich-snippets-review'));
}

function wpsmanageRichSnippets() {
    $pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
    wp_register_style('wp-social-css', $pluginDirectory . 'css/wp-social-seo.css');
    wp_enqueue_style('wp-social-css');
    session_start();
    $my_plugin_tabs = array(
        'wps-social-profile' => 'Your Company',
        'wps-manage-social-seo' => 'Social',
        'wps-rich-snippets-review' => 'Onsite Reviews',
        'wps-facebook-review' => 'Facebook Reviews',
        'wps-feeds' => 'Feeds',
        'wps-custom-text' => 'Custom Text'
    );
    echo admin_tabs($my_plugin_tabs);
    ?>   
    <script>
        jQuery(document).ready(function () {
            jQuery("body").addClass("wps-admin-page")
            jQuery(".wps-postbox-container .handlediv, .wps-postbox-container .hndle").on("click", function (n) {
                return n.preventDefault(), jQuery(this).parent().toggleClass("closed");
            });
        });
    </script>  
    <div class="wrap">                
    <!--<div class="alert-box warning"><span>warning: </span>Write your warning message here.</div>
    <div class="alert-box notice"><span>notice: </span>Write your notice message here.</div>-->
        <h2><?php _e('Rich snippets reviews', 'cqp'); ?> <a class="add-new-h2" href="<?php echo admin_url() ?>admin.php?page=wps-add-rich-snippets-review">Add New</a></h2>         
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="left-side">
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Rich snippets reviews');
                ?>
                <!--            <div class="postbox" id="ppw_global_postbox">                           -->
                <div class="inside">
                    <form id="review" name="review" method="post" action="">
                        <?php
                        if ($_REQUEST['action'] == 'delete') {
                            $del = $_REQUEST['review'];
                            if ($del != '') {
                                $idsToDelete = implode($del, ',');
                                global $wpdb;
                                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "rich_snippets_review WHERE id IN ($idsToDelete)"));
                                if ($wpdb->rows_affected > 0) {
                                    $_SESSION['area_status'] = 'deletesuccess';
                                    wp_redirect(admin_url('admin.php?page=wps-rich-snippets-review&paged="' . $_GET['paged'] . '"'));
                                    exit;
                                }
                            } else {
                                $_SESSION['area_status'] = 'deletefailed';
                                if ($_GET['paged'] != '') {
                                    wp_redirect(admin_url('admin.php?page=wps-rich-snippets-review&paged="' . $_GET['paged'] . '"'));
                                } else {
                                    wp_redirect(admin_url('admin.php?page=wps-rich-snippets-review'));
                                }
                            }
                        }
                        $myListTable = new Wps_Review_List_Table();
                        $myListTable->prepare_items();
                        $myListTable->display();
                        ?>
                    </form>
                </div>
                <!--            </div>           -->
                <?php
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('About');
                render_rr_show_content();
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
            </div>           
        </div>
        <?php displayRightRichSnippets(); ?>
    </div>
    <?php
}

function wpsSaveRichSnippets() {
    session_start();
    global $wpdb;
    if (isset($_POST['submit'])) {
        $insertArray = array();
        $insertArray['item_name'] = $_POST['item-name'];
        $insertArray['reviewer_name'] = $_POST['reviewer-name'];
        $insertArray['date_reviewed'] = $_POST['date-reviewed'];
        $insertArray['summary'] = $_POST['summary'];
        $insertArray['description'] = $_POST['description'];
        $insertArray['rating'] = $_POST['rating'];
        $insertArray['url'] = $_POST['url'];
        if ($_POST['id'] != '') {
            $wpdb->update($wpdb->prefix . "rich_snippets_review", $insertArray, array('id' => $_POST['id']), array('%s', '%s'), array('%d'));
            //if ($wpdb->insert_id > 0) {
            $_SESSION['area_status'] = 'updated';
//            } else {
//                $_SESSION['area_status'] = 'failed';
//            }
        } else {
            $wpdb->insert($wpdb->prefix . "rich_snippets_review", $insertArray, array('%s', '%s'));
            //echo $wpdb->last_query;exit;
            if ($wpdb->insert_id > 0) {
                $_SESSION['area_status'] = 'success';
            } else {
                $_SESSION['area_status'] = 'failed';
            }
        }
    }
}

function wpsFacebookReview() {
    session_start();
    global $wpdb;
    if (isset($_POST['submit'])) {
        $insertArray = array();
        $insertArray['name'] = $_POST['reviewer-name'];
        $insertArray['id'] = $_POST['post_id'];
        $serialize_array = serialize($insertArray);
        update_option('wnp_facebook_reviews', $serialize_array);
        $_SESSION['area_status'] = 'updated';
    }
}

function admin_tabs($tabs, $current = NULL) {
    if (is_null($current)) {
        if (isset($_GET['page'])) {
            $current = $_GET['page'];
        }
    }
    $content = '';
    $content .= '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $location => $tabname) {
        if ($current == $location) {
            $class = ' nav-tab-active';
        } else {
            $class = '';
        }
        $content .= '<a class="nav-tab' . $class . '" href="?page=' . $location . '">' . $tabname . '</a>';
    }
    $content .= '</h2>';
    return $content;
}

function wpsSaveSettings() {
    session_start();
    global $wpdb;
    if (isset($_POST['submit'])) {
        $insertArray = array();
        if ($_POST['facebook'] != '')
            $insertArray['facebook'] = sanitize_text_field($_POST['facebook']);
        if ($_POST['twitter'] != '')
            $insertArray['twitter'] = sanitize_text_field($_POST['twitter']);
        if ($_POST['googleplus'] != '')
            $insertArray['googleplus'] = sanitize_text_field($_POST['googleplus']);
        if ($_POST['instagram'] != '')
            $insertArray['instagram'] = sanitize_text_field($_POST['instagram']);
        if ($_POST['youtube'] != '')
            $insertArray['youtube'] = sanitize_text_field($_POST['youtube']);
        if ($_POST['linkedin'] != '')
            $insertArray['linkedin'] = sanitize_text_field($_POST['linkedin']);
        if ($_POST['myspace'] != '')
            $insertArray['myspace'] = sanitize_text_field($_POST['myspace']);
        if (!empty($insertArray)) {
            $serialize_array = serialize($insertArray);
            update_option('wnp_social_settings', $serialize_array);
            $_SESSION['area_status'] = 'updated';
        }
        // wp_redirect(admin_url('admin.php?page=web-nine-plc'));
    }
}

function wpsSaveCompany() {
    session_start();
    global $wpdb;
    if (isset($_POST['submit'])) {
        $insertArray = array();
        if ($_POST['type'] != '')
            $insertArray['type'] = sanitize_text_field($_POST['type']);
        if ($_POST['name'] != '')
            $insertArray['name'] = sanitize_text_field($_POST['name']);
        if ($_POST['url'] != '')
            $insertArray['url'] = esc_url($_POST['url']);
        if ($_POST['logo-url'] != '')
            $insertArray['logo-url'] = esc_url($_POST['logo-url']);
        if ($_POST['telephone'] != '')
            $insertArray['telephone'] = sanitize_text_field($_POST['telephone']);
//        if ($_POST['other_telephone'] != '')
//            $insertArray['other_telephone'] = sanitize_text_field($_POST['other_telephone']);
        if ($_POST['contact_type'] != '')
            $insertArray['contact_type'] = sanitize_text_field($_POST['contact_type']);
        if ($_POST['area_served'] != '')
            $insertArray['area_served'] = sanitize_text_field($_POST['area_served']);
//        if ($_POST['contact_option'] != '' && !empty($_POST['contact_option']))
//            $insertArray['contact_option'] = sanitize_text_field(implode(',', $_POST['contact_option']));
        if ($_POST['avail_language'] != '')
            $insertArray['avail_language'] = sanitize_text_field($_POST['avail_language']);
        if (!empty($insertArray)) {
            $serialize_array = serialize($insertArray);
            update_option('wnp_your_company', $serialize_array);
            $_SESSION['area_status'] = 'updated';
        }
        // wp_redirect(admin_url('admin.php?page=web-nine-plc'));
    }
}

add_action('wp_footer', 'wps_buffer_end');

function wps_buffer_end() {
    $get_option_details = unserialize(get_option('wnp_social_settings'));
    $get_company_option_details = unserialize(get_option('wnp_your_company'));
    $display_social = '';
    if (isset($get_option_details['facebook']))
        $display_social .= '"' . $get_option_details['facebook'] . '",';
    if (isset($get_option_details['twitter']))
        $display_social .= '"' . $get_option_details['twitter'] . '",';
    if (isset($get_option_details['googleplus']))
        $display_social .= '"' . $get_option_details['googleplus'] . '",';
    if (isset($get_option_details['instagram']))
        $display_social .= '"' . $get_option_details['instagram'] . '",';
    if (isset($get_option_details['youtube']))
        $display_social .= '"' . $get_option_details['youtube'] . '",';
    if (isset($get_option_details['linkedin']))
        $display_social .= '"' . $get_option_details['linkedin'] . '",';
    if (isset($get_option_details['myspace']))
        $display_social .= '"' . $get_option_details['myspace'] . '",';
    $display_social = rtrim($display_social, ",");

    $displayOut = '';
    if (isset($get_company_option_details['telephone'])) {
        $expl_telephone = explode(',', $get_company_option_details['telephone']);
        if (count($expl_telephone) == 1) {
            $displayOut .='"telephone" : "' . $get_company_option_details['telephone'] . '",';
        } else {
            $parts = split(',', $get_company_option_details['telephone']);
            $displayOut .='"telephone" : ["' . join('", "', $parts) . '"],';
        }
    }
    if (isset($get_company_option_details['contact_type']))
        $displayOut .='"contactType" : "' . $get_company_option_details['contact_type'] . '",';
    if (isset($get_company_option_details['contact_option']) && !empty($get_company_option_details['contact_option'])) {
        $expl_contact_option = explode(',', $get_company_option_details['contact_option']);
        if (count($expl_contact_option) == 1) {
            $displayOut .='"contactOption" : "' . $get_company_option_details['contact_option'] . '",';
        } else {
            $parts = split(',', $get_company_option_details['contact_option']);
            $displayOut .='"contactOption" : ["' . join('", "', $parts) . '"],';
        }
    }
    if (isset($get_company_option_details['area_served']) && !empty($get_company_option_details['area_served'])) {
        $expl_area_served = explode(',', $get_company_option_details['area_served']);
        if (count($expl_area_served) == 1) {
            $displayOut .='"areaServed" : "' . $get_company_option_details['area_served'] . '",';
        } else {
            $parts = split(',', $get_company_option_details['area_served']);
            $displayOut .='"areaServed" : ["' . join('", "', $parts) . '"],';
        }
    }
    if (isset($get_company_option_details['avail_language'])) {
        $expl_avail_language = explode(',', $get_company_option_details['avail_language']);
        if (count($expl_avail_language) == 1) {
            $displayOut .='"availableLanguage" : "' . $get_company_option_details['avail_language'] . '"';
        } else {
            $parts = split(',', $get_company_option_details['avail_language']);
            $displayOut .='"availableLanguage" : ["' . join('", "', $parts) . '"]';
        }
    }
    $displayOut = rtrim($displayOut, ",");
    echo '<script type="application/ld+json">
{ "@context" : "http://schema.org",
  "@type" : "' . $get_company_option_details['type'] . '",
  "name" : "' . $get_company_option_details['name'] . '",
  "url" : "' . $get_company_option_details['url'] . '",
  "logo": "' . $get_company_option_details['logo-url'] . '",
  "sameAs" : [' . $display_social . '],
      "contactPoint" : [
    { "@type" : "ContactPoint",
      ' . $displayOut . '
    } ]
}
</script>
';
}

add_filter('widget_text', 'do_shortcode');

function bartag_func($atts) {
    wp_enqueue_style('carouselcss', plugins_url('css/jquery.bxslider.css', __FILE__));
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery_carousel', plugins_url('js/jquery.bxslider.js', __FILE__));
    $get_option_details = unserialize(get_option('wnp_facebook_reviews'));
    $names = $get_option_details['name'];
    $i = 1;
    $render = '';
    $render .= '<div id="fb-root"></div><script>(function(d, s, id) {  var js, fjs = d.getElementsByTagName(s)[0];  if (d.getElementById(id)) return;  js = d.createElement(s); js.id = id;  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";  fjs.parentNode.insertBefore(js, fjs);}(document, \'script\', \'facebook-jssdk\'));</script>';
    $render .='<script>jQuery(document).ready(function () {
        jQuery(\'.bxslider-fb\').bxSlider({
        pager :false,
        auto:true,
        mode:\'fade\',
        speed: 3000,
        pause:10000,
        controls:false,
        wrapperClass: \'bx-wrapper-new\',
        autoHover:true,
        adaptiveHeight:true
        });       
        });</script>       
                    <ul class="bxslider-fb">';
    foreach ($names as $name) {
        $render .= '<li><div style = "float:left;" class = "fb-post" data-href = "https://www.facebook.com/' . $name . '/posts/' . $get_option_details['id'][$i - 1] . '" data-width = "100%" data-height = "400px">
        <div class = "fb-xfbml-parse-ignore">Post by ' . str_replace('.', ' ', $name) . '.</div>
        </div></li>';
        $i++;
    }
    $render .=' </ul>';
    return $render;
}

function display_rich_snippets() {
    session_start();
    global $wpdb;
    $picker1 = '#CCCCCC';
    $picker2 = '#FFF000';
    $picker3 = '#FFFFFF';
    $picker4 = '#000000';
    $get_option_details = unserialize(get_option('social_seo_options_picker'));
    if (!empty($get_option_details)) {
        if (isset($get_option_details['picker1']) && $get_option_details['picker1'] != '')
            $picker1 = $get_option_details['picker1'];
        if (isset($get_option_details['picker2']) && $get_option_details['picker2'] != '')
            $picker2 = $get_option_details['picker2'];
        if (isset($get_option_details['picker3']) && $get_option_details['picker3'] != '')
            $picker3 = $get_option_details['picker3'];
        if (isset($get_option_details['picker4']) && $get_option_details['picker4'] != '')
            $picker4 = $get_option_details['picker4'];
    } else {
        $picker1 = '#CCCCCC';
        $picker2 = '#FFF000';
        $picker3 = '#FFFFFF';
        $picker4 = '#000000';
    }
    ?>
    <style>       
        .gnrl-class{
            padding: 0px 0px 10px 0px;
            display:block;
            line-height: 20px;
        }
        .gnrl-new-class{
            display:block;
            line-height: 20px;
            float:right;
        }
        .gnrl-new-class a{
            color: <?php echo $picker4; ?>;
        }
        .top-class{
            background: none repeat scroll 0 0 <?php echo $picker2; ?>;
            border-radius: 5px;
            color: #000 !important;
            margin-bottom: 5px;
            /*            margin-top: 30px;*/
            padding: 10px;
            height: 100px;
        }
        .bottom-class {
            background: none repeat scroll 0 0 <?php echo $picker3; ?>;
            border-radius: 5px;
            color: #000;
            display: inline-block;
            float: right;
            font-style: italic;
            font-weight: normal;
            padding: 5px 10px;
            text-align: right;
        }
        .testimonial{
            background: none repeat scroll 0 0 <?php echo $picker1; ?>;
            display:inline-block;
            border-radius:5px;
            padding: 10px;
            width: 100%;
        }
    </style>
    <script>
        var ratingUrl = "<?php echo plugins_url(); ?>/wp-social-seo/";
    </script>
    <?php
    wp_enqueue_style('carouselcss', plugins_url('css/jquery.bxslider.css', __FILE__));
    wp_enqueue_style('ratingcss', plugins_url('js/jRating.jquery.css', __FILE__));
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery_carousel', plugins_url('js/jquery.bxslider.js', __FILE__));
    wp_enqueue_script('jquery_rating', plugins_url('js/jRating.jquery.js', __FILE__));
    $Lists = $wpdb->get_results('SELECT * FROM  ' . $wpdb->prefix . 'rich_snippets_review');
    if (!empty($Lists)) {
        //echo $wpdb->last_query;
        $i = 0;
        $newi=1;
        $display = '';
        //$display .= '<div id="fb-root"></div><script>(function(d, s, id) {  var js, fjs = d.getElementsByTagName(s)[0];  if (d.getElementById(id)) return;  js = d.createElement(s); js.id = id;  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";  fjs.parentNode.insertBefore(js, fjs);}(document, \'script\', \'facebook-jssdk\'));</script>';
        $display .='<script>jQuery(document).ready(function () {           
        jQuery(\'.bxslider-reviews\').bxSlider({
        pager :false,
        auto:true,
        mode:\'fade\',
        speed: 1000,
        pause:4000,
        controls:false,
        autoHover:true
        }); 
        jQuery(\'.basic\').jRating({
	  isDisabled : true
	});
        });</script>       
                    <ul class="bxslider-reviews">';
        foreach ($Lists as $List) {
            $display .='
            <li>
            <div class = "hms-testimonial-container-new" itemscope itemtype="http://schema.org/Review">
            <div class = "testimonial">
            <div class = "top-class">
            <div class = "gnrl-class" itemprop="itemReviewed" itemscope itemtype="http://schema.org/Thing"><span itemprop="name">' . stripcslashes($List->item_name) . '</span></div>
            <div class = "gnrl-class" itemprop = "description">' . preg_replace('/\\\\/', '', substr($List->description, 0, 100)) . '</div>
            </div>
            <div class = "bottom-class">
            <div class = "gnrl-new-class" itemprop="author" itemscope="" itemtype="http://schema.org/Person">Reviewed by <i><a href = "' . $List->url . '" target = "_blank"><span itemprop="name">' . stripcslashes($List->reviewer_name) . '</span></a></i> on <i>' . $List->date_reviewed . '</i></div>
            <div class = "gnrl-new-class" itemprop="reviewRating" itemscope="" itemtype="http://schema.org/Rating"><span itemprop="ratingValue" style="display:none;">' . $List->rating . '</span><div class = "basic" data-average = "' . $List->rating . '" data-id = "pn-display-rich-snippets-'.$newi.'"></div></div>
            </div>
            </div>
            </div>
            </li>';
            $newi++;
        }
        $display .=' </ul > ';
        return $display;
    } else {
        return '';
    }
    ?>
    <?php
}

function displayRight() {
    ?>
    <div class="right-side">
        <?php
        NMRichReviewsAdminHelper::render_container_open('content-container-right');
        NMRichReviewsAdminHelper::render_postbox_open('Information');
        render_rr_information();
        NMRichReviewsAdminHelper::render_postbox_close();
        NMRichReviewsAdminHelper::render_container_close();
        NMRichReviewsAdminHelper::render_container_open('content-container-right');
        NMRichReviewsAdminHelper::render_postbox_open('What we Do');
        render_rr_what_we_do();
        NMRichReviewsAdminHelper::render_postbox_close();
        NMRichReviewsAdminHelper::render_container_close();
        ?>
    </div>
    <?php
}

function displayRightSocialSeo() {
    ?>
    <div class="right-side">
        <?php
        NMRichReviewsAdminHelper::render_container_open('content-container-right');
        NMRichReviewsAdminHelper::render_postbox_open('Information');
        render_rr_information_social_seo();
        NMRichReviewsAdminHelper::render_postbox_close();
        NMRichReviewsAdminHelper::render_container_close();
        NMRichReviewsAdminHelper::render_container_open('content-container-right');
        NMRichReviewsAdminHelper::render_postbox_open('What we Do');
        render_rr_what_we_do();
        NMRichReviewsAdminHelper::render_postbox_close();
        NMRichReviewsAdminHelper::render_container_close();
        ?>
    </div>
    <?php
}

function displayRightFacebookReviews() {
    ?>
    <div class="right-side">
        <?php
        NMRichReviewsAdminHelper::render_container_open('content-container-right');
        NMRichReviewsAdminHelper::render_postbox_open('ShortCodes');
        render_rr_information_facebook_reviews();
        NMRichReviewsAdminHelper::render_postbox_close();
        NMRichReviewsAdminHelper::render_container_close();
        NMRichReviewsAdminHelper::render_container_open('content-container-right');
        NMRichReviewsAdminHelper::render_postbox_open('What we Do');
        render_rr_what_we_do();
        NMRichReviewsAdminHelper::render_postbox_close();
        NMRichReviewsAdminHelper::render_container_close();
        ?>
    </div>
    <?php
}

function displayRightRichSnippets() {
    ?>
    <div class="right-side">
        <?php
        NMRichReviewsAdminHelper::render_container_open('content-container-right');
        NMRichReviewsAdminHelper::render_postbox_open('ShortCodes');
        render_rr_information_rich_snippets();
        NMRichReviewsAdminHelper::render_postbox_close();
        NMRichReviewsAdminHelper::render_container_close();

        NMRichReviewsAdminHelper::render_container_open('content-container-right');
        NMRichReviewsAdminHelper::render_postbox_open('Color picker settings');
        render_rr_color_picker_settings();
        NMRichReviewsAdminHelper::render_postbox_close();
        NMRichReviewsAdminHelper::render_container_close();

        NMRichReviewsAdminHelper::render_container_open('content-container-right');
        NMRichReviewsAdminHelper::render_postbox_open('What we Do');
        render_rr_what_we_do();
        NMRichReviewsAdminHelper::render_postbox_close();
        NMRichReviewsAdminHelper::render_container_close();
        ?>
    </div>
    <?php
}

function render_rr_information() {
    $output = '<span style="background: none repeat scroll 0 0 #99ff99;display:block;padding: 10px;">Test your Data using <a target="_blank" href="https://developers.google.com/webmasters/structured-data/testing-tool/">Google\'s Structured Data Testing Tool </a></span></br>';
    $output .= '<span class="info_class">Countries may be specified concisely using just their standard ISO-3166 two-letter code, for example US, CA, MX</span></br></br>';
    $output .='<span class="info_class">Optional details about the language spoken. Languages may be specified by their common English name. If omitted, the language defaults to English, for example French, English</span>';
    echo $output;
}

function render_rr_what_we_do() {
    $output = '<a href="http://www.web9.co.uk/our-plugins/" target="_blank"><img src="http://www.web9.co.uk/wp-content/uploads/2014/12/web9.png" width="401px" height="80px" /></a>';
    $output .='<span style="background: none repeat scroll 0 0 #FFA500;display:block;padding: 10px; color:#fff;">Want to see how else we can help your business, we have a range of <a href="http://www.web9.co.uk/our-plugins/" target="_blank">free plugins</a> in the WordPress repository as we believe that by giving back to the community we help to create a better product for everyone to use.</span>';
    echo $output;
}

function render_rr_information_social_seo() {
    $output = '<span style="background: none repeat scroll 0 0 #99ff99;display:block;padding: 10px;">Test your Data using <a target="_blank" href="https://developers.google.com/webmasters/structured-data/testing-tool/">Google\'s Structured Data Testing Tool </a></span></br>';
    $output .= '<span class="info_class">Please add the links to your Social Media pages, which will then get added to your sites SERP to be displayed in Google Searches.</span></br></br>';
    $output .='<span class="info_class">See this link  to view <a href="https://developers.google.com/structured-data/customize/social-profiles" target="_blank">Googles Description</a></span>';
    echo $output;
}

function render_rr_information_facebook_reviews() {
    $output = '<span style="background: none repeat scroll 0 0 #99ff99;display:block;padding: 10px;">In a Widget, please use the following shortcode <strong>[facebook-review-slider]</strong> to display your FB reviews on your site.</span></br>';
    $output .= '<span class="info_class">If you wish to display facebook reviews on your Website, please open your facebook page reviews section and click on the actual date in the review tab, you will then have a URL that looks like this</span></br></br>';
    $output .='<span class="info_class">https://www.facebook.com/username/activity/2785136523501 (the numbers are the post ID), copy and paste the reviews FB name and Post ID.  Repeat this for as many as you wish.</span>';
    echo $output;
}

function render_rr_information_rich_snippets() {
    $output = '<span style="background: none repeat scroll 0 0 #99ff99;display:block;padding: 10px;">In a Widget, please use the following shortcode <strong>[wps-rich-snippets]</strong> to display your reviews on your site.</span></br>';
    $output .= '<span class="info_class"><a href="https://developers.google.com/structured-data/rich-snippets/" target="_blank">Google’s Rich Snippets</a> allow your visitors to add reviews to your website that will show up in the SERPs.  For more info, visit the Google page.</span></br></br>';
    echo $output;
}

function render_rr_color_picker_settings() {
    session_start();
    global $wpdb;
    $picker1 = '#CCCCCC';
    $picker2 = '#FFF000';
    $picker3 = '#FFFFFF';
    $picker4 = '#000000';
    $call_back_admin_email = '';

    $get_option_details = unserialize(get_option('social_seo_options_picker'));
    if (!empty($get_option_details)) {
        if (isset($get_option_details['picker1']) && $get_option_details['picker1'] != '')
            $picker1 = $get_option_details['picker1'];
        if (isset($get_option_details['picker2']) && $get_option_details['picker2'] != '')
            $picker2 = $get_option_details['picker2'];
        if (isset($get_option_details['picker3']) && $get_option_details['picker3'] != '')
            $picker3 = $get_option_details['picker3'];
        if (isset($get_option_details['picker4']) && $get_option_details['picker4'] != '')
            $picker4 = $get_option_details['picker4'];
    } else {
        $picker1 = '#CCCCCC';
        $picker2 = '#FFF000';
        $picker3 = '#FFFFFF';
        $picker4 = '#000000';
    }
    _socialStatusMessage('Color picker settings');
    if ($dropdown == 1) {
        $checked = 'checked="checked"';
    } else {
        $checked = '';
    }
    $output = '   <div class="info_class"> 
                    <form id="color_picker_form" name="color_picker_form" method="post" action="' . get_admin_url() . 'admin-post.php" onsubmit="return validate();">  
                        <fieldset>
                            <input type=\'hidden\' name=\'action\' value=\'submit-color-picker\' />
                            <table width="600px" cellpadding="0" cellspacing="0" class="form-table">
                                <tr>
                                    <td>Total background color : </td>
                                    <td><input readonly type="text" id="picker1" name="picker1" style="border-color:' . $picker1 . '" value="' . $picker1 . '"></input></td>
                                </tr>
                                <tr>
                                    <td>Top background color : </td>
                                    <td><input readonly type="text" id="picker2" name="picker2" style="border-color:' . $picker2 . '" value="' . $picker2 . '"></input></td>
                                </tr>
                                <tr>
                                    <td>Bottom background color : </td>
                                    <td><input readonly type="text" id="picker3" name="picker3" style="border-color:' . $picker3 . '" value="' . $picker3 . '"></input></td>
                                </tr>
                                <tr>
                                    <td>Bottom background color : </td>
                                    <td><input readonly type="text" id="picker4" name="picker4" style="border-color:' . $picker4 . '" value="' . $picker4 . '"></input></td>
                                </tr>
                                <tr>                                
                                    <td colspan="2"><input class="button-primary" type="submit" id="submit_form_settings" name="submit_form_settings"></input></td>
                                </tr>
                            </table>
                        </fieldset>
                    </form>   </div>             
    <script>
        function validate() {
            var picker1 = jQuery(\'#picker1\').val();
            var picker2 = jQuery(\'#picker2\').val();
            var picker3 = jQuery(\'#picker3\').val();  
            var picker4 = jQuery(\'#picker4\').val();  
            var call_back_admin_email = jQuery(\'#call_back_admin_email\').val();
            if (picker1 == \'\' || picker2 == \'\' || picker3 == \'\' || picker4 == \'\') {
                alert(\'Please fill all the required fields\');
                return false;
            }
            return true;
        }
        jQuery(document).ready(function () {
            jQuery(\'#picker1,#picker2,#picker3,#picker4\').colpick({
                layout: \'hex\',
                submit: 0,
                color: \'3289c7\',
                colorScheme: \'dark\',
                onChange: function (hsb, hex, rgb, el, bySetColor) {
                    jQuery(el).css(\'border-color\', \'#\' + hex);
                    // Fill the text box just if the color was set using the picker, and not the colpickSetColor function.
                    if (!bySetColor)
                        jQuery(el).val(\'#\' + hex);
                }
            }).keyup(function () {
                jQuery(this).colpickSetColor(this.value);
            });
        });
    </script>';
    echo $output;
}

function wpsFeeds() {

    $pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
    wp_register_style('wp-social-css', $pluginDirectory . 'css/wp-social-seo.css');
    wp_enqueue_style('wp-social-css');
    $get_option_details = unserialize(get_option('wnp_your_company'));
    $my_plugin_tabs = array(
        'wps-social-profile' => 'Your Company',
        'wps-manage-social-seo' => 'Social',
        'wps-rich-snippets-review' => 'Onsite Reviews',
        'wps-facebook-review' => 'Facebook Reviews',
        'wps-feeds' => 'Feeds',
        'wps-custom-text' => 'Custom Text'
    );
    echo admin_tabs($my_plugin_tabs);
    ?>

    <script>
        jQuery(document).ready(function () {
            jQuery("body").addClass("wps-admin-page")
            // binds form submission and fields to the validation engine
            jQuery('#companyID').ajaxForm({
                beforeSubmit: wpsValidate,
                success: function (data) {
                    jQuery('.success').show();
                }
            });
            jQuery(".wps-postbox-container .handlediv, .wps-postbox-container .hndle").on("click", function (n) {
                return n.preventDefault(), jQuery(this).parent().toggleClass("closed");
            });
        });
    </script>        
    <div class="wrap">                    
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="left-side">
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Feeds');
                ?>
                <!--            <div class="postbox" id="ppw_global_postbox">               -->
                Coming soon <br/>
                Embed Facebook & Twitter Social Feeds in your page or sidebar as a widget
                <div class="inside">                                                   
                </div>               
                <!--            </div>   -->
                <?php
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('About');
                render_rr_show_content();
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
            </div>            
        </div>    
        <?php displayRight(); ?>
    </div>
    <?php
}

function wpsCustomText() {

    $pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
    wp_register_style('wp-social-css', $pluginDirectory . 'css/wp-social-seo.css');
    wp_enqueue_style('wp-social-css');
    $get_option_details = unserialize(get_option('wnp_your_company'));
    $my_plugin_tabs = array(
        'wps-social-profile' => 'Your Company',
        'wps-manage-social-seo' => 'Social',
        'wps-rich-snippets-review' => 'Onsite Reviews',
        'wps-facebook-review' => 'Facebook Reviews',
        'wps-feeds' => 'Feeds',
        'wps-custom-text' => 'Custom Text'
    );
    echo admin_tabs($my_plugin_tabs);
    ?>

    <script>
        jQuery(document).ready(function () {
            jQuery("body").addClass("wps-admin-page")
            // binds form submission and fields to the validation engine
            jQuery('#companyID').ajaxForm({
                beforeSubmit: wpsValidate,
                success: function (data) {
                    jQuery('.success').show();
                }
            });
            jQuery(".wps-postbox-container .handlediv, .wps-postbox-container .hndle").on("click", function (n) {
                return n.preventDefault(), jQuery(this).parent().toggleClass("closed");
            });
        });
    </script>        
    <div class="wrap">                    
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="left-side">
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Custom Text');
                ?>
                <!--            <div class="postbox" id="ppw_global_postbox">               -->
                Ever wanted to embed some custom text messages or images in your sidebar ? and have it rotate among your testimonials or reviews ?  Now you can.
                <div class="inside">                                                   
                </div>               
                <!--            </div>   -->
                <?php
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('About');
                render_rr_show_content();
                NMRichReviewsAdminHelper::render_postbox_close();
                NMRichReviewsAdminHelper::render_container_close();
                ?>
            </div>            
        </div>    
        <?php displayRight(); ?>
    </div>
    <?php
}

function _socialStatusMessage($string) {
    if ($_SESSION['area_status'] == 'success') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box success"><span>Success : </span>New <?php echo $string; ?> has been added successfully</div>
        <?php
    } else if ($_SESSION['area_status'] == 'failed') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box errormes"><span>Error : </span>Problem in creating new <?php echo $string; ?>.</div>
        <?php
    } else if ($_SESSION['area_status'] == 'updated') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box success"><span>Success : </span><?php echo $string; ?> has been updated successfully.</div>
        <?php
    } else if ($_SESSION['area_status'] == 'deletesuccess') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box success"><span>Success : </span><?php echo $string; ?> has been deleted successfully.</div>
        <?php
    } else if ($_SESSION['area_status'] == 'deletefailed') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box errormes"><span>Error : </span>Problem in deleting <?php echo $string; ?>.</div>
        <?php
    } else if ($_SESSION['area_status'] == 'invalid_file') {
        unset($_SESSION['area_status']);
        ?>
        <div class="alert-box errormes"><span>Error : </span><?php echo $string; ?> should be a PHP file.</div>
        <?php
    }
}

function saveSocialSeoColorPicker() {
    session_start();
    global $wpdb;
    if (isset($_POST['submit_form_settings'])) {
        if (isset($_POST['picker1']))
            $insertArray['picker1'] = $_POST['picker1'];
        if (isset($_POST['picker2']))
            $insertArray['picker2'] = $_POST['picker2'];
        if (isset($_POST['picker3']))
            $insertArray['picker3'] = $_POST['picker3'];
        if (isset($_POST['picker4']))
            $insertArray['picker4'] = $_POST['picker4'];

        $serialize_array = serialize($insertArray);
        update_option('social_seo_options_picker', $serialize_array);
        $_SESSION['area_status'] = 'updated';
        wp_redirect(admin_url('admin.php?page=wps-rich-snippets-review'));
    }
    wp_redirect(admin_url('admin.php?page=wps-rich-snippets-review'));
}

function display_all_rich_snippets() {
    session_start();
    global $wpdb;
    $get_option_details = unserialize(get_option('social_seo_options_picker'));
    if (!empty($get_option_details)) {
        if (isset($get_option_details['picker1']) && $get_option_details['picker1'] != '')
            $picker1 = $get_option_details['picker1'];
        if (isset($get_option_details['picker2']) && $get_option_details['picker2'] != '')
            $picker2 = $get_option_details['picker2'];
        if (isset($get_option_details['picker3']) && $get_option_details['picker3'] != '')
            $picker3 = $get_option_details['picker3'];
    } else {
        $picker1 = '#CCCCCC';
        $picker2 = '#FFF000';
        $picker3 = '#FFFFFF';
    }
    wp_enqueue_style('ratingcss', plugins_url('js/jRating.jquery.css', __FILE__));
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery_rating', plugins_url('js/jRating.jquery.js', __FILE__));
    ?>
    <style>       
        .gnrl-class-all{
            padding: 0px 0px 10px 0px;
            display:block;
            line-height: 20px;
        }
        .gnrl-new-class-all{
            display:block;
            line-height: 20px;
            float:right;
        }
        .top-class-all{
            background: none repeat scroll 0 0 <?php echo $picker2; ?>;
            border-radius: 5px;
            color: #000 !important;
            margin-bottom: 5px;
            /*            margin-top: 30px;*/
            padding: 10px;          
        }
        .bottom-class-all {
            background: none repeat scroll 0 0 <?php echo $picker3; ?>;
            border-radius: 5px;
            color: #000;
            display: inline-block;
            float: right;
            font-style: italic;
            font-weight: normal;
            padding: 5px 10px;
            text-align: right;
        }
        .testimonial-all{
            background: none repeat scroll 0 0 <?php echo $picker1; ?>;
            display:inline-block;
            border-radius:5px;
            padding: 10px;
            width: 100%;
        }
        .display-all-reviews-all{
            list-style:none;
        }
        .display-all-reviews-all li{
            margin: 0px 0px 10px 0px;
        }
        .listing-all-reviews-all{
            width:100%
        }
    </style>
    <script>
        var ratingUrl = "<?php echo plugins_url(); ?>/wp-social-seo/";
    </script>
    <?php
    $Lists = $wpdb->get_results('SELECT * FROM  ' . $wpdb->prefix . 'rich_snippets_review');
    if (!empty($Lists)) {
        $i = 0;
        $newi=1;
        $display = '';
        $display .= '<div id="fb-root"></div><script>(function(d, s, id) {  var js, fjs = d.getElementsByTagName(s)[0];  if (d.getElementById(id)) return;  js = d.createElement(s); js.id = id;  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";  fjs.parentNode.insertBefore(js, fjs);}(document, \'script\', \'facebook-jssdk\'));</script>';
        $display .='<script>jQuery(document).ready(function () {                   
        jQuery(\'.basic\').jRating({
	  isDisabled : true
	});
        });</script> <div class="listing-all-reviews-all"><ul class="display-all-reviews-all">';
        foreach ($Lists as $List) {
            $display .='
            <li>
            <div class = "hms-testimonial-container-all" itemscope itemtype="http://schema.org/Review">
            <div class = "testimonial-all">
            <div class = "top-class-all">
            <div class = "gnrl-class-all" itemprop="itemReviewed" itemscope itemtype="http://schema.org/Thing"><span itemprop="name">' . stripcslashes($List->item_name) . '</span></div>
            <div class = "gnrl-class-all" itemprop = "description">' . preg_replace('/\\\\/', '', $List->description) . '</div>
            </div>
            <div class = "bottom-class-all">
            <div class = "gnrl-new-class-all" itemprop="author" itemscope="" itemtype="http://schema.org/Person">Reviewed by <i><a href = "' . $List->url . '" target = "_blank"><span itemprop="name">' . stripcslashes($List->reviewer_name) . '</span></a></i> on <i>' . $List->date_reviewed . '</i></div>
            <div class = "gnrl-new-class-all" itemprop="reviewRating" itemscope="" itemtype="http://schema.org/Rating"> <span itemprop="ratingValue" style="display:none;">' . $List->rating . '</span><div class = "basic" data-average = "' . $List->rating . '" data-id = "pn-display-all-rich-snippets-'.$newi.'"></div></div>
            </div>
            </div>
            </div>
            </li>';
            $newi++;
        }
        $display .=' </ul > </div>';
        return $display;
    }
}
?>