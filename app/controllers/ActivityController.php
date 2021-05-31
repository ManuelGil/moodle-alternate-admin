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

/**
 * ResourceController class
 *
 * @extends BaseController
 */
class ActivityController extends BaseController
{

	/**
	 * This method redirect to BASE URL when access to parent section. <br/>
	 * <b>post: </b>access to any method (POST, GET, DELETE, OPTIONS, HEAD etc...).
	 */
	public function anyIndex()
	{
		redirect(BASE_URL);
	}

	/**
	 * This method load the 'count-activities' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getCountActivities()
	{
		// Imports Database.
		global $DB;

		// SQL Query for count users.
		$sql = "SELECT		{course}.id,
							{course}.fullname,
				        	{modules}.name,
				        	COUNT({modules}.id) AS activities
				FROM		{course_modules}
				JOIN		{course}
					ON		{course_modules}.course = {course}.id
				JOIN		{modules}
					ON		{course_modules}.module = {modules}.id
				WHERE		{course_modules}.visible = 1
				GROUP BY	{course}.id, {modules}.name";

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
			'/activities/count-activities.mustache',
			[
				'items' => $items
			]
		);
	}

	/**
	 * This method load the 'list-module' route. <br/>
	 * <b>post: </b>access to GET method. <br/>
	 * <b>post: </b>AJAX request.
	 *
	 * @param string $module - the module name
	 * @param string|null $courseid - the course id
	 */
	public function getListModule(string $module, ?string $courseid = '')
	{
		// Imports Database.
		global $DB;

		if ($DB->record_exists('modules', ['name' => $module])) {
			$table = "{{$module}}";

			$sql = "SELECT		$table.id,
									$table.name,
									$table.intro
						FROM 		$table
						JOIN 		{course_modules}
							ON		$table.id = {course_modules}.instance
							AND		{course_modules}.visible = 1
						JOIN		{modules}
							ON		{course_modules}.module = {modules}.id
							AND		{modules}.name = :module
						WHERE		$table.course = :courseid";

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
							'module' => $module,
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
		} else {
			header_remove();
			http_response_code(404);
			header('HTTP/1.1 404 Not Found');
			echo "	<head>
						<link rel=\"stylesheet\" href=\"" . BASE_URL . "assets/css/slim.min.css\">
						<script src=\"" . BASE_URL . "assets/js/slim.min.js\"></script>
					</head>
					<body>
						<div class=\"page-error-wrapper\">
							<div>
								<h1 class=\"error-title\">404</h1>
								<h5 class=\"tx-sm-24 tx-normal\">Oopps. The page you were looking for doesn't exist.</h5>
								<p class=\"mg-b-50\">You may have mistyped the address or the page may have moved.</p>
								<p class=\"mg-b-50\"><a href=\"" . BASE_URL . "\" class=\"btn btn-error\">Back to Home</a></p>
							</div>
						</div>
					</body>";
		}
	}

	/**
	 * This method load the 'list-activities' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getListActivities()
	{
		// Imports Database.
		global $DB;

		// Parsing the courses.
		$courses = addslashes(
			json_encode(
				get_courses(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		$sql = "SELECT DISTINCT		name
				FROM				{modules}
				WHERE				visible = 1;";

		// Execute the query.
		$records = $DB->get_records_sql($sql);

		// Parsing the records.
		$modules = addslashes(
			json_encode(
				$records,
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/activities/list-activities.mustache',
			[
				'courses' => $courses,
				'modules' => $modules
			]
		);
	}
}
