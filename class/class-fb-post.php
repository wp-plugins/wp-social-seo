<?php

// Creating the widget 
class fbpost extends WP_Widget {

    function __construct() {
        parent::__construct(
// Base ID of your widget
                'social_fb_post',
// Widget name will appear in UI
                __('Structured Markup : Fb Post', 'wps_widget_domain'),
// Widget description
                array('description' => __('Display Reviews', 'wps_widget_domain'),)
        );
    }

// Creating widget front-end
// This is where the action happens
    public function widget($args, $instance) {
//        global $wpdb;
        $slider_interval = 10000;
        $transitionspeed = 3000;
        $height = '100px';
        $title = apply_filters('widget_title', $instance['title']);
        if (isset($instance['slider_speed']))
            $slider_interval = apply_filters('widget_title', $instance['slider_speed']);
        if (isset($instance['transition_speed']))
            $transitionspeed = apply_filters('widget_title', $instance['transition_speed']);
//        if (isset($instance['height']))
//            $height = apply_filters('widget_title', $instance['height']);

// before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];
//        echo "<div class='locationdiv'><form action='' method='post' style='display:inline;'><input type='text'name='location_search_box' class='location_search_box' id='location_search_box' placeholder='Find your local office'/></form></div>";
        session_start();
        global $wpdb;        
        ?>       
        <script>
            var ratingUrl = "<?php echo plugins_url(); ?>/wp-social-seo/";
        </script>
        <?php
        wp_enqueue_style('carouselcss', plugins_url('../css/jquery.bxslider.css', __FILE__));
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery_carousel', plugins_url('../js/jquery.bxslider.js', __FILE__));
        $get_option_details = unserialize(get_option('wnp_facebook_reviews'));
        $names = $get_option_details['name'];
        if (!empty($names)) {
            $i = 1;
            $render = '';
            $render .= '<div id="fb-root"></div><script>(function(d, s, id) {  var js, fjs = d.getElementsByTagName(s)[0];  if (d.getElementById(id)) return;  js = d.createElement(s); js.id = id;  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";  fjs.parentNode.insertBefore(js, fjs);}(document, \'script\', \'facebook-jssdk\'));</script>';
            $render .='<script>jQuery(document).ready(function () {
        jQuery(\'.bxslider-fb\').bxSlider({
        pager :false,
        auto:true,
        mode:\'fade\',
        speed: ' . $transitionspeed . ',
        pause:' . $slider_interval . ',
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
            echo $render;
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
        if (isset($instance['slider_speed'])) {
            $slider_speed = $instance['slider_speed'];
        } else {
            $slider_speed = __(5000, 'wps_widget_domain');
        }
        if (isset($instance['transition_speed'])) {
            $transition_speed = $instance['transition_speed'];
        } else {
            $transition_speed = __(5000, 'wps_widget_domain');
        }
//        if (isset($instance['height'])) {
//            $height = $instance['height'];
//        } else {
//            $height = __('100px', 'height');
//        }

// Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('slider_speed'); ?>"><?php _e('Slider interval time:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('slider_speed'); ?>" name="<?php echo $this->get_field_name('slider_speed'); ?>" type="text" value="<?php echo esc_attr($slider_speed); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('transition_speed'); ?>"><?php _e('Transition speed:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('transition_speed'); ?>" name="<?php echo $this->get_field_name('transition_speed'); ?>" type="text" value="<?php echo esc_attr($transition_speed); ?>" />
        </p>
<!--        <p>
            <label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height of the review content:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo esc_attr($height); ?>" />
        </p>-->
        <?php
    }

// Updating widget replacing old instances with new
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title']) ) ? strip_tags($new_instance['title']) : '';
        $instance['slider_speed'] = (!empty($new_instance['slider_speed']) ) ? strip_tags($new_instance['slider_speed']) : '';
        $instance['transition_speed'] = (!empty($new_instance['transition_speed']) ) ? strip_tags($new_instance['transition_speed']) : '';
        $instance['height'] = (!empty($new_instance['height']) ) ? strip_tags($new_instance['height']) : '';
        return $instance;
    }

}
