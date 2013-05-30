<?php

/**
 * iGeek Transient class (light version)
 *
 * It leverages WordPress Transient API to cache data and automatically
 * updates cache on expiry.
 *
 * @author Amit Gupta http://igeek.info/
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 *
 * @since 2013-04-06
 *
 * @version 2013-04-07
 * @version 2013-04-20
 * @version 2013-05-27
 * @version 2013-05-30
 */

class iGeek_Transient {

	protected $_key;	//A unique key for transient as md5 hash
	protected $_callback;	//callback which generates data to cache
	protected $_callback_args = array();	//array of arguments that callback accepts
	protected $_life = 600;	//life of cache in seconds - 10 minutes default
	protected $_do_cache = true;	//assume we want to cache data
	protected $_max_lock_duration = 100;	//max duration (in seconds) a lock can hold a key

	const key_prefix = 'ig_trnst_';	//unique prefix for keys

	public function __construct( $key ) {
		if( empty( $key ) || ! is_string( $key ) ) {
			//we have a problem, a key is needed, throw an exception
			throw new ErrorException( "Class iGeek_Transient initialized without a valid key", 0, 0 );
			return;	//return empty, class not initialized
		}

		$this->_key = self::key_prefix . md5( $key );

		//call init
		$this->_init();
	}

	/**
	 * class init stuff, runs when a new object is created
	 */
	protected function _init() {
		//allow override of max lock duration
		$max_lock_duration = intval( apply_filters( 'igeek_transient_max_lock_duration', $this->_max_lock_duration ) );

		if( $max_lock_duration > 0 ) {
			$this->_max_lock_duration = $max_lock_duration;
		}

		unset( $max_lock_duration );
	}

	/**
	 * This function accepts the number of seconds the cache is to live.
	 * If cache life is zero then data is not to be cached.
	 */
	public function expires_in( $seconds = 0 ) {
		$seconds = intval( $seconds );
		$this->_life = ( $seconds < 0 ) ? 0 : $seconds;

		if( $this->_life > 0 ) {
			$this->_do_cache = true;
		} else {
			$this->_do_cache = false;

			//delete any existing cache as well
			$this->delete();
		}

		return $this;
	}

	/**
	 * This function accepts the callback and the arguments to be passed to it
	 * to get the data which goes in cache
	 */
	public function updates_using( $callback, $args = array() ) {
		if( empty( $callback ) || ! is_callable( $callback ) ) {
			//uncallable callback, throw exception
			throw new ErrorException( "Un-callable callback specified", 0, 0 );
			return $this;
		}

		$args = (array) $args;
		$this->_callback = $callback;
		$this->_callback_args = $args;

		return $this;
	}

	/**
	 * This function deletes the cache in transient
	 */
	public function delete() {
		delete_transient( $this->_key );

		return $this;
	}

	/**
	 * This function gets the data from transient and returns it.
	 */
	public function get() {
		$data = get_transient( $this->_key );	//get data from transient

		if( $data !== false ) {
			//we have data
			return $data;
		}

		//fetch & return
		return $this->_fetch_data();
	}

	/**
	 * This function makes the callback and gets the data from user function,
	 * saves it in transient & returns it as well
	 */
	protected function _fetch_data() {
		if( empty( $this->_callback ) || ! is_callable( $this->_callback ) ) {
			//no callback, can't fetch anything, bail out
			return false;
		}

		if( $this->_is_locked() && ! $this->_release_stale_lock() ) {
			//another process is fetching data, bail out
			return;
		}

		//lock this key to avoid race conditions
		$this->_set_lock();

		try {
			$data = call_user_func_array( $this->_callback, $this->_callback_args );

			if( $this->_do_cache === true ) {
				//cache the data
				set_transient( $this->_key, $data, $this->_life );
			}
		} catch( Exception $e ) {
			$data = false;
		}

		//release lock
		$this->_release_lock();

		return $data;
	}

	/**
	 * This function checks whether there is a lock on the key or not. If the key
	 * is locked for update it returns TRUE else FALSE
	 */
	protected function _is_locked() {
		if( false === $this->_get_lock() ) {
			return false;	//no lock yet
		}

		return true;	//lock in place
	}

	/**
	 * This functions gets lock time of the key or returns FALSE
	 * if no lock is found
	 */
	protected function _get_lock() {
		return get_transient( 'lock_' . $this->_key );
	}

	/**
	 * This functions sets a lock on the key
	 */
	protected function _set_lock() {
		set_transient( 'lock_' . $this->_key , time() );
	}

	/**
	 * This function removes the lock from the key
	 */
	protected function _release_lock() {
		delete_transient( 'lock_' . $this->_key );
	}

	/**
	 * This function removes the lock from the key if its gone stale
	 * and returns TRUE else FALSE
	 */
	protected function _release_stale_lock() {
		if( ! $this->_is_locked() ) {
			//current key not locked, all good and dandy
			return true;
		}

		if( intval( time() - intval( $this->_get_lock() ) ) >= $this->_max_lock_duration ) {
			//lock is now stale, release it
			$this->_release_lock();
			return true;
		}

		return false;
	}

//end of class
}

//EOF
