<?php

/**
 * @copyright  (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Stubs;

use Joomla\Application\AbstractWebApplication;

/**
 * Concrete implementation of AbstractWebApplication for testing.
 *
 * PHPUnit 12 removed getMockForAbstractClass(), so tests that need an instance of the abstract
 * web application use this stub, and tests that need to observe or replace doExecute() build a
 * mock of it.
 */
class ConcreteWebApplication extends AbstractWebApplication
{
    /**
     * Records whether doExecute() was called.
     *
     * @var  boolean
     */
    public $executed = false;

    /**
     * Method to run the application routines.
     *
     * @return  void
     */
    protected function doExecute()
    {
        $this->executed = true;
    }
}
