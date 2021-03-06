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
        $review_number = 3;
        $review_category = '';
        $title = apply_filters('widget_title', $instance['title']);
        if (isset($instance['review_number']))
            $review_number = apply_filters('widget_title', $instance['review_number']);
        if (isset($instance['review_category']))
            $review_category = apply_filters('widget_title', $instance['review_category']);
        if (isset($instance['height']))
            $height = apply_filters('widget_title', $instance['height']);

//        echo "<div class='locationdiv'><form action='' method='post' style='display:inline;'><input type='text'name='location_search_box' class='location_search_box' id='location_search_box' placeholder='Find your local office'/></form></div>";
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
                height: <?php echo $height; ?>;
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
            .widget_social_rich_reviews .bxslider-reviews li{
                padding:0px 0px 10px 0px;
            }
        </style>
        <script>
            var ratingUrl = "<?php echo plugins_url(); ?>/wp-social-seo/";
        </script>
        <?php
        wp_enqueue_style('carouselcss', plugins_url('../css/jquery.bxslider.css', __FILE__));
        wp_enqueue_style('ratingcss', plugins_url('../js/jRating.jquery.css', __FILE__));
        wp_enqueue_script('jquery');
//        wp_enqueue_script('jquery_carousel', plugins_url('../js/jquery.bxslider.js', __FILE__));
        wp_enqueue_script('jquery_rating', plugins_url('../js/jRating.jquery.js', __FILE__));
        $where_condition = '';
        if ($review_category != '' && $review_category != 'none') {
            if ($review_category == 'page')
                $where_condition .=' WHERE category="' . get_the_ID() . '"';
            else if ($review_category == 'post')
                $where_condition .=' WHERE category="' . get_the_ID() . '"';
            else
                $where_condition .=' WHERE category="' . $review_category . '"';
        }
        if ($review_number != 'all')
            $limit = ' LIMIT 0,' . $review_number;
        else
            $limit = '';
        //echo 'SELECT * FROM  ' . $wpdb->prefix . 'rich_snippets_review '.$where_condition.' LIMIT 0,'.$review_number;
        $Lists = $wpdb->get_results('SELECT * FROM  ' . $wpdb->prefix . 'rich_snippets_review ' . $where_condition . $limit);
        if (!empty($Lists)) {

// before and after widget arguments are defined by themes
            echo $args['before_widget'];
            if (!empty($title))
                echo $args['before_title'] . $title . $args['after_title'];
            //echo $wpdb->last_query;
            $i = 0;
            $newi = 1;
            $display = '';
            $display .='<ul class="bxslider-reviews">';
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
            <div class = "gnrl-new-class" itemprop="reviewRating" itemscope="" itemtype="http://schema.org/Rating"><span itemprop="ratingValue" style="display:none;">' . $List->rating . '</span><div class = "basic" data-average = "' . $List->rating . '" data-id = "pn-widget-rich-snippets-' . $newi . '"></div></div>
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
        if (isset($instance['review_number'])) {
            $review_number = $instance['review_number'];
        } else {
            $review_number = __(1, 'wps_widget_domain');
        }
        if (isset($instance['review_category'])) {
            $review_category = $instance['review_category'];
        } else {
            $review_category = __('', 'wps_widget_domain');
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
        <!--        <p>
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
        </p>-->
        <p>
            <label for="<?php echo $this->get_field_id('review_number'); ?>"><?php _e('Number'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('review_number'); ?>" name="<?php echo $this->get_field_name('review_number'); ?>" type="text" value="<?php echo esc_attr($review_number); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('review_category'); ?>"><?php _e('Category:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('review_category'); ?>" name="<?php echo $this->get_field_name('review_category'); ?>" type="text" value="<?php echo esc_attr($review_category); ?>" />
        </p>        
        <?php
    }

// Updating widget replacing old instances with new
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title']) ) ? strip_tags($new_instance['title']) : '';
        $instance['review_number'] = (!empty($new_instance['review_number']) ) ? strip_tags($new_instance['review_number']) : '';
        $instance['review_category'] = (!empty($new_instance['review_category']) ) ? strip_tags($new_instance['review_category']) : '';
        $instance['height'] = (!empty($new_instance['height']) ) ? strip_tags($new_instance['height']) : '';
        return $instance;
    }

}
