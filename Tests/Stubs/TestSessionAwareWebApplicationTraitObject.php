<?php

/**
 * @copyright  (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Stubs;

use Joomla\Application\SessionAwareWebApplicationTrait;
use Joomla\Input\Input;

class TestSessionAwareWebApplicationTraitObject
{
    use SessionAwareWebApplicationTrait;

    public function getInput(): Input
    {
        return new Input([]);
    }
}
