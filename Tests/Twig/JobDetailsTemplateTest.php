<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Twig;

use Resque\Job as ResqueJob;

use Andaris\ResqueWebUiBundle\Dto\Job;

class JobDetailsTemplateTest extends ListTemplateTestCase
{
    const PAYLOAD = '{"id":"old123","class":"App\\\\Job\\\\SendMail","data":[]}';

    public function testTheRetryButtonOpensTheConfirmationRatherThanQueueingAtOnce()
    {
        $output = $this->render();

        $this->assertStringContainsString('data-target="#jobRetryConfirmation"', $output);
        $this->assertStringContainsString('<div class="modal fade" id="jobRetryConfirmation"', $output);
    }

    /**
     * The button carries a glyphicon and nothing else, so what it does has to
     * be spelled out for anything that does not look at it.
     */
    public function testTheRetryButtonSaysWhatItDoes()
    {
        $output = $this->render();

        $this->assertStringContainsString('aria-label="Queue this job again"', $output);
    }

    /**
     * Queueing a job changes what the workers will do, so the confirmation is
     * a form that posts rather than a link that can be followed by anything
     * that walks the page.
     */
    public function testTheConfirmationPostsToTheRetryRoute()
    {
        $output = $this->render();

        $this->assertRegExp('#<form[^>]+method="post"#', $output);
        $this->assertStringContainsString('andaris_resque_web_ui_job_retry?jobId=old123', $output);
        $this->assertStringContainsString('name="_token" value="a-token"', $output);
    }

    public function testTheJobThatCameOutIsLinked()
    {
        $output = $this->render($this->job('new456', ResqueJob::STATUS_WAITING, '{}'));

        $this->assertStringContainsString('alert-success', $output);
        $this->assertStringContainsString('andaris_resque_web_ui_job?jobId=new456', $output);
    }

    public function testAFailedRetryIsReportedInsteadOfALink()
    {
        $output = $this->render(null, 'payload');

        $this->assertStringContainsString('alert-danger', $output);
        $this->assertStringContainsString('its payload is not valid JSON', $output);
        $this->assertStringNotContainsString('alert-success', $output);
    }

    /**
     * The reason is a name out of a fixed set, and the sentence belongs to this
     * template: an address that carries something else says nothing at all.
     *
     * @dataProvider foreignReasonProvider
     */
    public function testAReasonOfSomewhereElseIsNotShown($reason, $trace)
    {
        $output = $this->render(null, $reason);

        $this->assertStringNotContainsString('alert-danger', $output);
        $this->assertStringNotContainsString($trace, $output);
    }

    public function foreignReasonProvider()
    {
        return [
            'a sentence of its own' => ['Please log in at https://evil.example/', 'evil.example'],
            'markup' => ['<script>alert(1)</script>', 'script'],
            'a reason that does not exist' => ['whatever', 'whatever'],
        ];
    }

    public function testNothingIsReportedWithoutARetry()
    {
        $output = $this->render();

        $this->assertStringNotContainsString('alert-success', $output);
        $this->assertStringNotContainsString('alert-danger', $output);
    }

    private function render(Job $retriedJob = null, $retryFailure = null)
    {
        return $this->renderTemplate('Job/details.html.twig', [
            'job' => $this->job('old123', ResqueJob::STATUS_FAILED, self::PAYLOAD),
            'retryToken' => 'a-token',
            'retriedJob' => $retriedJob,
            'retryFailure' => $retryFailure,
        ]);
    }

    private function job($id, $status, $payload)
    {
        return new Job($id, $status, 'emails', null, $payload, null, null, null, null, null);
    }
}
