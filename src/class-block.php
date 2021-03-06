<?php
/**
 * The file that provides CSS and JS assets for the theme.
 *
 * @link       https://github.com/AgriLife/agriflex4/blob/master/src/class-block.php
 * @since      1.0.0
 * @package    agriflex4
 * @subpackage agriflex4/src
 */

namespace AgriLife_LiveWhale;

/**
 * Loads required theme assets
 *
 * @package AgriFlex4
 * @since 1.0.0
 */
class Block {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}


	/**
	 * Build the LiveWhale JSON API URL
	 *
	 * @since 1.0.0
	 * @param array $attributes Associative array of block attributes.
	 * @return string
	 */
	private function get_url( $attributes ) {

		$url = 'https://calendar.tamu.edu/live/json/events';

		if ( array_key_exists( 'count', $attributes ) && ! empty( $attributes['count'] ) ) {
			$url .= '/max/' . $attributes['count'];
		}

		if ( array_key_exists( 'subscription', $attributes ) && ! empty( $attributes['subscription'] ) ) {
			$url .= '/subscription/' . $attributes['subscription'];
		}

		if ( array_key_exists( 'group', $attributes ) && ! empty( $attributes['group'] ) ) {
			$url .= '/group/' . $attributes['group'];
		}

		if ( array_key_exists( 'category', $attributes ) && ! empty( $attributes['category'] ) ) {
			$url .= '/category/' . $attributes['category'];
		}

		if ( array_key_exists( 'tag', $attributes ) && ! empty( $attributes['tag'] ) ) {
			$url .= '/tag/' . $attributes['tag'];
		}

		if ( array_key_exists( 'starred', $attributes ) && true === $attributes['starred'] ) {
			$url .= '/only_starred/true';
		}

		$url .= '/hide_repeats/';

		return $url;

	}

	/**
	 * Initialize the class actions
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {

		wp_register_script(
			'agrilife-livewhale',
			AGLVW_DIR_URL . '/js/block-livewhale.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'jquery' ),
			filemtime( AGLVW_DIR_PATH . '/js/block-livewhale.js' ),
			true
		);

		register_block_type(
			'agriflex4/livewhale-calendar',
			array(
				'editor_script'   => 'agrilife-livewhale',
				'render_callback' => array( $this, 'cgb_api_block_posts' ),
				'attributes'      => array(
					'group' => array(
						'type' => 'string',
					),
				),
			)
		);
	}

	/**
	 * Handle block content before adding to webpage.
	 *
	 * @since 1.0.0
	 * @param array  $attributes The block's attributes.
	 * @param string $content The block's content.
	 * @return string
	 */
	public function cgb_api_block_posts( $attributes, $content ) {

		// Declare HTML for calendar and events.
		$cal_template   = '<div class="alignfull livewhale livewhale-block invert"><div class="grid-container"><div class="grid-x grid-padding-x padding-y"><div class="events-cell cell medium-auto small-12 grid-container"><div class="grid-x grid-padding-x">%s</div></div>%s</div></div></div>';
		$event_template = '<div class="event cell medium-auto small-12"><div class="grid-x grid-padding-x"><div class="cell date shrink"><div class="month h3">%s</div><div class="h2 day">%s</div></div><div class="cell title auto"><a href="%s" title="%s" class="event-title medium-truncate-lines medium-truncate-2-lines">%s</a><div class="location medium-truncate-lines medium-truncate-1-line">%s</div></div></div></div>';
		$all_events     = '';

		// Decide how many calendar items to display.
		$count = 3;
		if ( array_key_exists( 'count', $attributes ) && ! empty( $attributes['count'] ) ) {
			$count = $attributes['count'];
		} else {
			$attributes['count'] = $count;
		}

		// Build the LiveWhale Feed.
		$furl      = $this->get_url( $attributes );
		$output    = '';
		$feed_json = wp_remote_get( $furl );

		if ( false === is_wp_error( $feed_json ) ) {

			$feed_array   = json_decode( $feed_json['body'], true );
			$l_events     = array_slice( $feed_array, 0, $count ); // Choose number of events.
			$l_event_list = '';
			$group        = 'agrilife';

			if ( array_key_exists( 'group', $attributes ) && ! empty( $attributes['group'] ) ) {

				$group = $attributes['group'];

			}

			$all_url = '';

			if ( isset( $attributes['allevents'] ) ) {

				$all_url = $attributes['allevents'];

			} else if ( isset( $attributes['all_url'] ) ) {

				$all_url = $attributes['all_url'];

			}

			if ( ! empty( $all_url ) ) {

				$all_events = sprintf(
					'<div class="events-all cell medium-shrink small-12"><a class="h3 arrow-right" href="%s">All Events</a></div>',
					$all_url
				);

			}

			foreach ( $l_events as $event ) {

				$title      = $event['title'];
				$url        = $event['url'];
				$location   = $event['location'];
				$date       = $event['date_utc'];
				$time       = $event['date_time'];
				$date       = date_create( $date );
				$date_day   = date_format( $date, 'd' );
				$date_month = date_format( $date, 'M' );

				if ( array_key_exists( 'custom_room_number', $event ) && ! empty( $event['custom_room_number'] ) ) {

					$location .= ' ' . $event['custom_room_number'];

				}

				$l_event_list .= sprintf(
					$event_template,
					$date_month,
					$date_day,
					$url,
					$title,
					$title,
					$location
				);

			}

			$output .= sprintf(
				$cal_template,
				$l_event_list,
				$all_events
			);

		}

		return $output;

	}
}
