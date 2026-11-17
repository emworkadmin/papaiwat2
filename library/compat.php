<?php
/**
 * Runtime compatibility shims.
 *
 * The legacy production host had the PECL geoip extension installed. The
 * container image does not ship it (the legacy GeoIP database it depends on is
 * discontinued), so provide no-op fallbacks to keep page-view logging working.
 */

if (!function_exists('geoip_country_code_by_name')) {
	function geoip_country_code_by_name($hostname)
	{
		return null;
	}
}

if (!function_exists('geoip_country_name_by_name')) {
	function geoip_country_name_by_name($hostname)
	{
		return null;
	}
}
