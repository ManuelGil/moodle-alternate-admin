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

namespace App\Controllers;

/**
 * ReportController class
 *
 * @extends BaseController
 */
class ReportController extends BaseController
{

	/**
	 * This method load the 'list-admins' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getListAdmins()
	{
		// Parsing the admin users.
		$admins = addslashes(
			json_encode(
				get_admins(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/reports/list-admins.mustache',
			[
				'admins' => $admins
			]
		);
	}

	/**
	 * This method load the 'users-created' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getUsersCreated()
	{
		// Imports Database.
		global $DB;

		// SQL Query for count users.
		$sql = "SELECT		YEAR(FROM_UNIXTIME(firstaccess)) AS years,
    						COUNT(*) AS users
				FROM		{user}
				GROUP BY 	years;";

		// Execute the query.
		$records = $DB->get_records_sql($sql);

		// Parsing the records.
		$items = addslashes(
			json_encode(
				$records,
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/reports/users-created.mustache',
			[
				'items' => $items
			]
		);
	}

	/**
	 * This method load the 'logged-once' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getLoggedOnce()
	{
		// Imports Database.
		global $DB;

		// SQL Query for count users.
		$sql = "SELECT		id,
        					username,
        					email,
        					firstname,
        					lastname
				FROM		{user}
				WHERE   	deleted = 0
    				AND 	lastlogin = 0
    				AND 	lastaccess > 0;";

		// Execute the query.
		$records = $DB->get_records_sql($sql);

		// Parsing the records.
		$items = addslashes(
			json_encode(
				$records,
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/reports/logged-once.mustache',
			[
				'items' => $items
			]
		);
	}

	/**
	 * This method load the 'logged-last-days' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getLoggedLastDays()
	{
		// Imports Database.
		global $DB;

		// SQL Query for count users.
		$sql = "SELECT		id,
        					username,
        					email,
        					firstname,
        					lastname,
							lastlogin
				FROM		{user}
				WHERE   	DATEDIFF(NOW(), FROM_UNIXTIME(lastlogin)) < 120;";

		// Execute the query.
		$records = $DB->get_records_sql($sql);

		// Parsing the records.
		$items = addslashes(
			json_encode(
				$records,
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/reports/logged-last-days.mustache',
			[
				'items' => $items
			]
		);
	}

	/**
	 * This method load the 'list-suspended' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getListSuspended()
	{
		// Imports Database.
		global $DB;

		// SQL Query for count users.
		$sql = "SELECT      id,
							username,
							email,
							firstname,
							lastname
				FROM		{user}
				WHERE       suspended = 1;";

		// Execute the query.
		$records = $DB->get_records_sql($sql);

		// Parsing the users.
		$users = addslashes(
			json_encode(
				$records,
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/reports/list-suspended.mustache',
			[
				'users' => $users
			]
		);
	}
}
