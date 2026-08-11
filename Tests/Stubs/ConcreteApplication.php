<?php

/**
 * @copyright  (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Stubs;

use Joomla\Application\AbstractApplication;

/**
 * Concrete implementation of AbstractApplication for testing.
 *
 * PHPUnit 12 removed getMockForAbstractClass(), so tests that need an instance of the abstract
 * application use this stub, and tests that need to observe doExecute() build a mock of it.
 */
class ConcreteApplication extends AbstractApplication
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
