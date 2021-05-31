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

use App\Controllers\EnrollmentController;
use PHPUnit\Framework\TestCase;

/**
 * EnrollmentControllerTest class
 *
 * @extends TestCase
 */
class EnrollmentControllerTest extends TestCase
{

	// Define an instance of EnrollmentController.
	private $enrollmentController;

	/**
	 * This method creates the objects against which you will test.
	 */
	public function setUp(): void
	{
		// Inflate the instance.
		$this->enrollmentController = new EnrollmentController();
	}

	/**
	 * This method checks the role assignment list when the course id is empty.
	 *
	 * @test
	 */
	public function testListModuleWithEmptyenrollmentID()
	{
		$this->assertEquals('[]', $this->enrollmentController->getListAssignments());
	}

	/**
	 * This method checks the role assignment list when the course id is empty.
	 *
	 * @test
	 */
	public function testListModuleWithInvalidenrollmentID()
	{
		$this->assertEquals('[]', $this->enrollmentController->getListAssignments('foo'));
	}
}
