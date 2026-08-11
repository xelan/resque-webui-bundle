<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;

use Andaris\ResqueWebUiBundle\Dto\Job;

class JobTest extends TestCase
{
    public function testItExposesEveryConstructorArgument()
    {
        $job = new Job(
            'abc123',
            3,
            'emails',
            'host:1:default',
            '{"class":"SendMail"}',
            '{"message":"boom"}',
            1500000000,
            1500000001,
            1500000002,
            1500000003
        );

        $this->assertSame('abc123', $job->getId());
        $this->assertSame(3, $job->getStatus());
        $this->assertSame('emails', $job->getQueue());
        $this->assertSame('host:1:default', $job->getWorker());
        $this->assertSame('{"class":"SendMail"}', $job->getPayload());
        $this->assertSame('{"message":"boom"}', $job->getException());
        $this->assertSame(1500000000, $job->getCreated());
        $this->assertSame(1500000001, $job->getStarted());
        $this->assertSame(1500000002, $job->getUpdated());
        $this->assertSame(1500000003, $job->getFinished());
    }

    /**
     * JobFactory passes null for every field that is absent in the Redis hash.
     */
    public function testTheOptionalFieldsStayNull()
    {
        $job = new Job('abc123', 1, 'emails', null, null, null, null, null, null, null);

        $this->assertNull($job->getWorker());
        $this->assertNull($job->getPayload());
        $this->assertNull($job->getException());
        $this->assertNull($job->getCreated());
        $this->assertNull($job->getStarted());
        $this->assertNull($job->getUpdated());
        $this->assertNull($job->getFinished());
    }

    /**
     * Redis returns every hash field as a string and the factory passes those
     * through untouched, so the getters currently hand out strings as well.
     */
    public function testTheValuesAreHandedBackWithoutConversion()
    {
        $job = new Job('abc123', '3', 'emails', null, null, null, '1500000000', null, null, null);

        $this->assertSame('3', $job->getStatus());
        $this->assertSame('1500000000', $job->getCreated());
    }
}
