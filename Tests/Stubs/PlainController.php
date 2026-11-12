<?php

/**
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Stubs;

/**
 * A controller with an execute() method which does not implement the ControllerInterface.
 */
class PlainController
{
    public function execute()
    {
        return 'Hello world!';
    }
}
