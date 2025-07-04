<?php

/**
 * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Stubs;

use Joomla\Controller\AbstractController;

class HasArgumentsController extends AbstractController
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function execute()
    {
        return 'Hello ' . $this->name . '!';
    }
}
