<?php
namespace Libsyn\Service;

/*
	This class handles all the wp_con tasks
	
*/
class Cron extends \Libsyn\Service {
	
	protected $function_name;
	
	protected $recurrence_interval;
	
	protected $hook;
	
	protected $args;
	
	public function __construct() {
		self::setCustomSchedules();
	}
	
    /**
     * Invokes list of custom Cron Schedules
     * 
     * 
     * @return <type>
     */
	private static function setCustomSchedules() {
		//setup custom cron recurrence intervals
		if(function_exists('wp_get_schedules')) {
			$schedules = wp_get_schedules();
			if(!empty($schedules) && empty($schedules['thirty_seconds'])) {//do not duplicate
				$schedule = 'Libsyn\Service\Cron::set_schedule_every_thirty_seconds';
				add_filter( 'cron_schedules', $schedule );
			}
		}
	}
	
    /**
     * Creates a every 30 Second Cron Schedule for WP
     * 
     * @param <type> $schedules  
     * 
     * @return <type>
     */
	public static function set_schedule_every_thirty_seconds( $schedules ) {
		$schedules['thirty_seconds'] = array(
				'interval'  => 30,
				'display'   => __( 'Every Thirty Seconds', LIBSYN_TEXT_DOMAIN )
		);
		 
		return $schedules;
	}
	
	public function run() {
		return $this->activate();
	}

    /**
     * Creates WP Cron event
     * 
     * @param string $func (Function Name)
     */
	public function activate() {
		if ( !wp_next_scheduled( $this->hook ) ) {
			wp_schedule_event( $this->getFirstRun(), $this->recurrence_interval, $this->hook, $this->args );
		}
	}
	
    /**
     * Deactivates WP Cron event
     * 
     * @param string $func (Function Name)
     */
	public function deactivate() {
		wp_clear_scheduled_hook( $this->hook );
	}
	
    /**
     * Deactivates WP Cron event
	 * has to be called statically becaue WP doesn't support
	 * non-static methods to be called from Cron
     * 
     * @param string $func (Function Name)
     */
	public static function deactivateStatic($hook) {
		wp_clear_scheduled_hook( $hook );
	}
	
    /**
     * Simply Checks Php Version for Cron Usage
     * 
     * 
     * @return bool
     */
	public function checkPhpVersion() {
		$php_version = floatval(phpversion());
		if($php_version >= $this->getMinimumPhpVersion() && $php_version <= $this->getMaxPhpVersion()) {
			return true;
		}
		return false;
	}
	
    /**
     * Calculates current time plus the recurrence interval
	 * to get the first run time. (defaults to current time)
     * 
     * 
     * @return int
     */
	public function getFirstRun() {
		if(function_exists('wp_get_schedules') && !empty($this->recurrence_interval)) {
			$schedules = wp_get_schedules();
			if(!empty($schedules[$this->recurrence_interval]['interval'])) {
				return time() + intval($schedules[$this->recurrence_interval]['interval']);
			}
		}
		return time();//default to current time
	}
	
}