<?php

class WPS_Object_Cache {

	public function __construct(){}

	/**
	 * Set cache, redundant with WP_Object_Cache::add
	 * @param $key
	 * @param $data
	 * @param string $group
	 * @param float|int $expire
	 * @return bool
	 */
	public static function set($key, $data, $group='app', $expire=60*60*12){

		if( !is_string($key) )
			$key = json_encode($key);

		return wp_cache_add($key, $data, $group, $expire);
	}


	/**
	 * Get cache, redundant with WP_Object_Cache::add
	 * @param $key
	 * @param string $group
	 * @return bool
	 */
	public static function get($key, $group='app'){

		if( !is_string($key) )
			$key = json_encode($key);

		return wp_cache_get($key, $group);
	}


	/**
	 * Delete cache, redundant with WP_Object_Cache::add
	 * @param $key
	 * @param string $group
	 * @return bool
	 */
	public static function delete($key, $group='app'){

		if( !is_string($key) )
			$key = json_encode($key);

		return wp_cache_delete($key, $group);
	}
}
