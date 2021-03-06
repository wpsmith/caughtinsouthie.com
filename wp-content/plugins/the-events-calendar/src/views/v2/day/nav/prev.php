<?php
/**
 * View: Day View Nav Previous Button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events/v2/day/nav/prev.php
 *
 * See more documentation about our views templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @var string $link The URL to the previous page.
 *
 * @version 4.9.10
 *
 */
?>
<li class="tribe-events-c-nav__list-item tribe-events-c-nav__list-item--prev">
	<a
		href="<?php echo esc_url( $link ); ?>"
		rel="prev"
		class="tribe-events-c-nav__prev tribe-common-b2 tribe-common-b1--min-medium"
		data-js="tribe-events-view-link"
	>
		<?php esc_html_e( 'Previous Day', 'the-events-calendar' ); ?>
	</a>
</li>
