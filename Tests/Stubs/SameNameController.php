<?php

/**
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Stubs;

use Joomla\Controller\AbstractController;

/**
 * A controller class with a function of the same name, see below.
 */
class SameNameController extends AbstractController
{
    public function execute()
    {
        return 'Hello from the class!';
    }
}

/**
 * A function with the same name as the controller class above.
 */
function SameNameController()
{
    return 'Hello from the function!';
}
