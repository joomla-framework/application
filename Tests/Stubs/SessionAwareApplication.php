<?php

/**
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Stubs;

use Joomla\Application\SessionAwareWebApplicationTrait;
use Joomla\Input\Input;

/**
 * Concrete host for SessionAwareWebApplicationTrait.
 *
 * PHPUnit 12 removed getMockForTrait(), so the trait is exercised through this class instead.
 */
class SessionAwareApplication
{
    use SessionAwareWebApplicationTrait;

    /**
     * The input object handed to the trait.
     *
     * @var  Input|null
     */
    private $input;

    /**
     * Set the input object the trait should see.
     *
     * @param   Input  $input  The input object.
     *
     * @return  void
     */
    public function setInput(Input $input): void
    {
        $this->input = $input;
    }

    /**
     * Method to get the application input object.
     *
     * @return  Input
     */
    public function getInput(): Input
    {
        return $this->input ??= new Input([]);
    }
}
