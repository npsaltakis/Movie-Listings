<?php

/**
 * @package     Com_Movielist
 * @copyright   (C) 2026 Nick Psaltakis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\Movielist\Administrator\Extension;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Psr\Container\ContainerInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Component class for com_movielist.
 */
class MovielistComponent extends MVCComponent implements BootableExtensionInterface
{
    /**
     * Booting the extension.
     *
     * @param   ContainerInterface  $container  The container.
     *
     * @return  void
     */
    public function boot(ContainerInterface $container)
    {
    }
}
