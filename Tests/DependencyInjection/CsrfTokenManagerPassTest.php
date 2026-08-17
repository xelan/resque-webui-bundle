<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

use Andaris\ResqueWebUiBundle\DependencyInjection\Compiler\CsrfTokenManagerPass;

class CsrfTokenManagerPassTest extends TestCase
{
    public function testTheManagerOfTheApplicationIsPreferred()
    {
        $container = $this->createContainer();
        $container->setDefinition(CsrfTokenManagerPass::APPLICATION_SERVICE, new Definition(CsrfTokenManager::class));

        (new CsrfTokenManagerPass())->process($container);

        $this->assertTrue($container->hasAlias(CsrfTokenManagerPass::BUNDLE_SERVICE));
        $this->assertSame(
            CsrfTokenManagerPass::APPLICATION_SERVICE,
            (string) $container->getAlias(CsrfTokenManagerPass::BUNDLE_SERVICE)
        );
    }

    /**
     * Not every application turns CSRF protection on, and the retry form needs
     * a token either way.
     */
    public function testTheOwnManagerStaysWithoutOneOfTheApplication()
    {
        $container = $this->createContainer();

        (new CsrfTokenManagerPass())->process($container);

        $this->assertFalse($container->hasAlias(CsrfTokenManagerPass::BUNDLE_SERVICE));
        $this->assertTrue($container->hasDefinition(CsrfTokenManagerPass::BUNDLE_SERVICE));
    }

    private function createContainer()
    {
        $container = new ContainerBuilder();
        $container->setDefinition(CsrfTokenManagerPass::BUNDLE_SERVICE, new Definition(CsrfTokenManager::class));

        return $container;
    }
}
