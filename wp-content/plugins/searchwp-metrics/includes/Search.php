<?php

namespace SearchWP_Metrics;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Search
 * @package SearchWP_Metrics
 */
class Search {

	private $metrics;
	private $engine = 'default';
	private $query;
	private $query_id;
	private $hits;
	private $query_hash;
	private $query_hash_id;
	private $uid_hash;
	private $uid_hash_id;

	/**
	 * Search constructor.
	 *
	 * @param string $query
	 * @param string $engine
	 * @param int $hits
	 */
	function __construct( $query = '', $engine = 'default', $hits = 0, $metrics ) {
		$this->metrics = new \SearchWP_Metrics();
		$this->metrics->set_uid();

		if ( ! empty( $query ) ) {
			$this->set_query( $query );
		}

		if ( ! empty( $engine ) ) {
			$this->set_engine( $engine );
		}

		if ( ! empty( $hits ) ) {
			$this->set_hits( $hits );
		}
	}

	/**
	 * Setter for engine
	 *
	 * @param string $engine Engine name
	 */
	function set_engine( $engine ) {
		$this->engine = function_exists( 'SWP' ) && SWP()->is_valid_engine( $engine ) ? $engine : 'default';
	}

	/**
	 * Normalizes a string to a standard format
	 */
	public function normalize( $string ) {
		$string = strtolower( $string );

		return $string;
	}

	/**
	 * Setter for the query
	 *
	 * @param string $query
	 */
	public function set_query( $query = '' ) {
		global $wpdb;

		$this->query = trim( $query );

		// To improve accuracy we're going to normalize the data
		if ( apply_filters( 'searchwp_metrics_normalize_logged_searches', true ) ) {
			$this->query = $this->normalize( $this->query );
		}

		if ( empty( $query ) ) {
			return;
		}

		$queries_table = $this->metrics->get_table_name( 'queries' );

		// Ensure this query is in the queries table
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$queries_table} (query) VALUES (%s)",
				sanitize_text_field( $this->query )
			)
		);

		$this->set_query_id();
	}

	/**
	 * Setter for query ID
	 */
	private function set_query_id() {
		global $wpdb;

		if ( empty( $this->query ) ) {
			$this->query_id = 0;
			return;
		}

		$queries_table = $this->metrics->get_table_name( 'queries' );

		$query_id = $wpdb->get_var(
			$wpdb->prepare(
				"
				# noinspection SqlResolve
				SELECT id
				FROM $queries_table
				WHERE query = %s
				",
				$this->query
			)
		);

		$this->query_id = absint( $query_id );
	}

	/**
	 * Setter for the hit count
	 *
	 * @param int $hits
	 */
	public function set_hits( $hits ) {
		$this->hits = absint( $hits );
	}

	/**
	 * Getter for query
	 *
	 * @return string
	 */
	public function get_query() {
		return $this->query;
	}

	/**
	 * Getter for hash
	 *
	 * @return string
	 */
	public function get_hash() {
		do {
			$hash = md5( time() . mt_rand() . $this->metrics->get_uid() . $this->query_id . $this->engine );
		}

		while ( $this->hash_exists( $hash ) );

		return $hash;
	}

	/**
	 * Check to see if a specific hash exists
	 *
	 * @param $hash
	 *
	 * @return bool
	 */
	private function hash_exists( $hash ) {
		global $wpdb;

		if ( ! preg_match( '/^[a-f0-9]{32}$/', $hash ) ) {
			return false;
		}

		$searches_table = $this->metrics->get_table_name( 'searches' );

		$wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT hash
				FROM $searches_table
				WHERE hash = %s
				",
				$this->get_hash_table_value( $hash )
			)
		);

		if ( ( $wpdb->num_rows ) > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Get hash as table value (used for lookups)
	 *
	 * @param $hash
	 *
	 * @return string
	 */
	private function get_hash_table_value( $hash ) {
		return $hash . '_hash';
	}

	/**
	 * Getter for UID hash
	 *
	 * @return mixed
	 */
	public function get_uid_hash() {
		return $this->uid_hash;
	}

	/**
	 * Getter for UID hash ID
	 *
	 * @return mixed
	 */
	public function get_uid_hash_id() {
		return $this->uid_hash_id;
	}

	/**
	 * Getter for query hash
	 *
	 * @return mixed
	 */
	public function get_query_hash() {
		return $this->query_hash;
	}

	/**
	 * Getter for query hash ID
	 *
	 * @return mixed
	 */
	public function get_query_hash_id() {
		return $this->query_hash_id;
	}

	/**
	 * Log this search
	 *
	 * @return string
	 */
	public function log() {
		global $wpdb;

		if ( empty( $this->query_id ) || empty( $this->engine ) ) {
			return '';
		}

		// Determine UID hash and ID
		$uids                   = new \SearchWP_Metrics\ID( 'uid' );
		$this->uid_hash         = $this->metrics->get_uid();
		$this->uid_hash_id      = $uids->get_numeric_id_from_hash( $this->uid_hash );

		// Determine query hash and ID
		$hashes                 = new \SearchWP_Metrics\ID( 'hash' );
		$this->query_hash       = $hashes->generate();
		$this->query_hash_id    = $hashes->get_hash_id();

		$search_args = apply_filters( 'searchwp_metrics_search_args', array(
			'query'     => $this->query_id,
			'engine'    => $this->engine,
			'tstamp'    => current_time( 'mysql', 1 ),
			'hits'      => is_numeric( $this->hits ) ? absint( $this->hits ) : 0,
			'hash'      => absint( $this->query_hash_id ),
			'uid'       => absint( $this->uid_hash_id ),
		) );

		$wpdb->insert(
			$this->metrics->get_table_name( 'searches' ),
			$search_args,
			array(
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
			)
		);

		// Return public query hash
		return $this->query_hash;
	}
}
