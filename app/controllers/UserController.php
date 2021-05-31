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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Valitron\Validator;

/**
 * UserController class
 *
 * @extends BaseController
 */
class UserController extends BaseController
{

	/**
	 * This method load the 'list-users' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getListUsers()
	{
		// Parsing the users.
		$users = addslashes(
			json_encode(
				get_users(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/users/list-users.mustache',
			[
				'users' => $users
			]
		);
	}

	/**
	 * This method load the 'bulk-user-creation' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getBulkUserCreation()
	{
		// Render template.
		return $this->render('/users/bulk-user-creation.mustache');
	}

	/**
	 * This method load the 'bulk-user-creation' route. <br/>
	 * <b>post: </b>access to POST method.
	 */
	public function postBulkUserCreation()
	{
		$validation = new Validator($_POST);

		$validation->rules([
			'required' => [
				['start'],
				['count']
			],
			'optional' => [
				['prefix'],
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
			$prefix = $_POST['prefix'];
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
                                    <th>username</th>
                                    <th>password</th>
                                </tr>
                            </thead>
							<tbody>";

			// Loop through the users.
			for ($i = 0; $i < $count; $i++) {
				// If username exist launch an error.
				try {
					$index = $start + $i;

					// Set an username.
					$username = "{$prefix}{$separator}{$index}";

					// Set a password.
					if (isset($_POST['unique']) && !empty($_POST['password'])) {
						$password = $_POST['password'];
					} else {
						$chars = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';
						$password = substr(str_shuffle($chars), 0, 7);
					}

					// Create a new user.
					$user = create_user_record($username, $password);

					// Add the new user into the table.
					$result .= "<tr>
									<td>{$user->id}</td>
									<td>{$username}</td>
									<td>{$password}</td>
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
        							      <strong>Well done!</strong> {$successes} users were created.
        							</div>";
			}

			// Add a message with the number of failures.
			if ($failures > 0) {
				$message['danger'] = "<div class=\"alert alert-danger\" role=\"alert\">
        							      <strong>Heads up!</strong> {$failures} users could not be created.
        							</div>";
			}

			// Add the result.
			$message['info'] = "<div class=\"alert alert-info\" role=\"alert\">
									  <strong>Oh snap!</strong> The following users were created:<br><br>
									  {$result}
        						</div>";

			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('message', $message);
		}

		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}

	/**
	 * This method load the 'list-courses' route. <br/>
	 * <b>post: </b>access to GET method. <br/>
	 * <b>post: </b>AJAX request.
	 *
	 * @param string|null $userid - the user id
	 */
	public function getListCourses(?string $userid = '')
	{
		// Create a log channel.
		$log = new Logger('App');
		$log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/error.log', Logger::ERROR));

		try {
			header_remove();
			http_response_code(200);
			header('HTTP/1.1 200 OK');
			header('Content-Type: application/json');

			// Execute and parse the query.
			return json_encode(enrol_get_users_courses((float) $userid));
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
	 * This method load the 'list-courses-user' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getListCoursesUser()
	{
		// Parsing the users.
		$users = addslashes(
			json_encode(
				get_users_listing(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/users/list-courses-user.mustache',
			[
				'users' => $users
			]
		);
	}

	/**
	 * This method load the 'user-data' route. <br/>
	 * <b>post: </b>access to GET method. <br/>
	 * <b>post: </b>AJAX request.
	 *
	 * @param string|null $userid - the user id
	 */
	public function getUserData(?string $userid = '')
	{
		// Imports Database.
		global $DB;

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
				$DB->get_record(
					'user',
					[
						'id' => (float) $userid
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
	 * This method load the 'edit-user' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getEditUser()
	{
		// Parsing the users.
		$users = addslashes(
			json_encode(
				get_users_listing(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		$authplugins = addslashes(
			json_encode(
				get_enabled_auth_plugins(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		$countries = addslashes(
			json_encode(
				get_string_manager()->get_list_of_countries(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		$choices = \core_date::get_list_of_timezones();
		$choices['99'] = get_string('serverlocaltime');

		$timezones = addslashes(
			json_encode(
				$choices,
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/users/edit-user.mustache',
			[
				'users' => $users,
				'authplugins' => $authplugins,
				'countries' => $countries,
				'timezones' => $timezones
			]
		);
	}

	/**
	 * This method load the 'edit-user' route. <br/>
	 * <b>post: </b>access to POST method.
	 */
	public function postEditUser()
	{
		// Imports Database.
		global $DB;

		$validation = new Validator($_POST);

		$validation->rules([
			'required' => [
				['userid'],
				['username'],
				['auth'],
				['firstname'],
				['lastname'],
				['email'],
				['country'],
				['timezone']
			],
			'optional' => [
				['suspended'],
				['password'],
				['forcepasschange'],
				['maildisplay'],
				['moodlenetprofile']['city']
			],
			'numeric' => [
				['userid']
			],
			'ascii' => [
				['username']
			],
			'email' => [
				['email']
			]
		]);

		if (!$validation->validate()) {
			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('errors', $validation->errors());
		} else {
			$data = new \stdClass();

			$data->id = $_POST['userid'];

			if (isset($_POST['username']) && !empty(trim($_POST['username']))) {
				$data->username = trim($_POST['username']);
			}
			if (isset($_POST['auth']) && !empty(trim($_POST['auth']))) {
				$data->auth = trim($_POST['auth']);
			}
			if (isset($_POST['password']) && !empty(trim($_POST['password']))) {
				$data->password = password_hash($_POST['password'], PASSWORD_DEFAULT, array());
			}
			if (isset($_POST['firstname']) && !empty(trim($_POST['firstname']))) {
				$data->firstname = trim($_POST['firstname']);
			}
			if (isset($_POST['lastname']) && !empty(trim($_POST['lastname']))) {
				$data->lastname = trim($_POST['lastname']);
			}
			if (isset($_POST['email']) && !empty(trim($_POST['email']))) {
				$data->email = trim($_POST['email']);
			}
			if (isset($_POST['maildisplay']) && !empty(trim($_POST['maildisplay']))) {
				$data->maildisplay = trim($_POST['maildisplay']);
			}
			if (isset($_POST['moodlenetprofile']) && !empty(trim($_POST['moodlenetprofile']))) {
				$data->moodlenetprofile = trim($_POST['moodlenetprofile']);
			}
			if (isset($_POST['city']) && !empty(trim($_POST['city']))) {
				$data->city = trim($_POST['city']);
			}
			if (isset($_POST['country']) && !empty(trim($_POST['country']))) {
				$data->country = trim($_POST['country']);
			}
			if (isset($_POST['timezone']) && !empty(trim($_POST['timezone']))) {
				$data->timezone = trim($_POST['timezone']);
			}
			if (isset($_POST['suspended'])) {
				$data->suspended = 1;
			} else {
				$data->suspended = 0;
			}

			$data->timemodified = time();

			try {
				$DB->update_record('user', $data);

				$message['success'] = "<div class=\"alert alert-success\" role=\"alert\">
        							      <strong>Well done!</strong> the user were updated.
        							</div>";
			} catch (\Throwable $e) {
				$message['danger'] = "<div class=\"alert alert-danger\" role=\"alert\">
        							      <strong>Heads up!</strong> the user could not be updated.
        							</div>";
			}

			if (isset($_POST['forcepasschange'])) {
				$data = new \stdClass();

				$data->userid = $_POST['userid'];
				$data->name = 'auth_forcepasswordchange';
				$data->value = 1;

				try {
					$transaction = $DB->start_delegated_transaction();
					$DB->insert_record('user_preferences', $data);
					$transaction->allow_commit();
				} catch (\Throwable $e) {
					// Make sure transaction is valid.
					if (!empty($transaction) && !$transaction->is_disposed()) {
						$transaction->rollback($e);
					}
				}
			}

			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('message', $message);
		}

		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}

	/**
	 * This method load the 'upload-users' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getUploadUsers()
	{
		// Render template.
		return $this->render('/users/upload-users.mustache');
	}

	/**
	 * This method load the 'upload-users' route. <br/>
	 * <b>post: </b>access to POST method.
	 */
	public function postUploadUsers()
	{
		// Imports Database.
		global $DB;

		$validation = new Validator($_POST);

		$validation->rules([
			'required' => [
				['delimiter']
			],
			'optional' => [
				['unique'],
				['forcepasschange']
			]
		]);

		if (!$validation->validate()) {
			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('errors', $validation->errors());
		} else {
			// Define the count variables.
			$successes = 0;
			$failures = 0;

			$result = "<div class=\"table-responsive\">
                        <table id=\"table\" class=\"table table-striped table-hover table-condensed\">
                            <thead>
                                <tr>
                                    <th>id</th>
                                    <th>username</th>
                                    <th>password</th>
                                </tr>
                            </thead>
                            <tbody>";

			$fileName = $_FILES['file']['tmp_name'];

			if ($_FILES['file']['size'] > 0) {
				$file = fopen($fileName, 'r');

				while (($row = fgetcsv($file, 0, $_POST['delimiter'])) !== FALSE) {

					// If username exist launch an error.
					try {
						$data = new \stdClass();

						// Set an username.
						$data->username = trim($row[0]);
						// Set the firstname.
						$data->firstname = trim($row[1]);
						// Set the lastname.
						$data->lastname = trim($row[2]);
						// Set the email.
						$data->email = trim($row[3]);

						// Set the auth.
						if (isset($row[4])) {
							$data->auth = trim($row[4]);
						} else {
							$data->auth = 'manual';
						}

						// Set a password.
						if (isset($_POST['unique']) && !empty($_POST['password'])) {
							$password = $_POST['password'];
						} else {
							if (isset($row[5])) {
								$password = $row[5];
							} else {
								$chars = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';
								$password = substr(str_shuffle($chars), 0, 7);
							}
						}

						$data->password = password_hash($password, PASSWORD_DEFAULT, array());

						$data->mnethostid = 1;

						// Create a new user.
						$userid = $DB->insert_record('user', $data, true, true);

						// Add the new user into the table.
						$result .= "<tr>
									<td>{$userid}</td>
									<td>{$row[0]}</td>
									<td>{$password}</td>
								</tr>";

						if (isset($_POST['forcepasschange'])) {
							$data = new \stdClass();

							$data->userid = $userid;
							$data->name = 'auth_forcepasswordchange';
							$data->value = 1;

							try {
								$transaction = $DB->start_delegated_transaction();
								$DB->insert_record('user_preferences', $data);
								$transaction->allow_commit();
							} catch (\Throwable $e) {
								// Make sure transaction is valid.
								if (!empty($transaction) && !$transaction->is_disposed()) {
									$transaction->rollback($e);
								}
							}
						}

						// Add one user to the count.
						$successes++;
					} catch (\Throwable $e) {
						// Add one fault to the count.
						$failures++;
					}
				}
			}

			// Close the table of users.
			$result .= "</tbody></table></div>";

			// Add a message with the number of hits.
			if ($successes > 0) {
				$message['success'] = "<div class=\"alert alert-success\" role=\"alert\">
        							      <strong>Well done!</strong> {$successes} users were created.
        							</div>";
			}

			// Add a message with the number of failures.
			if ($failures > 0) {
				$message['danger'] = "<div class=\"alert alert-danger\" role=\"alert\">
        							      <strong>Heads up!</strong> {$failures} users could not be created.
        							</div>";
			}

			// Add the result.
			$message['info'] = "<div class=\"alert alert-info\" role=\"alert\">
									  <strong>Oh snap!</strong> The following users were created:<br><br>
									  {$result}
        						</div>";

			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('message', $message);
		}

		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}

	/**
	 * This method load the 'suspend-user' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getSuspendUser()
	{
		// Parsing the users.
		$users = addslashes(
			json_encode(
				get_users_listing(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/users/suspend-user.mustache',
			[
				'users' => $users
			]
		);
	}

	/**
	 * This method load the 'suspend-user' route. <br/>
	 * <b>post: </b>access to POST method.
	 */
	public function postSuspendUser()
	{
		// Imports Database.
		global $DB;

		$validation = new Validator($_POST);

		$validation->rules([
			'required' => [
				['users']
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
			foreach ($_POST['users'] as $userid) {
				try {
					$DB->set_field('user', 'suspended', '1', ['id' => $userid]);

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
        							      <strong>Well done!</strong> {$successes} users were suspended.
        							</div>";
			}

			// Add a message with the number of failures.
			if ($failures > 0) {
				$message['danger'] = "<div class=\"alert alert-danger\" role=\"alert\">
        							      <strong>Heads up!</strong> {$failures} users could not be suspended.
        							</div>";
			}

			$segment = $this->session->getSegment('alternateadmin');

			$segment->setFlash('message', $message);
		}

		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}

	/**
	 * This method load the 'switch-authentication' route. <br/>
	 * <b>post: </b>access to GET method.
	 */
	public function getSwitchAuthentication()
	{
		// Parsing the users.
		$users = addslashes(
			json_encode(
				get_users_listing(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		$authplugins = addslashes(
			json_encode(
				get_enabled_auth_plugins(),
				JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		);

		// Render template.
		return $this->render(
			'/users/switch-authentication.mustache',
			[
				'users' => $users,
				'authplugins' => $authplugins
			]
		);
	}

	/**
	 * This method load the 'switch-authentication' route. <br/>
	 * <b>post: </b>access to POST method.
	 */
	public function postSwitchAuthentication()
	{
		// Imports Database.
		global $DB;

		$validation = new Validator($_POST);

		$validation->rules([
			'required' => [
				['users'],
				['auth']
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
			foreach ($_POST['users'] as $userid) {
				try {
					$DB->set_field('user', 'auth', $_POST['auth'], ['id' => $userid]);

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

	/**
	 * This method load the 'download-users' route. <br/>
	 * <b>post: </b>access to GET method. <br/>
	 * <b>post: </b>Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.
	 */
	public function getDownloadUsers()
	{
		global $DB;

		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		$users = $DB->get_records('user');

		$sheet->setCellValue('A1', 'id');
		$sheet->setCellValue('B1', 'auth');
		$sheet->setCellValue('C1', 'confirmed');
		$sheet->setCellValue('D1', 'policyagreed');
		$sheet->setCellValue('E1', 'deleted');
		$sheet->setCellValue('F1', 'suspended');
		$sheet->setCellValue('G1', 'mnethostid');
		$sheet->setCellValue('H1', 'username');
		$sheet->setCellValue('I1', 'password');
		$sheet->setCellValue('J1', 'idnumber');
		$sheet->setCellValue('K1', 'firstname');
		$sheet->setCellValue('L1', 'lastname');
		$sheet->setCellValue('M1', 'email');
		$sheet->setCellValue('N1', 'emailstop');
		$sheet->setCellValue('O1', 'icq');
		$sheet->setCellValue('P1', 'skype');
		$sheet->setCellValue('Q1', 'yahoo');
		$sheet->setCellValue('R1', 'aim');
		$sheet->setCellValue('S1', 'msn');
		$sheet->setCellValue('T1', 'phone1');
		$sheet->setCellValue('U1', 'phone2');
		$sheet->setCellValue('V1', 'institution');
		$sheet->setCellValue('W1', 'department');
		$sheet->setCellValue('X1', 'address');
		$sheet->setCellValue('Y1', 'city');
		$sheet->setCellValue('Z1', 'country');
		$sheet->setCellValue('AA1', 'lang');
		$sheet->setCellValue('AB1', 'calendartype');
		$sheet->setCellValue('AC1', 'theme');
		$sheet->setCellValue('AD1', 'timezone');
		$sheet->setCellValue('AE1', 'firstaccess');
		$sheet->setCellValue('AF1', 'lastaccess');
		$sheet->setCellValue('AG1', 'lastlogin');
		$sheet->setCellValue('AH1', 'currentlogin');
		$sheet->setCellValue('AI1', 'lastip');
		$sheet->setCellValue('AJ1', 'secret');
		$sheet->setCellValue('AK1', 'picture');
		$sheet->setCellValue('AL1', 'url');
		$sheet->setCellValue('AM1', 'description');
		$sheet->setCellValue('AN1', 'descriptionformat');
		$sheet->setCellValue('AO1', 'mailformat');
		$sheet->setCellValue('AP1', 'maildigest');
		$sheet->setCellValue('AQ1', 'maildisplay');
		$sheet->setCellValue('AR1', 'autosubscribe');
		$sheet->setCellValue('AS1', 'trackforums');
		$sheet->setCellValue('AT1', 'timecreated');
		$sheet->setCellValue('AU1', 'timemodified');
		$sheet->setCellValue('AV1', 'trustbitmask');
		$sheet->setCellValue('AW1', 'imagealt');
		$sheet->setCellValue('AX1', 'lastnamephonetic');
		$sheet->setCellValue('AY1', 'firstnamephonetic');
		$sheet->setCellValue('AZ1', 'middlename');
		$sheet->setCellValue('BA1', 'alternatename');

		$index = 2;

		foreach ($users as $user) {
			$sheet->setCellValue('A' . $index, $user->id);
			$sheet->setCellValue('B' . $index, $user->auth);
			$sheet->setCellValue('C' . $index, $user->confirmed);
			$sheet->setCellValue('D' . $index, $user->policyagreed);
			$sheet->setCellValue('E' . $index, $user->deleted);
			$sheet->setCellValue('F' . $index, $user->suspended);
			$sheet->setCellValue('G' . $index, $user->mnethostid);
			$sheet->setCellValue('H' . $index, $user->username);
			$sheet->setCellValue('I' . $index, $user->password);
			$sheet->setCellValue('J' . $index, $user->idnumber);
			$sheet->setCellValue('K' . $index, $user->firstname);
			$sheet->setCellValue('L' . $index, $user->lastname);
			$sheet->setCellValue('M' . $index, $user->email);
			$sheet->setCellValue('N' . $index, $user->emailstop);
			$sheet->setCellValue('O' . $index, $user->icq);
			$sheet->setCellValue('P' . $index, $user->skype);
			$sheet->setCellValue('Q' . $index, $user->yahoo);
			$sheet->setCellValue('R' . $index, $user->aim);
			$sheet->setCellValue('S' . $index, $user->msn);
			$sheet->setCellValue('T' . $index, $user->phone1);
			$sheet->setCellValue('U' . $index, $user->phone2);
			$sheet->setCellValue('V' . $index, $user->institution);
			$sheet->setCellValue('W' . $index, $user->department);
			$sheet->setCellValue('X' . $index, $user->address);
			$sheet->setCellValue('Y' . $index, $user->city);
			$sheet->setCellValue('Z' . $index, $user->country);
			$sheet->setCellValue('AA' . $index, $user->lang);
			$sheet->setCellValue('AB' . $index, $user->calendartype);
			$sheet->setCellValue('AC' . $index, $user->theme);
			$sheet->setCellValue('AD' . $index, $user->timezone);
			$sheet->setCellValue('AE' . $index, $user->firstaccess);
			$sheet->setCellValue('AF' . $index, $user->lastaccess);
			$sheet->setCellValue('AG' . $index, $user->lastlogin);
			$sheet->setCellValue('AH' . $index, $user->currentlogin);
			$sheet->setCellValue('AI' . $index, $user->lastip);
			$sheet->setCellValue('AJ' . $index, $user->secret);
			$sheet->setCellValue('AK' . $index, $user->picture);
			$sheet->setCellValue('AL' . $index, $user->url);
			$sheet->setCellValue('AM' . $index, $user->description);
			$sheet->setCellValue('AN' . $index, $user->descriptionformat);
			$sheet->setCellValue('AO' . $index, $user->mailformat);
			$sheet->setCellValue('AP' . $index, $user->maildigest);
			$sheet->setCellValue('AQ' . $index, $user->maildisplay);
			$sheet->setCellValue('AR' . $index, $user->autosubscribe);
			$sheet->setCellValue('AS' . $index, $user->trackforums);
			$sheet->setCellValue('AT' . $index, $user->timecreated);
			$sheet->setCellValue('AU' . $index, $user->timemodified);
			$sheet->setCellValue('AV' . $index, $user->trustbitmask);
			$sheet->setCellValue('AW' . $index, $user->imagealt);
			$sheet->setCellValue('AX' . $index, $user->lastnamephonetic);
			$sheet->setCellValue('AY' . $index, $user->firstnamephonetic);
			$sheet->setCellValue('AZ' . $index, $user->middlename);
			$sheet->setCellValue('BA' . $index, $user->alternatename);

			$index++;
		}

		$writer = new Csv($spreadsheet);

		header('Content-Description: File Transfer');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename=user.csv');
		header('Cache-Control: max-age=0');

		$writer->save('php://output');
	}

	/**
	 * This method load the 'download-example' route. <br/>
	 * <b>post: </b>access to GET method. <br/>
	 * <b>post: </b>Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.
	 */
	public function getDownloadExample()
	{
		global $DB;

		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		$users = $DB->get_records('user');

		$sheet->setCellValue('A1', 'username1');
		$sheet->setCellValue('A2', 'username2');
		$sheet->setCellValue('A3', 'username3');
		$sheet->setCellValue('B1', 'first name one');
		$sheet->setCellValue('B2', 'first name two');
		$sheet->setCellValue('B3', 'first name three');
		$sheet->setCellValue('C1', 'surname one');
		$sheet->setCellValue('C2', 'surname two');
		$sheet->setCellValue('C3', 'surname three');
		$sheet->setCellValue('D1', 'mail1@example.com');
		$sheet->setCellValue('D2', 'mail2@example.com');
		$sheet->setCellValue('D3', 'mail3@example.com');
		$sheet->setCellValue('E1', 'auth (optional)');
		$sheet->setCellValue('E2', 'auth (optional)');
		$sheet->setCellValue('E3', 'auth (optional)');
		$sheet->setCellValue('F1', 'p4ssw0rD (optional)');
		$sheet->setCellValue('F2', 'p4ssw0rD (optional)');
		$sheet->setCellValue('F3', 'p4ssw0rD (optional)');

		$writer = new Csv($spreadsheet);

		header('Content-Description: File Transfer');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename=example.csv');
		header('Cache-Control: max-age=0');

		$writer->save('php://output');
	}
}
