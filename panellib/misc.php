<?php
/* vim: ts=4
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2024 The Cacti Group, Inc.                           |
 | Copyright (C) 2004-2024 Petr Macek                                      |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | https://github.com/xmacan/                                              |
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

function register_misc() {
	global $registry;

	$registry['misc'] = array(
		'name'        => __('Miscellaneous Panels', 'intropage'),
		'description' => __('Panels that general non-categorized data about Cacti\'s.', 'intropage')
	);

	$panels = array(
		'ntp_dns' => array(
			'name'         => __('NTP/DNS Status', 'intropage'),
			'description'  => __('Checking your Cacti system clock for drift from a known baseline and DNS resolving check', 'intropage'),
			'class'        => 'misc',
			'level'        => PANEL_SYSTEM,
			'refresh'      => 7200,
			'trefresh'     => false,
			'force'        => true,
			'width'        => 'quarter-panel',
			'priority'     => 30,
			'alarm'        => 'green',
			'requires'     => false,
			'update_func'  => 'ntp_dns',
			'details_func' => false,
			'trends_func'  => false
		),
		'maint' => array(
			'name'         => __('Maint Plugin Details', 'intropage'),
			'description'  => __('Maint Plugin details on upcoming schedules', 'intropage'),
			'class'        => 'misc',
			'level'        => PANEL_USER,
			'refresh'      => 300,
			'trefresh'     => false,
			'force'        => true,
			'width'        => 'quarter-panel',
			'priority'     => 98,
			'alarm'        => 'red',
			'requires'     => 'maint',
			'update_func'  => 'maint',
			'details_func' => false,
			'trends_func'  => false
		),
		'webseer' => array(
			'name'         => __('Webseer Details', 'intropage'),
			'description'  => __('Plugin webseer URL Service Check Details', 'intropage'),
			'class'        => 'misc',
			'level'        => PANEL_SYSTEM,
			'refresh'      => 60,
			'trefresh'     => false,
			'force'        => true,
			'width'        => 'quarter-panel',
			'priority'     => 36,
			'alarm'        => 'green',
			'requires'     => 'webseer',
			'update_func'  => 'webseer',
			'details_func' => 'webseer_detail',
			'trends_func'  => false
		),
		'servcheck' => array(
			'name'         => __('Servcheck plugin Details', 'intropage'),
			'description'  => __('Plugin ServCheck Details', 'intropage'),
			'class'        => 'misc',
			'level'        => PANEL_SYSTEM,
			'refresh'      => 60,
			'trefresh'     => false,
			'force'        => true,
			'width'        => 'quarter-panel',
			'priority'     => 36,
			'alarm'        => 'green',
			'requires'     => 'servcheck',
			'update_func'  => 'servcheck',
			'details_func' => 'servcheck_detail',
			'trends_func'  => false
		)
	);

	return $panels;
}

// -------------------------------------ntp_dns-------------------------------------------
function ntp_dns($panel, $user_id) {
	global $config;

	$ntp_server = read_config_option('intropage_ntp_server');
	$dns_host   = read_config_option('intropage_dns_host');

	$panel['data']  = '<table class="cactiTable">';
	$panel['alarm'] = 'green';

	if (empty($ntp_server)) {
		$panel['alarm'] = 'grey';
		$panel['data']  .= '<tr><td>' . __('No NTP server configured', 'intropage') . '<span class="inpa_sq color_grey"></span></td></tr>';
	} elseif (!filter_var(trim($ntp_server), FILTER_VALIDATE_IP) && !filter_var(trim($ntp_server), FILTER_VALIDATE_DOMAIN)) {
		$panel['alarm'] = 'red';
		$panel['data']  .= '<tr><td>' . __('Wrong NTP server configured - %s<br/>Please fix it in settings', $ntp_server, 'intropage') . '<span class="inpa_sq color_red"></span></td></tr>';
	} else {
		$timestamp = ntp_time($ntp_server);

		// try again
		if ($timestamp == 'error') {
			$timestamp = ntp_time($ntp_server);
		}

		if (substr($timestamp, 1, 5) != 'error') {
			$diff_time = date('U') - $timestamp;

			$panel['data'] .= '<tr><td><span class="txt_big">' . date('Y-m-d H:i:s') . ' (Time Diff: ' . $diff_time . ')</span></td></tr>';

			if ($diff_time > 1400000000) {
				$panel['alarm'] = 'red';
				$panel['data'] .= '<tr><td>' . __('Failed to get NTP time from %s', $ntp_server, 'intropage') . '<span class="inpa_sq color_red"></span></td></tr>';
			} elseif ($diff_time < -600 || $diff_time > 600) {
				$panel['alarm'] = 'red';
			} elseif ($diff_time < -120 || $diff_time > 120) {
				$panel['alarm'] = 'yellow';
			}

			if ($panel['alarm'] != 'green') {
				$panel['data'] .= '<tr><td>' . __('Please check time as it is off by more', 'intropage') . '<span class="inpa_sq color_red"></span></td></tr>';
				$panel['data'] .= '<tr><td>' . __('than %s seconds from NTP server %s.', $diff_time, $ntp_server, 'intropage') . '</td></tr>';
			} else {
				$panel['data'] .= '<tr><td>' . __('Localtime is equal to NTP server', 'intropage') . ' ' . $ntp_server . '</td></tr>';
			}
		} else {
			$panel['alarm'] = 'red';
			$panel['data']  .= '<tr><td>' . __('Unable to contact the NTP server indicated.', 'intropage') . '</td></tr>';
			$panel['data']  .= '<tr><td>' . 'Server: ' . $ntp_server . '</td></tr>';

			$panel['data']  .= '<tr><td>' . 'Timestamp: ' . $timestamp . '</td></tr>';
			$panel['data']  .= '<tr><td>' . __('Please check your configuration.', 'intropage') . '</td></tr>';
		}
	}

	$panel['data']  .= '<tr><td colspan="2"><br/><br/></td></tr>';

	if (empty($dns_host)) {
		$panel['alarm'] = 'grey';
		$panel['data']  .= '<tr><td>' . __('No DNS hostname configured', 'intropage') . '<span class="inpa_sq color_grey"></span></td></tr>';
	} elseif (!filter_var(trim($dns_host), FILTER_VALIDATE_DOMAIN)) {
		$panel['alarm'] = 'red';
		$panel['data']  .= '<tr><td>' . __('Wrong DNS hostname configured - %s<br/>Please fix it in settings', $dns_host, 'intropage') . '<span class="inpa_sq color_red"></span></td></tr>';
	} else {
		$start = microtime(true);

		$dns_response = cacti_gethostinfo($dns_host, DNS_A | DNS_CNAME | DNS_AAAA);

		$total_time = 1000*(microtime(true) - $start);

		if ($dns_response) {
			$panel['data'] .= '<tr><td>' . __('DNS hostname (%s) resolving ok.', $dns_host, 'intropage') . '</td></tr>';
			$panel['data'] .= '<tr><td>' . __('DNS resolv time: %s ms', round($total_time,2), 'intropage') . '</td></tr>';
		} else {
			$panel['alarm'] = 'red';
			$panel['data']  .= '<tr><td>' . __('DNS hostname (%s) resolving failed.', $dns_host, 'intropage') . '<span class="inpa_sq color_red"></span></td></tr>';
			$panel['data']  .= '<tr><td>' . __('Please check your configuration.', 'intropage') . '</td></tr>';
		}
	}

	$panel['data'] .= '</table>';

	save_panel_result($panel, $user_id);
}

//---------------------------maint plugin--------------------
function maint($panel, $user_id) {
	global $config;

	$panel['alarm'] = 'green';

	$maint_days_before = read_config_option('intropage_maint_plugin_days_before');

	if (api_plugin_is_enabled('maint') && $maint_days_before >= 0) {

		$simple_perms = get_simple_device_perms($user_id);

		if (!$simple_perms) {
			$allowed_devices = intropage_get_allowed_devices($_SESSION['sess_user_id']);
			$host_cond = 'IN (' . $allowed_devices . ')';
		} else {
			$allowed_devices = false;
			$q_host_cond = '';
		}

		if (!$simple_perms) {
			$q_host_cond = 'AND host.id ' . $host_cond;
		}

		if ($allowed_devices !== false || $simple_perms) {
			$schedules = db_fetch_assoc("SELECT *
				FROM plugin_maint_schedules
				WHERE enabled = 'on'");

			if (cacti_sizeof($schedules)) {
				foreach ($schedules as $sc) {
					$t = time();

					switch ($sc['mtype']) {
					case 1:
						if ($t > ($sc['stime'] - $maint_days_before) && $t < $sc['etime']) {
							$hosts = db_fetch_assoc_prepared("SELECT description FROM host
								INNER JOIN plugin_maint_hosts
								ON host.id=plugin_maint_hosts.host
								WHERE schedule = ?
								$q_host_cond",
								array($sc['id']));

							if (cacti_sizeof($hosts)) {
								$panel['data'] .= '<b>' . date('d. m . Y  H:i', $sc['stime']) .
									' - ' . date('d. m . Y  H:i', $sc['etime']) .
									' - ' . $sc['name'] . ' (One time)<br/>';

								$text = 'Affected hosts:</b> ' . implode (', ', array_column($hosts,'description'));

								$panel['data'] .= '<div class="inpa_loglines" title="' . $text . '">' . $text . '</div><br/><br/>';

							}
						}

						break;
					case 2:
						/* past, calculate next */
						if ($sc['etime'] < $t) {
							/* convert start and end to local so that hour stays same for add days across daylight saving time change */
							$starttimelocal = (new DateTime('@' . strval($sc['stime'])))->setTimezone( new DateTimeZone( date_default_timezone_get()));
							$endtimelocal   = (new DateTime('@' . strval($sc['etime'])))->setTimezone( new DateTimeZone( date_default_timezone_get()));
							$nowtime        = new DateTime();
							/* add interval days */
							$addday = new DateInterval( 'P' . strval($sc['minterval'] / 86400) . 'D');
							while ($endtimelocal < $nowtime) {
								$starttimelocal = $starttimelocal->add( $addday );
								$endtimelocal   = $endtimelocal->add( $addday );
							}

							$sc['stime'] = $starttimelocal->getTimestamp();
							$sc['etime'] = $endtimelocal->getTimestamp();
						}

						if ($t > ($sc['stime'] - $maint_days_before) && $t < $sc['etime']) {
							$hosts = db_fetch_assoc_prepared("SELECT description FROM host
								INNER JOIN plugin_maint_hosts
								ON host.id=plugin_maint_hosts.host
								WHERE schedule = ?
								$q_host_cond",
								array($sc['id']));

							if (cacti_sizeof($hosts)) {
								$panel['data'] .= '<b>' . date('d. m . Y  H:i', $sc['stime']) .
									' - ' . date('d. m . Y  H:i', $sc['etime']) .
									' - ' . $sc['name'] . ' (Reoccurring)<br/>';

								$text = 'Affected hosts:</b> ' . implode (', ', array_column($hosts,'description'));

								$panel['data'] .= '<div class="inpa_loglines" title="' . $text . '">' . $text . '</div><br/><br/>';
							}

						}

						break;
					}
				}
			}
		}
	} else {
		$panel['data'] = __('Maint plugin is not installed/enabled', 'intropage');
	}

	save_panel_result($panel, $user_id);
}

// -------------------------------------plugin webseer-------------------------------------------
function webseer($panel, $user_id) {
	global $config;

	$panel['alarm'] = 'green';

	$lines = read_user_setting('intropage_number_of_lines', read_config_option('intropage_number_of_lines'), false, $user_id);
	$important_period = read_user_setting('intropage_important_period', read_config_option('intropage_important_period'), false, $user_id);
	if ($important_period == -1) {
		$important_period = time();
	}

	if (!api_plugin_is_enabled('webseer')) {
		$panel['alarm']  = 'yellow';
		$panel['data']   = __('Plugin Webseer isn\'t installed or started', 'intropage');
		$panel['detail'] = FALSE;
	} else {
		$all  = db_fetch_cell('SELECT COUNT(*) FROM plugin_webseer_urls');
		$disa = db_fetch_cell("SELECT COUNT(*) FROM plugin_webseer_urls WHERE enabled != 'on'");
		$ok   = db_fetch_cell("SELECT COUNT(*) FROM plugin_webseer_urls WHERE enabled = 'on' AND result = 1");
		$ko   = db_fetch_cell("SELECT COUNT(*) FROM plugin_webseer_urls WHERE enabled = 'on' AND result != 1");

		if ($ko > '0') {
			$panel['alarm'] = 'red';
		}

		$panel['data']  = '<b>' . __('Webseer plugin no longer supported, use plugin servcheck instead', 'intropage') . '</b><br/>';
		$panel['data'] .= __('Number of checks (all/disabled): ', 'intropage') . $all . ' / ' . $disa . '<br/>';
		$panel['data'] .= __('Status (up/down): ', 'intropage') . $ok . ' / ' . $ko . '<br/><br/>';

		$logs = db_fetch_assoc ('SELECT pwul.lastcheck, pwul.result, pwul.http_code, pwul.error, pwu.url,
			UNIX_TIMESTAMP(pwul.lastcheck) AS secs
			FROM plugin_webseer_urls_log AS pwul
			INNER JOIN plugin_webseer_urls AS pwu
			ON pwul.url_id = pwu.id
			WHERE pwu.id = 1
			ORDER BY pwul.lastcheck DESC
			LIMIT ' . ($lines - 4));

		if (cacti_sizeof($logs) > 0) {

			$panel['data'] .= '<table class="cactiTable">';
			$panel['data'] .= '<tr><td colspan="3"><strong>' . __('Last log messages', 'intropage') . '</strong></td></tr>';
			$panel['data'] .= '<tr><td class="rpad">' . __('Date', 'intropage') . '</td>' .
				'<td class="rpad">' . __('URL', 'intropage') . '</td>' .
				'<td class="rpad">' . __('HTTP code', 'intropage') . '</td></tr>';

			foreach ($logs as $row) {
				$color = 'grey';
				$text = '';

				if ($row['http_code'] == 200) {
					if ($row['secs'] > (time()-($important_period))) {
						$color = 'green';
					}
					$text = __('OK');
				} else {
					if ($row['secs'] > (time()-($important_period))) {
						$color = 'red';
					}
					$text = __('Failed');
				}

				if ($panel['alarm'] == 'grey' && $color == 'green') {
					$panel['alarm'] = 'green';
				}

				if ($panel['alarm'] == 'green' && $color == 'yellow') {
					$panel['alarm'] = 'yellow';
				}

				if ($panel['alarm'] == 'yellow' && $color == 'red') {
					$panel['alarm'] = 'red';
				}

				$panel['data'] .= '<td class="rpad">' . $row['lastcheck'] . '</td>' .
					'<td class="rpad">' . $row['url'] . '</td>' .
					'<td class="rpad"><span class="inpa_sq color_' . $color . '"></span>' . $row['http_code'] . ' (' . $text . ')</td></tr>';
			}

			$panel['data'] .= '</table>';
		}
	}

	save_panel_result($panel, $user_id);
}

//------------------------------------ webseer_plugin -----------------------------------------------------
function webseer_detail() {
	global $config, $log;

        $important_period = read_user_setting('intropage_important_period', read_config_option('intropage_important_period'), false, $_SESSION['sess_user_id']);
        if ($important_period == -1) {
                $important_period = time();
        }

	$panel = array(
		'name'   => __('Webseer Plugin - Details', 'intropage'),
		'alarm'  => 'grey',
		'detail' => '',
	);

	$logs = db_fetch_assoc ('SELECT pwul.lastcheck, pwul.result, pwul.http_code, pwul.error, pwu.url,
		UNIX_TIMESTAMP(pwul.lastcheck) AS secs
		FROM plugin_webseer_urls_log AS pwul
		INNER JOIN plugin_webseer_urls AS pwu
		ON pwul.url_id=pwu.id
		WHERE pwu.id = 1
		ORDER BY pwul.lastcheck DESC
		LIMIT 40');

	$panel['detail'] = '<table class="cactiTable"><tr class="tableHeader">';

	$panel['detail'] .=
		'<th class="left">'  . __('Date', 'intropage')      . '</th>' .
		'<th class="left">'  . __('URL', 'intropage')       . '</th>' .
		'<th class="left">'  . __('Result', 'intropage')    . '</th>' .
		'<th class="right">' . __('HTTP code', 'intropage') . '</th>' .
		'<th class="right">' . __('Error', 'intropage')     . '</th>' .
	'</tr>';

	foreach ($logs as $log)	{
		$color = 'grey';

		$panel['detail'] .= '<tr>';
		$panel['detail'] .= '<td class="left">' . $log['lastcheck'] . '</td>';
		$panel['detail'] .= '<td class="left">' . $log['url'] . '</td>';

		if ($log['result'] == 1) {
			if ($log['secs'] > (time()-($important_period))) {
				$color = 'green';
			}
			$panel['detail'] .= '<td class="left"><span class="inpa_sq color_' . $color . '"></span>' . __('OK') . '</td>';
		} else {
			if ($log['secs'] > (time()-($important_period))) {
				$color = 'red';
			}
			$panel['detail'] .= '<td class="left"><span class="inpa_sq color_' . $color . '"></span>' . __('Failed') . '</td>';
		}

		$panel['detail'] .= '<td class="right">' . $log['http_code'] . '</td>';
		$panel['detail'] .= '<td class="right">' . $log['error'] . '</td></tr>';

		if ($color == 'red')	{
			$panel['alarm'] = 'red';
		}
	}

	$panel['detail'] .= '</table>';

	return $panel;
}


// -------------------------------------plugin servcheck-------------------------------------------
function servcheck($panel, $user_id) {
	global $config;

	$panel['alarm'] = 'green';

	$lines = read_user_setting('intropage_number_of_lines', read_config_option('intropage_number_of_lines'), false, $user_id);
	$important_period = read_user_setting('intropage_important_period', read_config_option('intropage_important_period'), false, $user_id);
	if ($important_period == -1) {
		$important_period = time();
	}

	if (!api_plugin_is_enabled('servcheck')) {
		$panel['alarm']  = 'yellow';
		$panel['data']   = __('Plugin Servcheck isn\'t installed or started', 'intropage');
		$panel['detail'] = FALSE;
	} else {
		$ok = 0; $ko = 0;
		$all  = db_fetch_cell('SELECT COUNT(*) FROM plugin_servcheck_test');
		$disa = db_fetch_cell("SELECT COUNT(*) FROM plugin_servcheck_test WHERE enabled != 'on'");

		$tests = db_fetch_assoc('SELECT display_name, type, id, lastcheck FROM plugin_servcheck_test');
			
		foreach ($tests as $test) {
			$state = db_fetch_cell_prepared('SELECT result FROM plugin_servcheck_log
				WHERE test_id = ? ORDER BY lastcheck DESC LIMIT 1',
				array($test['id']));
				
			if ($state == 'ok') {
				$ok++;
			} else {
				$ko++;
			}
		}

		if ($ko > '0') {
			$panel['alarm'] = 'red';
		}

		$panel['data'] .= __('Number of checks (all/disabled): ', 'intropage') . $all . ' / ' . $disa . '<br/>';
		$panel['data'] .= __('Status (ok/error): ', 'intropage') . $ok . ' / ' . $ko . '<br/><br/>';
		$logs = db_fetch_assoc ('SELECT psl.lastcheck as `lastcheck`, result, error, display_name, type,
			UNIX_TIMESTAMP(psl.lastcheck) AS secs
			FROM plugin_servcheck_log AS psl
			LEFT JOIN plugin_servcheck_test AS pst
			ON psl.test_id = pst.id 
			LIMIT ' . ($lines - 4));

		if (cacti_sizeof($logs) > 0) {

			$panel['data'] .= '<table class="cactiTable">';
			$panel['data'] .= '<tr><td colspan="4"><strong>' . __('Last log records', 'intropage') . '</strong></td></tr>';
			$panel['data'] .= '<tr><td class="rpad">' . __('Date', 'intropage') . '</td>' .
				'<td class="rpad">' . __('Test', 'intropage') . '</td>' .
				'<td class="rpad">' . __('Type', 'intropage') . '</td>' .
				'<td class="rpad">' . __('Result', 'intropage') . '</td></tr>';

			foreach ($logs as $row) {
				$color = 'grey';
				$text = '';

				if ($row['result'] == 'ok') {
					if ($row['secs'] > (time()-($important_period))) {
						$color = 'green';
					}
					$text = __('OK');
				} else {
					if ($row['secs'] > (time()-($important_period))) {
						$color = 'red';
					}
					$text = __('Failed');
				}

				if ($panel['alarm'] == 'grey' && $color == 'green') {
					$panel['alarm'] = 'green';
				}

				if ($panel['alarm'] == 'green' && $color == 'yellow') {
					$panel['alarm'] = 'yellow';
				}

				if ($panel['alarm'] == 'yellow' && $color == 'red') {
					$panel['alarm'] = 'red';
				}

				$panel['data'] .= '<td class="rpad">' . $row['lastcheck'] . '</td>' .
					'<td class="rpad">' . $row['display_name'] . '</td>' .
					'<td class="rpad">' . $row['type'] . '</td>' .
					'<td class="rpad"><span class="inpa_sq color_' . $color . '"></span>' . $row['result'] .'</td></tr>';
			}

			$panel['data'] .= '</table>';
		}
	}

	save_panel_result($panel, $user_id);
}

//------------------------------------ servcheck_plugin_detail-------------------------------------------------
function servcheck_detail() {
	global $config, $log;

        $important_period = read_user_setting('intropage_important_period', read_config_option('intropage_important_period'), false, $_SESSION['sess_user_id']);
        if ($important_period == -1) {
                $important_period = time();
        }

	$panel = array(
		'name'   => __('Servcheck Plugin - Details', 'intropage'),
		'alarm'  => 'grey',
		'detail' => '',
	);

	$logs = db_fetch_assoc ('SELECT psl.lastcheck as `lastcheck`, result, error, display_name, type,
		UNIX_TIMESTAMP(psl.lastcheck) AS secs
		FROM plugin_servcheck_log AS psl
		LEFT JOIN plugin_servcheck_test AS pst
		ON psl.test_id = pst.id
		ORDER BY psl.lastcheck DESC
		LIMIT 40');

	$panel['detail'] = '<table class="cactiTable"><tr class="tableHeader">';

	$panel['detail'] .=
		'<th class="left">'  . __('Date', 'intropage') . '</th>' .
		'<th class="left">'  . __('Test', 'intropage') . '</th>' .
		'<th class="left">'  . __('Type', 'intropage') . '</th>' .
		'<th class="right">' . __('Result', 'intropage') . '</th>' .
		'<th class="right">' . __('Error', 'intropage') . '</th>' .
	'</tr>';

	foreach ($logs as $log)	{
		$color = 'grey';

		$panel['detail'] .= '<tr>';
		$panel['detail'] .= '<td class="left">' . $log['lastcheck'] . '</td>';
		$panel['detail'] .= '<td class="left">' . $log['display_name'] . '</td>';
		$panel['detail'] .= '<td class="left">' . $log['type'] . '</td>';

		if ($log['result'] == 'ok') {
			if ($log['secs'] > (time()-($important_period))) {
				$color = 'green';
			}

			$panel['detail'] .= '<td class="left"><span class="inpa_sq color_' . $color . '"></span>' . __('OK') . '</td>';
		} else {
			if ($log['secs'] > (time()-($important_period))) {
				$color = 'red';
			}
			$panel['detail'] .= '<td class="left"><span class="inpa_sq color_' . $color . '"></span>' . __('Failed') . '</td>';
		}

		$panel['detail'] .= '<td class="right">' . $log['result'] . '</td>';
		$panel['detail'] .= '<td class="right">' . $log['error'] . '</td></tr>';

		if ($color == 'red')	{
			$panel['alarm'] = 'red';
		}
	}

	$panel['detail'] .= '</table>';

	return $panel;
}

