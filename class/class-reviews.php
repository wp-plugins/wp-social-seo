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
        $slider_interval = 10000;
        $transitionspeed = 3000;
        $height = '100px';
        $title = apply_filters('widget_title', $instance['title']);
        if (isset($instance['slider_speed']))
            $slider_interval = apply_filters('widget_title', $instance['slider_speed']);
        if (isset($instance['transition_speed']))
            $transitionspeed = apply_filters('widget_title', $instance['transition_speed']);
        if (isset($instance['height']))
            $height = apply_filters('widget_title', $instance['height']);

//        echo "<div class='locationdiv'><form action='' method='post' style='display:inline;'><input type='text'name='location_search_box' class='location_search_box' id='location_search_box' placeholder='Find your local office'/></form></div>";
        session_start();
        global $wpdb;
        $picker1 = '#CCCCCC';
        $picker2 = '#FFF000';
        $picker3 = '#FFFFFF';
        $picker4 = '#000000';
        $picker5 = '#000000';
        $picker6 = '#000000';

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
            if (isset($get_option_details['picker5']) && $get_option_details['picker5'] != '')
                $picker5 = $get_option_details['picker5'];
            if (isset($get_option_details['picker6']) && $get_option_details['picker6'] != '')
                $picker6 = $get_option_details['picker6'];
         
        } else {
            $picker1 = '#CCCCCC';
            $picker2 = '#FFF000';
            $picker3 = '#FFFFFF';
            $picker4 = '#000000';
            $picker5 = '#000000';
            $picker6 = '#000000';
         
        }
        ?>
        <style>       
            .gnrl-class{
                padding: 0px 0px 10px 0px;
                display:block;
                float:left;
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
                color: <?php echo $picker6; ?> !important;
                margin-bottom: 5px;
                /*            margin-top: 30px;*/
                padding: 10px;
                height: <?php echo $height; ?>;
            }
            .gnrl-class{
                color:<?php echo $picker6; ?> !important;
            }
            .bottom-class {
                background: none repeat scroll 0 0 <?php echo $picker3; ?>;
                border-radius: 5px;
                color: <?php echo $picker5;?> !important;
                margin:10px auto;
                display: inline-block;
             
                font-style: italic;
                font-weight: normal;
                padding: 5px 10px;
                text-align: right;

            }
            .testimonial{


                background: none repeat scroll 0 0 <?php echo $picker1; ?>;
                display:inline-block;
             border: 1px solid #eee;
             
                width: 100%;
            }
.testimonial-header {
    /*background: rgba(0, 0, 0, 0) url("/wp-content/plugins/wp-social-seo/images/review-header.png") no-repeat scroll center center;*/
    height: 80px;
    border-radius:5px 5px 0 0;
    background-color:#6BC600;
    color:#fff;
    font-size: 26px;
      text-align: center;
      padding-top: 18px;

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
        $Lists = $wpdb->get_results('SELECT * FROM  ' . $wpdb->prefix . 'rich_snippets_review WHERE pageid='.get_the_ID().' ORDER BY rand()');
        if (!empty($Lists)) {
            
// before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];
            //echo $wpdb->last_query;
            $i = 0;
            $newi=1;
            $display = '';
            $display .='<script>jQuery(document).ready(function () {           
        jQuery(\'.bxslider-reviews\').bxSlider({
        pager :false,
        auto:true,
        mode:\'fade\',
        speed: ' . $transitionspeed . ',
        pause:' . $slider_interval . ',
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
            <div class="testimonial-header">
                Excellent';
                  $display.=' <div class = "gnrl-new-class" itemprop="reviewRating" itemscope="" itemtype="http://schema.org/Rating"><span itemprop="ratingValue" style="display:none;">' . $List->rating . '</span><div class = "basic" data-average = "' . $List->rating . '" data-id = "pn-widget-rich-snippets-'.$newi.'"></div></div> 
            </div>
               <div class = "bottom-class">
           <div class = "gnrl-class" itemprop="itemReviewed" itemscope itemtype="http://schema.org/Thing"><span itemprop="name">' . stripcslashes($List->item_name) . '</span></div>
          </div>
           
           
            <div class = "top-class">
           
            <div class = "gnrl-class" itemprop = "description">' . preg_replace('/\\\\/', '', substr($List->description, 0, 100)) . '</div>
 
 
  <div class = "gnrl-new-class" itemprop="author" itemscope="" itemtype="http://schema.org/Person">Reviewed by <i><a href = "' . $List->url . '" target = "_blank"><span itemprop="name">' . stripcslashes($List->reviewer_name) . '</span></a></i> on <i>' . $List->date_reviewed . '</i></div>
            </div>
         
           
            </div>
            </div>
            </li>';
                 $newi++;
            }
            $display .= ' </ul > ';
            echo $display;
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
        if (isset($instance['height'])) {
            $height = $instance['height'];
        } else {
            $height = __('100px', 'height');
        }

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
        <p>
            <label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height of the review content:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo esc_attr($height); ?>" />
        </p>
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
