<?php
/**
 * Resque Web UI Application.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Andaris\ResqueWebUiBundle\DependencyInjection\Compiler\CsrfTokenManagerPass;

/**
 * Bundle class
 */
class AndarisResqueWebUiBundle extends Bundle
{
    const VERSION = '1.4.0';

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CsrfTokenManagerPass());
    }
}
