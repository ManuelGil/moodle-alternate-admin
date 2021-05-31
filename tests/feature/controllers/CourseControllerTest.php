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

use App\Controllers\CourseController;
use PHPUnit\Framework\TestCase;

/**
 * CourseControllerTest class
 *
 * @extends TestCase
 */
class CourseControllerTest extends TestCase
{

	// Define an instance of CourseController.
	private $courseController;

	/**
	 * This method creates the objects against which you will test.
	 */
	public function setUp(): void
	{
		// Inflate the instance.
		$this->courseController = new CourseController();
	}

	/**
	 * This method checks the count role by course when the role id is empty.
	 *
	 * @test
	 */
	public function testCountRoleWithEmptyRoleID()
	{
		$this->assertEquals('[]', $this->courseController->getCountRole());
	}

	/**
	 * This method checks the count role by course when the role id is invalid.
	 *
	 * @test
	 */
	public function testCountRoleWithInvalidRoleID()
	{
		$this->assertEquals('[]', $this->courseController->getCountRole('foo'));
	}

	/**
	 * This method checks the user list when the course id is empty.
	 *
	 * @test
	 */
	public function testListUsersWithEmptyCourseID()
	{
		$this->assertEquals('[]', $this->courseController->getListUsers());
	}

	/**
	 * This method checks the user list when the course id is invalid.
	 *
	 * @test
	 */
	public function testListUsersWithInvalidCourseID()
	{
		$this->assertEquals('[]', $this->courseController->getListUsers('foo'));
	}
}
