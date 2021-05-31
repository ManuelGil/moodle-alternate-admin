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

use Aura\Session\SessionFactory;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;
use Mustache_Logger_StreamLogger;

/**
 * BaseController class.
 */
class BaseController
{

	protected $mustacheEngine;
	protected $session;

	/**
	 * This method construct a new Controller.
	 */
	public function __construct()
	{
		// Imports Config.
		global $CFG;

		// Sets a new Session.
		$sessionFactory = new SessionFactory;
		$this->session = $sessionFactory->newInstance($_COOKIE);

		// Setting an intance for Mustache Engine.
		$this->mustacheEngine = new Mustache_Engine(array(
			'cache' => $CFG->localcachedir . '/mustache',
			'cache_file_mode' => $CFG->umaskpermissions,
			'cache_lambda_templates' => true,
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/../views'),
			'charset' => 'ISO-8859-1',
			'logger' => new Mustache_Logger_StreamLogger('php://stderr'),
		));

		// Create a new filter for base_url.
		$this->mustacheEngine->addHelper('base_url', function () {
			return BASE_URL;
		});

		// Create a new filter for site_url.
		$this->mustacheEngine->addHelper('site_url', function () {
			global $CFG;

			return $CFG->wwwroot;
		});

		// Create a new filter for flash values.
		$this->mustacheEngine->addHelper('message', function (string $type) {
			$segment = $this->session->getSegment('alternateadmin');

			return $segment->getFlash('message')[$type] ?? '';
		});

		// Create a new filter for flash values.
		$this->mustacheEngine->addHelper('errors', function (string $field) {
			$segment = $this->session->getSegment('alternateadmin');

			return $segment->getFlash('errors')[$field][0] ?? '';
		});

		// Create a new filter for md5 encoding.
		$this->mustacheEngine->addHelper('md5', function (string $email) {
			return md5(strtolower(trim($email)));
		});
	}

	/**
	 * This method render the template.
	 *
	 * @param string $templateName - the filename of template.
	 * @param array $templateData - the data with context of the template.
	 * @return string the template rendered.
	 */
	protected function render($templateName, $templateData = [])
	{
		// Imports Current User.
		global $USER;

		$localData = array(
			'USER' => $USER,
		);

		$template = $this->mustacheEngine->loadTemplate($templateName);

		// Render the template.
		return $template->render(array_merge($templateData, $localData));
	}
}
