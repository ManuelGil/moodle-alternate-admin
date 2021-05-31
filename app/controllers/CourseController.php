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
 * CourseController class
 *
 * @extends BaseController
 */
class CourseController extends BaseController
{

	/**
	 * This method load the 'list-courses' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getListCourses()
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
			'/courses/list-courses.mustache',
			[
				'courses' => $courses
			]
		);
	}

	/**
	 * This method load the 'count-courses' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getCountCourses()
	{
		// Imports Database.
		global $DB;

		// SQL Query for count courses by category.
		$sql = "SELECT		{course_categories}.id,
							{course_categories}.name,
							COUNT({course}.id) AS courses
				FROM		{course_categories}
				LEFT JOIN	{course}
					ON		{course_categories}.id = {course}.category
					AND		{course}.visible = 1
				GROUP BY	{course_categories}.id;";

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
			'/courses/count-courses.mustache',
			[
				'items' => $items
			]
		);
	}

	/**
	 * This method load the 'count-role' route. <br/>
	 * <b>post: </b>access to GET method. <br/>
	 * <b>post: </b>AJAX request.
	 *
	 * @param string|null $roleid - the role id
	 */
	public function getCountRole(?string $roleid = '')
	{
		// Imports Database.
		global $DB;

		// SQL Query for count role.
		$sql = "SELECT		{course}.id,
							{course}.fullname AS course,
							COUNT({course}.id) AS users
				FROM		{role_assignments}
				JOIN		{context}
					ON		{role_assignments}.contextid = {context}.id
					AND		{context}.contextlevel = 50
				JOIN		{user}
					ON		{user}.id = {role_assignments}.userid
				JOIN		{course}
					ON		{context}.instanceid = {course}.id
				WHERE		{role_assignments}.roleid = :roleid
				GROUP BY	{course}.id
				ORDER BY	users ASC;";

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
						'roleid' => (float) $roleid
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
	 * This method load the 'count-with-role' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getCountWithRole()
	{
		// Parsing the records.
		$roles = addslashes(
			json_encode(
				role_fix_names(get_all_roles(), \context_system::instance(), ROLENAME_ORIGINAL, true),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/courses/count-with-role.mustache',
			[
				'roles' => $roles
			]
		);
	}

	/**
	 * This method load the 'non-role-course' route. <br/>
	 * <b>post: </b>access to GET method. <br/>
	 * <b>post: </b>AJAX request.
	 *
	 * @param string|null $roleid - the role id
	 */
	public function getNonRoleCourse(?string $roleid = '')
	{
		// Imports Database.
		global $DB;

		// SQL Query for count role.
		$sql = "SELECT		{course}.id,
				    		{course}.fullname
				FROM 		{course}
				LEFT JOIN 	{context}
					ON 		{course}.id = {context}.instanceid
				    AND		{context}.contextlevel = 50
				LEFT JOIN	{role_assignments}
					ON		{context}.id = {role_assignments}.contextid
				    AND 	{role_assignments}.roleid = :roleid
				GROUP BY 	{course}.id
				HAVING 		COUNT({role_assignments}.id) = 0;";

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
						'roleid' => (float) $roleid
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
	 * This method load the 'course-without-role' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getCourseWithoutRole()
	{
		// Parsing the records.
		$roles = addslashes(
			json_encode(
				role_fix_names(get_all_roles(), \context_system::instance(), ROLENAME_ORIGINAL, true),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/courses/course-without-role.mustache',
			[
				'roles' => $roles
			]
		);
	}

	/**
	 * This method load the 'list-users' route. <br/>
	 * <b>post: </b>access to GET method. <br/>
	 * <b>post: </b>AJAX request.
	 *
	 * @param string|null $courseid - the course id
	 */
	public function getListUsers(?string $courseid = '')
	{
		// Imports Database.
		global $DB;

		// Gets roles.
		$sql = "SELECT      {user}.id,
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
	 * This method load the 'list-users-course' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getListUsersCourse()
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
			'/courses/list-users-course.mustache',
			[
				'courses' => $courses
			]
		);
	}

	/**
	 * This method load the 'bulk-course-creation' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getBulkCourseCreation()
	{
		// Imports Database.
		global $DB;

		// Parsing the categories.
		$categories = addslashes(
			json_encode(
				$DB->get_records('course_categories'),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/courses/bulk-course-creation.mustache',
			[
				'categories' => $categories
			]
		);
	}

	/**
	 * This method load the 'bulk-course-creation' route. <br/>
	 * <b>post: </b>access to POST method.
	 */
	public function postBulkCourseCreation()
	{
		// Imports Config.
		global $CFG;

		require_once("{$CFG->dirroot}/course/lib.php");

		$validation = new Validator($_POST);

		$validation->rules([
			'required' => [
				['category'],
				['fullname'],
				['shortname'],
				['start'],
				['count']
			],
			'optional' => [
				['separator']
			],
			'lengthMax' => [
        		['separator', 1]
			],
			'integer' => [
				['start'],
				['count']
			],
			'min' => [
				['start', 1],
				['count', 1]
			]
		]);

		if (!$validation->validate()) {
			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('errors', $validation->errors());
		} else {
			// Parsing the post params.
			$category = $_POST['category'];
			$fullname = $_POST['fullname'];
			$shortname = $_POST['shortname'];
			$separator = $_POST['separator'];
			$start = $_POST['start'];
			$count = $_POST['count'];

			// Define the count variables.
			$successes = 0;
			$failures = 0;

			// The users has been show in a table component.
			$result = "<div class=\"table-responsive\">
                        <table id=\"table\" class=\"table table-striped table-hover table-condensed\">
                            <thead>
                                <tr>
                                    <th>id</th>
                                    <th>fullname</th>
                                    <th>count</th>
                                </tr>
                            </thead>
							<tbody>";

			// Loop through the users.
			for ($i = 0; $i < $count; $i++) {
				// If username exist launch an error.
				try {
					$index = $start + $i;

					$data = new \stdClass();

					// Set name.
					$full = "{$fullname} {$index}";
					$short = "{$shortname}{$separator}{$index}";

					// Set category
					$data->category = $category;
					$data->fullname = $full;
					$data->shortname = $short;

					$course = create_course($data);

					// Add the new user into the table.
					$result .= "<tr>
									<td>{$course->id}</td>
									<td>{$course->fullname}</td>
									<td>{$course->shortname}</td>
								</tr>";

					// Add one user to the count.
					$successes++;
				} catch (\Throwable $e) {
					// Add one fault to the count.
					$failures++;
				}
			}

			// Close the table of users.
			$result .= "</tbody></table></div>";

			// Add a message with the number of hits.
			if ($successes > 0) {
				$message['success'] = "<div class=\"alert alert-success\" role=\"alert\">
        							      <strong>Well done!</strong> {$successes} courses were created.
        							</div>";
			}

			// Add a message with the number of failures.
			if ($failures > 0) {
				$message['danger'] = "<div class=\"alert alert-danger\" role=\"alert\">
        							      <strong>Heads up!</strong> {$failures} courses could not be created.
        							</div>";
			}

			// Add the result.
			$message['info'] = "<div class=\"alert alert-info\" role=\"alert\">
									  <strong>Oh snap!</strong> The following courses were created:<br><br>
									  {$result}
        						</div>";

			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('message', $message);
		}

		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}
}
