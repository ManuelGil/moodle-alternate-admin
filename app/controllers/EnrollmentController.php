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

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Valitron\Validator;

/**
 * EnrollmentController class
 *
 * @extends BaseController
 */
class EnrollmentController extends BaseController
{

	/**
	 * This method load the 'bulk-user-enrollment' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getBulkUserEnrollment()
	{
		// Parsing the courses.
		$courses = addslashes(
			json_encode(
				get_courses(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Parsing the users.
		$users = addslashes(
			json_encode(
				get_users_listing(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Parsing the records.
		$roles = addslashes(
			json_encode(
				role_fix_names(get_all_roles(), \context_system::instance(), ROLENAME_ORIGINAL, true),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/enrollments/bulk-user-enrollment.mustache',
			[
				'courses' => $courses,
				'users' => $users,
				'roles' => $roles
			]
		);
	}

	/**
	 * This method load the 'bulk-user-enrollment' route. <br/>
	 * <b>post: </b>access to POST method.
	 */
	public function postBulkUserEnrollment()
	{
		$validation = new Validator($_POST);

		$validation->rules([
			'required' => [
				['courses'],
				['users'],
				['role']
			]
		]);

		if (!$validation->validate()) {
			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('errors', $validation->errors());
		} else {
			// Define the count variables.
			$successes = 0;
			$failures = 0;

			// Loop through the courses.
			foreach ($_POST['courses'] as $courseid) {
				$context = \context_course::instance($courseid);

				// Loop through the users.
				foreach ($_POST['users'] as $userid) {
					if (!is_enrolled($context, $userid)) {
						try {
							enrol_try_internal_enrol($courseid, $userid, $_POST['role'], time());

							$successes++;
						} catch (\Throwable $e) {
							// Add one fault to the count.
							$failures++;
						}
					}
				}
			}

			// Add a message with the number of hits.
			if ($successes > 0) {
				$message['success'] = "<div class=\"alert alert-success\" role=\"alert\">
        							      <strong>Well done!</strong> {$successes} users were enrolled.
        							</div>";
			}

			// Add a message with the number of failures.
			if ($failures > 0) {
				$message['danger'] = "<div class=\"alert alert-danger\" role=\"alert\">
        							      <strong>Heads up!</strong> {$failures} users could not be enrolled.
        							</div>";
			}

			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('message', $message);
		}

		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}

	/**
	 * This method load the 'bulk-user-unenrollment' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getBulkUserUnenrollment()
	{
		// Parsing the courses.
		$courses = addslashes(
			json_encode(
				get_courses(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/enrollments/bulk-user-unenrollment.mustache',
			[
				'courses' => $courses
			]
		);
	}

	/**
	 * This method load the 'bulk-user-unenrollment' route. <br/>
	 * <b>post: </b>access to POST method.
	 */
	public function postBulkUserUnenrollment()
	{
		// Imports Database.
		global $DB;

		$validation = new Validator($_POST);

		$validation->rules([
			'required' => [
				['course'],
				['users']
			]
		]);

		if (!$validation->validate()) {
			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('errors', $validation->errors());
		} else {
			// The users has been show in a table component.
			$result = "<div class=\"table-responsive\">
                        <table id=\"table\" class=\"table table-striped table-hover table-condensed\">
                            <thead>
                                <tr>
									<th>user id</th>
                                    <th>plugin</th>
                                </tr>
                            </thead>
							<tbody>";

			if (isset($_POST['course']) && isset($_POST['users'])) {
				// Get intances of enrol table.
				$instances = $DB->get_records('enrol', array('courseid' => $_POST['course']));

				foreach ($_POST['users'] as $userid) {
					foreach ($instances as $instance) {
						$plugin = enrol_get_plugin($instance->enrol);
						$plugin->unenrol_user($instance, $userid);

						// Add the new user into the table.
						$result .= "<tr>
										<td>{$userid}</td>
										<td>" . get_class($plugin) . "</td>
									</tr>";
					}
				}
			}

			$result .= "</tbody></table></div>";

			// Add the result.
			$message['info'] = "<div class=\"alert alert-info\" role=\"alert\">
									  <strong>Oh snap!</strong> The following users were unrolleds:<br><br>
									  {$result}
        						</div>";

			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('message', $message);
		}

		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}

	/**
	 * This method load the 'dynamic-unenrollment' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getDynamicUnenrollment()
	{
		// Parsing the courses.
		$courses = addslashes(
			json_encode(
				get_courses(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Parsing the users.
		$users = addslashes(
			json_encode(
				get_users_listing(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);


		// Render template.
		return $this->render(
			'/enrollments/dynamic-unenrollment.mustache',
			[
				'courses' => $courses,
				'users' => $users
			]
		);
	}

	/**
	 * This method load the 'dynamic-unenrollment' route. <br/>
	 * <b>post: </b>access to POST method.
	 */
	public function postDynamicUnenrollment()
	{
		// Imports Database.
		global $DB;

		$validation = new Validator($_POST);

		$validation->rules([
			'required' => [
				['search']
			],
			'alpha' => [
				['search']
			]
		]);

		if (!$validation->validate()) {
			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('errors', $validation->errors());
		} else {
			// The users has been show in a table component.
			$result = "<div class=\"table-responsive\">
                        <table id=\"table\" class=\"table table-striped table-hover table-condensed\">
                            <thead>
                                <tr>
									<th>user id</th>
                                    <th>plugin</th>
                                </tr>
                            </thead>
							<tbody>";

			if ($_POST['search'] == 'course') {
				if (isset($_POST['course']) && isset($_POST['users'])) {
					// Get intances of enrol table.
					$instances = $DB->get_records('enrol', array('courseid' => $_POST['course']));

					foreach ($_POST['users'] as $userid) {
						foreach ($instances as $instance) {
							$plugin = enrol_get_plugin($instance->enrol);
							$plugin->unenrol_user($instance, $userid);

							// Add the new user into the table.
							$result .= "<tr>
											<td>{$userid}</td>
											<td>" . get_class($plugin) . "</td>
										</tr>";
						}
					}
				}
			}

			if ($_POST['search'] == 'user') {
				if (isset($_POST['courses']) && isset($_POST['user'])) {
					foreach ($_POST['courses'] as $courseid) {
						// Get intances of enrol table.
						$instances = $DB->get_records('enrol', array('courseid' => $courseid));

						foreach ($instances as $instance) {
							$plugin = enrol_get_plugin($instance->enrol);
							$plugin->unenrol_user($instance, $_POST['user']);

							// Add the new user into the table.
							$result .= "<tr>
											<td>{$_POST['user']}</td>
											<td>" . get_class($plugin) . "</td>
										</tr>";
						}
					}
				}
			}

			$result .= "</tbody></table></div>";

			// Add the result.
			$message['info'] = "<div class=\"alert alert-info\" role=\"alert\">
									  <strong>Oh snap!</strong> The following users were unrolleds:<br><br>
									  {$result}
        						</div>";

			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('message', $message);
		}

		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}

	/**
	 * This method load the 'list-assignments' route. <br/>
	 * <b>post: </b>access to GET method. <br/>
	 * <b>post: </b>AJAX request.
	 *
	 * @param string|null $courseid - the course id
	 */
	public function getListAssignments(?string $courseid = '')
	{
		// Imports Database.
		global $DB;

		// Gets roles.
		$sql = "SELECT      {role_assignments}.id,
							{user}.username,
							{user}.email,
							{user}.firstname,
							{user}.lastname,
							{role}.shortname AS role
				FROM		{role_assignments}
				JOIN		{context}
					ON		{role_assignments}.contextid = {context}.id
					AND		{context}.contextlevel = 50
				JOIN 		{role}
					ON 		{role_assignments}.roleid = {role}.id
				JOIN		{user}
					ON		{user}.id = {role_assignments}.userid
				JOIN		{course}
					ON		{context}.instanceid = {course}.id
                WHERE       {course}.id = :courseid";

		// Create a log channel.
		$log = new Logger('App');
		$log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/error.log', Logger::ERROR));

		try {
			header_remove();
			http_response_code(200);
			header('HTTP/1.1 200 OK');
			header('Content-Type: application/json');

			// Execute and parse the query.
			return json_encode(
				$DB->get_records_sql(
					$sql,
					[
						'courseid' => (float) $courseid
					]
				)
			);
		} catch (\Throwable $e) {
			// When an error occurred.
			if (DEBUG) {
				header_remove();
				http_response_code(404);
				header('HTTP/1.1 404 Not Found');
				echo '<pre>' . $e->getTraceAsString() . '</pre>';
				echo PHP_EOL;
				echo $e->getMessage();
			} else {
				$log->error($e->getMessage(), $e->getTrace());
				header_remove();
				http_response_code(500);
				header('HTTP/1.1 500 Internal Server Error');
			}
			exit;
		}
	}

	/**
	 * This method load the 'switch-role' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getSwitchRole()
	{
		// Parsing the courses.
		$courses = addslashes(
			json_encode(
				get_courses(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Parsing the records.
		$roles = addslashes(
			json_encode(
				role_fix_names(get_all_roles(), \context_system::instance(), ROLENAME_ORIGINAL, true),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/enrollments/switch-role.mustache',
			[
				'courses' => $courses,
				'roles' => $roles
			]
		);
	}

	/**
	 * This method load the 'switch-role' route. <br/>
	 * <b>post: </b>access to POST method.
	 */
	public function postSwitchRole()
	{
		// Imports Database.
		global $DB;

		$validation = new Validator($_POST);

		$validation->rules([
			'required' => [
				['course'],
				['users'],
				['role']
			]
		]);

		if (!$validation->validate()) {
			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('errors', $validation->errors());
		} else {
			// Define the count variables.
			$successes = 0;
			$failures = 0;

			// Loop through the users.
			foreach ($_POST['users'] as $assignmentid) {
				try {
					$DB->set_field('role_assignments', 'roleid', $_POST['role'], ['id' => $assignmentid]);

					// Add one user to the count.
					$successes++;
				} catch (\Throwable $e) {
					// Add one fault to the count.
					$failures++;
				}
			}

			// Add a message with the number of hits.
			if ($successes > 0) {
				$message['success'] = "<div class=\"alert alert-success\" role=\"alert\">
        							      <strong>Well done!</strong> {$successes} users were updated.
        							</div>";
			}

			// Add a message with the number of failures.
			if ($failures > 0) {
				$message['danger'] = "<div class=\"alert alert-danger\" role=\"alert\">
        							      <strong>Heads up!</strong> {$failures} users could not be updated.
        							</div>";
			}

			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('message', $message);
		}

		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}
}
