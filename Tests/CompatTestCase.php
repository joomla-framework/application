<?php
/**
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

use Joomla\Application\Tests\PhpUnit\PhpUnit6TestCase;
use Joomla\Application\Tests\PhpUnit\PhpUnit7TestCase;

$versionClass = class_exists('\\PHPUnit\\Runner\\Version') ? '\\PHPUnit\\Runner\\Version' : '\\PHPUnit_Runner_Version';

// Note, the compatibility classes MUST be in separate files so as to not introduce parse errors for older PHP versions
if (version_compare($versionClass::id(), '7.0', '>='))
{
	/**
	 * Compatibility test case used for PHPUnit 7.x and later
	 */
	abstract class CompatTestCase extends PhpUnit7TestCase
	{
	}
}
elseif (version_compare($versionClass::id(), '4.0', '>='))
{
	/**
	 * Compatibility test case used for PHPUnit 6.x and earlier
	 */
	abstract class CompatTestCase extends PhpUnit6TestCase
	{
	}
}
else
{
	/**
	 * Compatibility test case used for PHPUnit 3.x and earlier
	 *
	 * @since 1.4.0
	 */
	abstract class TestCase extends PhpUnit3TestCase
	{
	}
}
