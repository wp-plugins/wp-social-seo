<?php

// Creating the widget 
class reviews extends WP_Widget {

    function __construct() {
        parent::__construct(
// Base ID of your widget
                'social_rich_reviews',
// Widget name will appear in UI
                __('Structured Markup : Reviews', 'wps_widget_domain'),
// Widget description
                array('description' => __('Display Reviews', 'wps_widget_domain'),)
        );
    }

// Creating widget front-end
// This is where the action happens
    public function widget($args, $instance) {
//        global $wpdb;
        $title = apply_filters('widget_title', $instance['title']);
// before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];
//        echo "<div class='locationdiv'><form action='' method='post' style='display:inline;'><input type='text'name='location_search_box' class='location_search_box' id='location_search_box' placeholder='Find your local office'/></form></div>";


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
        wp_enqueue_style('carouselcss', plugins_url('../css/jquery.bxslider.css', __FILE__));
        wp_enqueue_style('ratingcss', plugins_url('../js/jRating.jquery.css', __FILE__));
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery_carousel', plugins_url('../js/jquery.bxslider.js', __FILE__));
        wp_enqueue_script('jquery_rating', plugins_url('../js/jRating.jquery.js', __FILE__));
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
            <div class = "hms-testimonial-container-new" itemscope itemtype = "http://data-vocabulary.org/Review">
            <div class = "testimonial">
            <div class = "top-class">
            <div class = "gnrl-class" itemprop = "itemreviewed">' . stripcslashes($List->item_name) . '</div>
            <div class = "gnrl-class" itemprop = "description">' . preg_replace('/\\\\/', '', substr($List->description, 0, 100)) . '</div>
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
            echo  $display;
        } else {
            echo '';
        }
        ?>
        <?php
    }

// Widget Backend 
    public function form($instance) {
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('New title', 'wps_widget_domain');
        }
// Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <?php
    }

// Updating widget replacing old instances with new
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title']) ) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }

}
