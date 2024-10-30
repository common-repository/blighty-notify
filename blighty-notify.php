<?php
/**
 * Plugin Name: Blighty Notify
 * Plugin URI: http://blighty.net/wordpress-blighty-notify-plugin/
 * Description: Send an email to the blog admin when a page is requested.
 * (C) 2015-2017 Chris Murfin (Blighty)
 * Version: 1.4.0
 * Author: Blighty
 * Author URI: http://blighty.net
 * License: GPLv3 or later
 **/

/**

Copyright (C) 2015-2017 Chris Murfin

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

**/

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

define('BNO_PLUGIN_NAME', 'Blighty Notify');
define('BNO_PLUGIN_VERSION', '1.4.0');

define('BNO_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)));


if ( is_admin() ){ // admin actions
	require_once(BNO_PLUGIN_DIR .'/admin-settings.php');
	add_action( 'admin_menu', 'bno_setup_menu' );
	add_action( 'admin_init', 'bno_init' );
}

add_shortcode( 'bno_notify', 'bno_notify' );

function bno_notify( $atts ) {

	if (get_option('bno_debug') == 1) {
		$debug = true;
	} else {
		$debug = false;
	}

	if ($debug) {
		echo 'bno_notify: shortcode found<br />';
	}

	if (get_option('bno_exclude_robots') == 1) {
		if ($debug) {
			echo 'bno_notify: excluding robots<br />';
		}
		if (strpos($_SERVER['HTTP_USER_AGENT'],'http') > 0) {
			if ($debug) {
				echo 'bno_notify: robot detected<br />';
			}
			return null;
		} else {
			if ($debug) {
				echo 'bno_notify: robot not detected<br />';
			}
		}
	}

	if ($debug) {
		echo 'bno_notify: preparing email<br />';
	}

	$emailAddress = get_option('bno_email');

  if (empty($emailAddress)) {
		if ($debug) {
			echo 'bno_notify: overriding wordpress admin email<br />';
		}
    $emailAddress = get_bloginfo('admin_email');
  }
  
    $location = bno_getlocation($_SERVER['REMOTE_ADDR']);
    $referer = (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'n/a');

	$headers = 'From: ' .get_bloginfo('name') .' <' .get_bloginfo('admin_email') .'>' . "\r\n";
	$subj = '[' .get_bloginfo('name') .'] Page requested';
	$body = 'A page on ' .get_bloginfo('name') .' has been requested...' ."\r\n\r\n" .
					'Page: ' .$_SERVER['REQUEST_URI'] ."\r\n\r\n" .
					'Referer: ' .$referer ."\r\n\r\n" .
					'IP: ' .$_SERVER['REMOTE_ADDR'] ."\r\n\r\n" .
					'Reverse Lookup: http://ip-api.com/#' .$_SERVER['REMOTE_ADDR'] . "\r\n\r\n" .
					'City: ' .$location->city ."\r\n" .
					'Postcode: ' .$location->zip ."\r\n" .
					'Country: ' .$location->regionName ."\r\n" .
					'ISP: ' .$location->isp ."\r\n\r\n" .
					'- (Location may not be accurate.)' ."\r\n\r\n" .
					'User agent: ' .$_SERVER['HTTP_USER_AGENT'] ."\r\n\r\n";

	if ($debug) {
		echo 'bno_notify: sending email<br />';
	}

	wp_mail( $emailAddress, $subj, $body, $headers );

	if ($debug) {
		echo 'bno_notify: email sent<br />';
	}

}

function bno_getlocation($ip) {
    $service_url = 'http://ip-api.com/json/' .$ip;
    $curl = curl_init($service_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $curl_response = curl_exec($curl);
    if ($curl_response === false) {
        $info = curl_getinfo($curl);
        curl_close($curl);
        return null;
        //die('error occured during curl exec. Additional info: ' . var_export($info));
    }
    curl_close($curl);
    $decoded = json_decode($curl_response);
    if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
        return null;
        //die('error occured: ' . $decoded->response->errormessage);
    }
    return $decoded;
}
?>
