<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Hands the retry form the token manager of the application, where there is one.
 *
 * An application with CSRF protection turned on already keeps tokens somewhere,
 * and its manager is the one its session, its listeners and its forms agree on.
 * Only where there is none does the bundle fall back to a manager of its own,
 * which is why this runs as a pass: whether the framework defines that service
 * is not settled while the extensions are still loading.
 */
class CsrfTokenManagerPass implements CompilerPassInterface
{
    const BUNDLE_SERVICE = 'andaris_resque_web_ui.csrf_token_manager';
    const APPLICATION_SERVICE = 'security.csrf.token_manager';

    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::APPLICATION_SERVICE)) {
            return;
        }

        $container->removeDefinition(self::BUNDLE_SERVICE);
        $container->setAlias(self::BUNDLE_SERVICE, new Alias(self::APPLICATION_SERVICE, false));
    }
}
