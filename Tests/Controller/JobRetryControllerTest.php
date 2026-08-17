<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Resque\Job as ResqueJob;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Controller\JobController;
use Andaris\ResqueWebUiBundle\Dto\Job;
use Andaris\ResqueWebUiBundle\Dto\JobFactory;
use Andaris\ResqueWebUiBundle\Exception\JobNotRepeatableException;

class JobRetryControllerTest extends TestCase
{
    const VALID_TOKEN = 'a-valid-token';

    /**
     * @var array
     */
    private $parameters;

    /**
     * Queueing a job again changes what the workers will do, so a request that
     * does not carry the token of this interface must not be acted on.
     *
     * @dataProvider rejectedTokenProvider
     */
    public function testARequestWithoutTheTokenIsRefused($token)
    {
        $factory = $this->createMock(JobFactory::class);
        $factory->expects($this->never())->method('recreate');

        $this->expectException(BadRequestHttpException::class);

        $this->createController($factory)->retryAction('old123', $this->retryRequest($token));
    }

    public function rejectedTokenProvider()
    {
        return [
            'no token at all' => [null],
            'empty' => [''],
            'another token' => ['not-the-token'],
            'an array' => [['a-valid-token']],
        ];
    }

    public function testTheJobIsQueuedAgainAndTheResultIsInTheAddress()
    {
        $factory = $this->createMock(JobFactory::class);
        $factory->method('createById')->willReturn($this->job('old123'));
        $factory->expects($this->once())->method('recreate')->willReturn($this->job('new456'));

        $response = $this->createController($factory)->retryAction('old123', $this->retryRequest(self::VALID_TOKEN));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/job/old123?retried=new456', $response->getTargetUrl());
    }

    public function testAJobThatIsGoneIsNotFound()
    {
        $factory = $this->createMock(JobFactory::class);
        $factory->method('createById')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->createController($factory)->retryAction('gone', $this->retryRequest(self::VALID_TOKEN));
    }

    public function testAJobThatCannotBeRepeatedComesBackWithTheReason()
    {
        $factory = $this->createMock(JobFactory::class);
        $factory->method('createById')->willReturn($this->job('old123'));
        $factory->method('recreate')->willThrowException(
            JobNotRepeatableException::payloadNamesNoClass('old123')
        );

        $response = $this->createController($factory)->retryAction('old123', $this->retryRequest(self::VALID_TOKEN));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/job/old123?retryFailure=class', $response->getTargetUrl());
    }

    /**
     * The details page links the job a retry produced, as long as it is still
     * there; an address naming one that is gone simply shows no link.
     */
    public function testTheDetailsPageLinksTheJobThatCameOut()
    {
        $factory = $this->createMock(JobFactory::class);
        $factory->method('createById')->willReturnCallback(function ($id) {
            return $id === 'gone' ? null : $this->job($id);
        });

        $this->createController($factory)->detailsAction('old123', Request::create('/job/old123?retried=new456'));

        $this->assertInstanceOf(Job::class, $this->parameters['retriedJob']);
        $this->assertSame('new456', $this->parameters['retriedJob']->getId());
    }

    public function testTheDetailsPageShowsNoLinkForAJobThatIsGone()
    {
        $factory = $this->createMock(JobFactory::class);
        $factory->method('createById')->willReturnCallback(function ($id) {
            return $id === 'gone' ? null : $this->job($id);
        });

        $this->createController($factory)->detailsAction('old123', Request::create('/job/old123?retried=gone'));

        $this->assertNull($this->parameters['retriedJob']);
    }

    /**
     * The address is written by whoever hands out the link, so only the reasons
     * this bundle knows reach the page; anything else is simply not a failure.
     *
     * @dataProvider retryFailureProvider
     */
    public function testOnlyAKnownReasonIsReported($reason, $expected)
    {
        $factory = $this->createMock(JobFactory::class);
        $factory->method('createById')->willReturn($this->job('old123'));

        $request = Request::create('/job/old123', 'GET', ['retryFailure' => $reason]);

        $this->createController($factory)->detailsAction('old123', $request);

        $this->assertSame($expected, $this->parameters['retryFailure']);
    }

    public function retryFailureProvider()
    {
        return [
            'unreadable payload' => ['payload', 'payload'],
            'no class' => ['class', 'class'],
            'refused' => ['refused', 'refused'],
            'a sentence of its own' => ['Please log in at https://evil.example/', null],
            'markup' => ['<script>alert(1)</script>', null],
            'empty' => ['', null],
            'an array' => [['class'], null],
        ];
    }

    public function testTheDetailsPageCarriesTheTokenTheFormNeeds()
    {
        $factory = $this->createMock(JobFactory::class);
        $factory->method('createById')->willReturn($this->job('old123'));

        $this->createController($factory)->detailsAction('old123', Request::create('/job/old123'));

        $this->assertSame(self::VALID_TOKEN, $this->parameters['retryToken']);
    }

    private function retryRequest($token)
    {
        return Request::create('/job/old123/retry', 'POST', $token === null ? [] : ['_token' => $token]);
    }

    private function job($id)
    {
        return new Job($id, ResqueJob::STATUS_FAILED, 'emails', null, '{}', null, null, null, null, null);
    }

    private function createController(JobFactory $factory)
    {
        $parameters = &$this->parameters;

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturnCallback(function ($view, array $given = []) use (&$parameters) {
            $parameters = $given;

            return '';
        });

        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('getToken')->willReturn(new CsrfToken(JobController::RETRY_TOKEN, self::VALID_TOKEN));
        $csrf->method('isTokenValid')->willReturnCallback(function (CsrfToken $token) {
            return $token->getValue() === self::VALID_TOKEN;
        });

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(function ($route, array $parameters = []) {
            $id = $parameters['jobId'];
            unset($parameters['jobId']);
            $query = http_build_query($parameters);

            return '/job/' . $id . ($query === '' ? '' : '?' . $query);
        });

        return new JobController($twig, $this->createMock(ResqueConfigurator::class), $factory, $csrf, $router);
    }
}
