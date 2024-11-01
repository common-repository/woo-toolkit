<?php
// Register and load the widget
function wpb_load_widget() {
    register_widget( 'wpb_widget' );
}
add_action( 'widgets_init', 'wpb_load_widget' );

// Creating the widget 
class wpb_widget extends WC_Widget {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->widget_cssclass    = 'woocommerce widget_recently_viewed_products';
        $this->widget_description = __( "Show list of recently sold products", 'woocommerce' );
        $this->widget_id          = 'woocommerce_recently_sold_products';
        $this->widget_name        = __( 'Recently Sold Products', 'woocommerce' );
        $this->settings           = array(
            'title'  => array(
                'type'  => 'text',
                'std'   => __( 'Recently Sold Products', 'woocommerce' ),
                'label' => __( 'Title', 'woocommerce' ),
            ),
            'number' => array(
                'type'  => 'number',
                'step'  => 1,
                'min'   => 1,
                'max'   => 15,
                'std'   => 10,
                'label' => __( 'Number of products to show', 'woocommerce' ),
            ),
        );

        parent::__construct();
    }

// Creating widget front-end

public function widget( $args, $instance ) {
    $title = apply_filters( 'widget_title', $instance['title'] );
    $instance['number'] = 10;


// This is where you run the code and display the output
    $after_date    = date( 'Y-m-d', strtotime('-7 days') );

    $args = apply_filters( 'dokan_widget_args', array(
        'widget_id'     => 'woocommerce_recently_sold_products',
        'before_widget' => '<aside id="%1$s" class="widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
        'numberposts'   => 10,
        'post_status'   => 'wc-completed',
        'date_query'    => array(
                'after'     => $after_date,
                'inclusive' => true
            )
    ) );

    $orders = wc_get_orders( $args );
    $products = [];

    foreach ( $orders as $order ) {
        $items = $order->get_items();
        
        foreach ( $items as $item ) {
            array_push( $products, $item->get_product_id() );
        }
    }


    $products = count($products) ? $products : [0];


            ob_start();

        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : $this->settings['number']['std'];

        $query_args = array(
            'posts_per_page' => $number,
            'no_found_rows'  => 1,
            'post_status'    => 'publish',
            'post_type'      => 'product',
            'post__in'       => $products,
            'orderby'        => 'post__in',
        );

        if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => 'outofstock',
                    'operator' => 'NOT IN',
                ),
            ); // WPCS: slow query ok.
        }

        $r = new WP_Query( apply_filters( 'woocommerce_recently_sold_products_widget_query_args', $query_args ) );

        if ( $r->have_posts() ) {

            $this->widget_start( $args, $instance );

            echo wp_kses_post( apply_filters( 'woocommerce_before_widget_product_list', '<ul class="product_list_widget">' ) );

            $template_args = array(
                'widget_id' => $args['widget_id'],
            );

            while ( $r->have_posts() ) {
                $r->the_post();
                wc_get_template( 'content-widget-product.php', $template_args );
            }

            echo wp_kses_post( apply_filters( 'woocommerce_after_widget_product_list', '</ul>' ) );

            $this->widget_end( $args );
        } else {
            $this->widget_start( $args, $instance );

            echo wp_kses_post( apply_filters( 'woocommerce_before_widget_product_list', '<ul class="product_list_widget">' ) );

            $template_args = array(
                'widget_id' => $args['widget_id'],
            );

          echo __('No order placed yet ','woocommerce'); ?><i class="fa fa-frown-o"></i><?php

            echo wp_kses_post( apply_filters( 'woocommerce_after_widget_product_list', '</ul>' ) );

            $this->widget_end( $args );
        }

        wp_reset_postdata();

        $content = ob_get_clean();

        echo $content; // WPCS: XSS ok.
    }
    


    
// Updating widget replacing old instances with new

public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    return $instance;
    }
} 
