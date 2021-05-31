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

use App\Controllers\UserController;
use PHPUnit\Framework\TestCase;

/**
 * UserControllerTest class
 *
 * @extends TestCase
 */
class UserControllerTest extends TestCase
{

	// Define an instance of UserController.
	private $userController;

	/**
	 * This method creates the objects against which you will test.
	 */
	public function setUp(): void
	{
		// Inflate the instance.
		$this->userController = new UserController();
	}

	/**
	 * This method checks the course list when the user id is empty.
	 *
	 * @test
	 */
	public function testListCoursesWithEmptyUserID()
	{
		$this->assertEquals('[]', $this->userController->getListCourses());
	}


	/**
	 * This method checks the course list when the user id is invalid.
	 *
	 * @test
	 */
	public function testListCoursesWithInvalidUserID()
	{
		$this->assertEquals('[]', $this->userController->getListCourses('foo'));
	}

	/**
	 * This method checks the user data when the user id is empty.
	 *
	 * @test
	 */
	public function testUserDataWithEmptyUserID()
	{
		$this->assertEquals('false', $this->userController->getUserData());
	}


	/**
	 * This method checks the user data when the user id is invalid.
	 *
	 * @test
	 */
	public function testUserDataWithInvalidUserID()
	{
		$this->assertEquals('false', $this->userController->getUserData('foo'));
	}
}
