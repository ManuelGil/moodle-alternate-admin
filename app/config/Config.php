<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * $Id$
 * <p>Title: Alternate Admin for Moodle.</p>
 * <p>Description: This wrapper for Moodle adds a new interface to
 * 					streamline your administrative tasks.</p>
 *
 * @package		wrapper
 * @author 		$Author: 2021 Manuel Gil. <https://imgil.dev/> $
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 */

try {
	// Start dotEnv instance.
	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
	$dotenv->load();

	// Sets debug mode.
	define('DEBUG', $_ENV['MODE_DEBUG'] === 'true');

	// If you want to run phpunit, uncomment this
	// define('CLI_SCRIPT', true);

	// Gets Moodle Config.
	require_once($_ENV['MDL_CONFIG']);

	// Imports Config.
	global $CFG;

	// Sets database config.
	define('DB_HOST', $CFG->dbhost);
	define('DB_USER', $CFG->dbuser);
	define('DB_PASS', $CFG->dbpass);
	define('DB_NAME', $CFG->dbname);
} catch (\Throwable $e) {
	header_remove();
	http_response_code(500);
	header('HTTP/1.1 500 Not Found');
	echo '<pre>' . $e->getTraceAsString() . '</pre>';
	echo PHP_EOL;
	echo $e->getMessage();
}
