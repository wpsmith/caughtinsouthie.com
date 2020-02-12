<?php
/**
 * Properties Widget
 *
 * @package      CoreFunctionality
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

class EA_Properties_Widget extends WP_Widget {

	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $defaults;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// Widget Slug
		$widget_slug = 'widget_properties';

		// widget basics
		$widget_ops = array(
			'classname'   => $widget_slug,
			'description' => 'Displays latest property listings.'
		);

		// widget controls
		$control_ops = array(
			'id_base' => $widget_slug,
			//'width'   => '400',
		);

		// load widget
		parent::__construct( $widget_slug, 'Properties Widget', $widget_ops, $control_ops );

	}

	/**
	 * Outputs the HTML for this widget.
	 *
	 * @since 1.0.0
	 * @param array $args An array of standard parameters for widgets in this theme
	 * @param array $instance An array of settings for this widget instance
	 */
	function widget( $args, $instance ) {

		// Don't run on property archive
		if( is_post_type_archive( 'propery' ) )
			return;

		extract( $args );

		$loop = new WP_Query( array(
			'posts_per_page' => 3,
			'post_type' => 'property'
		));

		if( $loop->have_posts() ):

			echo $before_widget;
			echo '<h3 class="widget-title">Latest <span class="grey">Property</span> <span class="green">Listings</a> <a class="go" href="' . get_post_type_archive_link( 'property' ) . '">' . ea_icon( 'arrow-right' ) . '</a></h3>';
			while( $loop->have_posts() ): $loop->the_post();
				get_template_part( 'partials/archive' );
			endwhile;
			echo $after_widget;

		endif;
		wp_reset_postdata();
	}

}
add_action( 'widgets_init', function(){ register_widget( 'EA_Properties_Widget' ); } );
