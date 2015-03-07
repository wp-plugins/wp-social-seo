<?php
error_reporting(0);
/**
 * Plugin Name: Wp Social
 * Plugin URI: http://www.web9.co.uk/
 * Description: Use structured data markup embedded in your public website to specify your preferred social profiles. You can specify these types of social profiles: Facebook, Twitter, Google+, Instagram, YouTube, LinkedIn and Myspace.
 * Version: 2.7
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
add_action('admin_menu', 'wps_admin_init');
add_action('admin_post_submit-wnp-settings', 'wpsSaveSettings');
add_action('admin_post_submit-wps-company', 'wpsSaveCompany');
add_action('admin_post_submit-facebook-review', 'wpsFacebookReview');
add_action('admin_post_submit-rich-snippets-review', 'wpsSaveRichSnippets');
add_shortcode('facebook-review-slider', 'bartag_func');
add_shortcode('wps-rich-snippets', 'display_rich_snippets');

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
    $wpdb->query("ALTER TABLE `" . $wpdb->prefix . "rich_snippets_review" . "` ADD url TEXT NOT NULL AFTER `description`");
    add_menu_page(__('Structured Data', 'wps'), __('Structured Data', 'wps'), 'manage_options', 'wps-social-profile', 'wpscallWebNicePlc', '');
    //add_submenu_page('', __('Your company', 'wps'), __('Your company', 'wps'), 'manage_options', 'wps-manage-your-company', 'wpsmanageCompany');
    add_submenu_page('', __('Social seo', 'wps'), __('Social seo', 'wps'), 'manage_options', 'wps-manage-social-seo', 'wpsmanageSocialSeo');
    add_submenu_page('', __('Facebook review', 'wps'), __('Facebook review', 'wps'), 'manage_options', 'wps-facebook-review', 'wpsmanageFacebookReview');
    add_submenu_page('', __('Rich snippets review', 'wps'), __('Rich snippets review', 'wps'), 'manage_options', 'wps-rich-snippets-review', 'wpsmanageRichSnippets');
    add_submenu_page('', __('Rich snippets review', 'wps'), __('Rich snippets review', 'wps'), 'manage_options', 'wps-add-rich-snippets-review', 'wpsmanageAddRichSnippets');
    add_submenu_page('', __('Rich snippets review', 'wps'), __('Rich snippets review', 'wps'), 'manage_options', 'wps-delete-snipepts-review', 'wpsmanageDeleteRichSnippets');
}

function wps_load_custom_wp_admin_style() {
    wp_enqueue_style('wpsadminstyle', plugins_url('css/wps-admin-style.css', __FILE__));
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-form');
}

add_action('admin_enqueue_scripts', 'wps_load_custom_wp_admin_style');

function wpscallWebNicePlc() {
    $pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
    wp_register_style('wp-social-css', $pluginDirectory . 'css/wp-social-seo.css');
    wp_enqueue_style('wp-social-css');
    $get_option_details = unserialize(get_option('wnp_your_company'));
    $my_plugin_tabs = array(
        'wps-social-profile' => 'Your company',
        'wps-manage-social-seo' => 'Social seo',
        'wps-facebook-review' => 'Facebook review',
        'wps-rich-snippets-review' => 'Rich snippets review',
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
               <p>Specify your social profiles to Googlehttps://developers.google.com/webmasters/structured-data/customize/social-profiles</p>
               <p>Use mark-up on your official website to add your social profile information to the Google Knowledge panel in some searches. Knowledge panels can prominently display your social profile information.</p>
               <p>Our other free plugins can be found at https://profiles.wordpress.org/pigeonhut/</p>
               <p>To see more about us as a company, visit http://www.web9.co.uk</p>
               <p>Proudly made in Belfast, Northern Ireland.</p>';
    echo $output;
}

function wpsmanageSocialSeo() {
    $pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
    wp_register_style('wp-social-css', $pluginDirectory . 'css/wp-social-seo.css');
    wp_enqueue_style('wp-social-css');
    $get_option_details = unserialize(get_option('wnp_social_settings'));
    $my_plugin_tabs = array(
        'wps-social-profile' => 'Your company',
        'wps-manage-social-seo' => 'Social seo',
        'wps-facebook-review' => 'Facebook review',
        'wps-rich-snippets-review' => 'Rich snippets review',
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
        <h2><?php _e('Wp Social profile settings', 'wnp'); ?></h2> 
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="left-side">
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Social Seo');
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
                                            <span style="background: none repeat scroll 0 0 #99ff99;padding: 10px;">You can test your Data using <a target="_blank" href="https://developers.google.com/webmasters/structured-data/testing-tool/">Google's Structured Data Testing Tool </a></span>
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
        <?php displayRight(); ?>
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
        'wps-social-profile' => 'Your company',
        'wps-manage-social-seo' => 'Social seo',
        'wps-facebook-review' => 'Facebook review',
        'wps-rich-snippets-review' => 'Rich snippets review',
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
        <?php displayRight(); ?>
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
        'wps-social-profile' => 'Your company',
        'wps-manage-social-seo' => 'Social seo',
        'wps-facebook-review' => 'Facebook review',
        'wps-rich-snippets-review' => 'Rich snippets review',
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
            var descriptionValue = jQuery('input[name=description]').fieldValue();
            var ratingValue = jQuery('input[name=rating]').fieldValue();
            // usernameValue and passwordValue are arrays but we can do simple
            // "not" tests to see if the arrays are empty
            if (!itemValue[0]) {
                alert('Please enter a item name');
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
            if (!summary[0]) {
                alert('Please enter summary');
                return false;
            }
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
                NMRichReviewsAdminHelper::render_postbox_open('Facebook Reviews');
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
                                        <td>Item name : </td>
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
                                    <tr height="50">
                                        <td>Summary : </td>
                                        <td><input type="text" id="summary" name="summary" value="<?php echo $getSummary; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Description : </td>
                                        <td><input type="text" id="description" name="description" value="<?php echo $getDescription; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>URL : </td>
                                        <td><input type="text" id="url" name="url" value="<?php echo $getUrl; ?>" /></td>
                                    </tr>
                                    <tr height="50">
                                        <td>Rating : </td>
                                        <td><input type="text" id="rating" name="rating" value="<?php echo $getRating; ?>" /></td>
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
        <?php displayRight(); ?>
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
        'wps-social-profile' => 'Your company',
        'wps-manage-social-seo' => 'Social seo',
        'wps-facebook-review' => 'Facebook review',
        'wps-rich-snippets-review' => 'Rich snippets review',
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
        <h2><?php _e('Rich snippets review', 'cqp'); ?> <a class="add-new-h2" href="<?php echo admin_url() ?>admin.php?page=wps-add-rich-snippets-review">Add New</a></h2>         
        <div id="poststuff" class="metabox-holder ppw-settings">
            <div class="left-side">
                <?php
                NMRichReviewsAdminHelper::render_container_open('content-container');
                NMRichReviewsAdminHelper::render_postbox_open('Facebook Reviews');
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
        <?php displayRight(); ?>
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
        jQuery(\'.bxslider\').bxSlider({
        pager :false,
        auto:true,
        mode:\'fade\',
        speed: 1000,
        pause:10000,
        controls:false,
        autoHover:true
        });        
        });</script>       
                    <ul class="bxslider">';
    foreach ($names as $name) {
        $render .= '<li><div style = "float:left;" class = "fb-post" data-href = "https://www.facebook.com/' . $name . '/posts/' . $get_option_details['id'][$i - 1] . '" data-width = "100%">
        <div class = "fb-xfbml-parse-ignore">Post by ' . str_replace('.', ' ', $name) . '.</div>
        </div></li>';
        $i++;
    }
    $render .=' </ul>';
    return $render;
}

function display_rich_snippets() {
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
        .top-class{
            background: none repeat scroll 0 0 #fff000;
            border-radius: 5px;
            color: #000 !important;
            margin-bottom: 5px;
            /*            margin-top: 30px;*/
            padding: 10px;
        }
        .bottom-class {
            background: none repeat scroll 0 0 #fff;
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
            background: none repeat scroll 0 0 #ccc;
            display:inline-block;
            border-radius:5px;
            padding: 10px;
        }
    </style>
    <script>
        var ratingUrl = "<?php echo plugins_url(); ?>/wp-social-seo/";
    </script>
    <?php
    session_start();
    global $wpdb;
    wp_enqueue_style('carouselcss', plugins_url('css/jquery.bxslider.css', __FILE__));
    wp_enqueue_style('ratingcss', plugins_url('js/jRating.jquery.css', __FILE__));
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery_carousel', plugins_url('js/jquery.bxslider.js', __FILE__));
    wp_enqueue_script('jquery_rating', plugins_url('js/jRating.jquery.js', __FILE__));
    $Lists = $wpdb->get_results('SELECT * FROM  ' . $wpdb->prefix . 'rich_snippets_review');
    if (!empty($Lists)) {
        //echo $wpdb->last_query;
        $i = 0;
        $display = '';
        $display .= '<div id="fb-root"></div><script>(function(d, s, id) {  var js, fjs = d.getElementsByTagName(s)[0];  if (d.getElementById(id)) return;  js = d.createElement(s); js.id = id;  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";  fjs.parentNode.insertBefore(js, fjs);}(document, \'script\', \'facebook-jssdk\'));</script>';
        $display .='<script>jQuery(document).ready(function () {           
        jQuery(\'.bxslider\').bxSlider({
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
                    <ul class="bxslider">';
        foreach ($Lists as $List) {
            $display .='
            <li>
            <div class = "hms-testimonial-container" itemscope itemtype = "http://data-vocabulary.org/Review">
            <div class = "testimonial">
            <div class = "top-class">
            <div class = "gnrl-class" itemprop = "itemreviewed">' . stripcslashes($List->item_name) . '</div>
            <div class = "gnrl-class" itemprop = "description">' . preg_replace('/\\\\/', '', $List->description) . '</div>
            </div>
            <div class = "bottom-class">
            <div class = "gnrl-new-class" itemprop = "reviewer">Reviewed by <i><a href = "' . $List->url . '" target = "_blank">' . stripcslashes($List->reviewer_name) . '</a></i> on <time itemprop = "dtreviewed" datetime = "' . $List->date_reviewed . '"><i>' . $List->date_reviewed . '</i></time></div>
            <div class = "gnrl-new-class" itemprop = "rating"><div class = "basic" data-average = "' . $List->rating . '" data-id = "1"></div></div>
            </div>
            </div>
            </div>
            </li>';
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
?>