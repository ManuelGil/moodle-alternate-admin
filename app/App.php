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

namespace App;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;

/**
 * App class.
 */
class App
{

	/**
	 * This method launch the application.
	 */
	public function run()
	{
		// Get the base url of the application.
		$baseDir = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
		$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'] . $baseDir;

		// Setting the BASE URL as constant.
		define('BASE_URL', $baseUrl);

		// Get the route name.
		// If not exist the default route is '/'.
		$route = $_GET['route'] ?? '/';

		$router = new RouteCollector();

		/**
		 * This rutine restricts access to addresses. <br/>
		 * <b>post: </b>To access is required have permissions as administrator.
		 */
		$router->filter('auth', function () {
			// Imports Config.
			global $CFG;

			if (!isloggedin()) {
				// If not user in logged.
				// Redirect to root.
				redirect($CFG->wwwroot . '/');
				return false;
			} else {
				// If user isn't admin.
				// Redirect to root.
				if (!is_siteadmin()) {
					redirect($CFG->wwwroot . '/');
					return false;
				}
			}
		});

		$router->group(['before' => 'auth'], function ($router) {
			$router->controller('/', Controllers\MainController::class);
			$router->controller('/activities', Controllers\ActivityController::class);
			$router->controller('/courses', Controllers\CourseController::class);
			$router->controller('/enrollments', Controllers\EnrollmentController::class);
			$router->controller('/reports', Controllers\ReportController::class);
			$router->controller('/users', Controllers\UserController::class);
		});

		// Create a log channel.
		$log = new Logger('App');
		$log->pushHandler(new StreamHandler(__DIR__ . '/../logs/error.log', Logger::ERROR));

		$dispatcher = new Dispatcher($router->getData());

		$response = '';

		try {
			$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $route);
		} catch (HttpMethodNotAllowedException $e) {
			// When method is not allowed.
			if (DEBUG) {
				header_remove();
				http_response_code(405);
				header('HTTP/1.1 405 Method Not Allowed');
				echo $e->getMessage();
			} else {
				$log->error($e->getMessage(), $e->getTrace());
				header_remove();
				http_response_code(500);
				header('HTTP/1.1 500 Internal Server Error');
				echo "	<head>
							<link rel=\"stylesheet\" href=\"" . BASE_URL . "assets/css/slim.min.css\">
							<script src=\"" . BASE_URL . "assets/js/slim.min.js\"></script>
						</head>
						<body>
							<div class=\"page-error-wrapper\">
								<div>
									<h1 class=\"error-title\">500</h1>
									<h5 class=\"tx-sm-24 tx-normal\">Oopps. Internal server error.</h5>
									<p class=\"mg-b-50\">The server encountered an internal server error and was unable to complete your request.</p>
								</div>
							</div>
						</body>";
			}
			exit;
		} catch (HttpRouteNotFoundException $e) {
			// When route no exist.
			if (DEBUG) {
				header_remove();
				http_response_code(404);
				header('HTTP/1.1 404 Not Found');
				echo $e->getMessage();
			} else {
				$log->error($e->getMessage(), $e->getTrace());
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
			exit;
		} catch (\Throwable $e) {
			// When a generic error occurred.
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
				echo "	<head>
							<link rel=\"stylesheet\" href=\"" . BASE_URL . "assets/css/slim.min.css\">
							<script src=\"" . BASE_URL . "assets/js/slim.min.js\"></script>
						</head>
						<body>
							<div class=\"page-error-wrapper\">
								<div>
									<h1 class=\"error-title\">500</h1>
									<h5 class=\"tx-sm-24 tx-normal\">Oopps. Internal server error.</h5>
									<p class=\"mg-b-50\">The server encountered an internal server error and was unable to complete your request.</p>
								</div>
							</div>
						</body>";
			}
			exit;
		}

		echo $response;
	}
}
