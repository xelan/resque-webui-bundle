<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Resque\Job as ResqueJob;
use Symfony\Component\HttpFoundation\Request;

use Andaris\ResqueWebUiBundle\Dto\Job;
use Andaris\ResqueWebUiBundle\Dto\JobCriteria;

class JobCriteriaTest extends TestCase
{
    public function testTheDefaultsShowEveryJobNewestFirst()
    {
        $criteria = new JobCriteria();

        $this->assertNull($criteria->getStatus());
        $this->assertSame('created', $criteria->getField());
        $this->assertSame(JobCriteria::DIRECTION_DESCENDING, $criteria->getDirection());
        $this->assertTrue($criteria->isDescending());
    }

    /**
     * @dataProvider sortableFieldProvider
     */
    public function testEveryColumnOfTheListCanBeSortedOn($field, $getter)
    {
        $criteria = new JobCriteria(null, $field);

        $this->assertSame($field, $criteria->getField());
        $this->assertSame($getter, $criteria->getFieldGetter());
    }

    public function sortableFieldProvider()
    {
        return [
            'id' => ['id', 'getId'],
            'status' => ['status', 'getStatus'],
            'queue' => ['queue', 'getQueue'],
            'worker' => ['worker', 'getWorker'],
            'created' => ['created', 'getCreated'],
            'started' => ['started', 'getStarted'],
            'updated' => ['updated', 'getUpdated'],
            'finished' => ['finished', 'getFinished'],
        ];
    }

    /**
     * The field selects a getter, so anything unknown has to fall back to the
     * default rather than reach the job.
     *
     * @dataProvider unknownFieldProvider
     */
    public function testAnUnknownFieldFallsBackToTheDefault($field)
    {
        $this->assertSame('created', (new JobCriteria(null, $field))->getField());
    }

    public function unknownFieldProvider()
    {
        return [
            'not a column' => ['payload'],
            'a method' => ['getPayload'],
            'empty' => [''],
            'null' => [null],
            'numeric' => ['1'],
        ];
    }

    /**
     * @dataProvider directionProvider
     */
    public function testOnlyAscendingTurnsOffTheDefaultDirection($direction, $expected)
    {
        $this->assertSame($expected, (new JobCriteria(null, 'created', $direction))->getDirection());
    }

    public function directionProvider()
    {
        return [
            'ascending' => ['asc', JobCriteria::DIRECTION_ASCENDING],
            'descending' => ['desc', JobCriteria::DIRECTION_DESCENDING],
            'unknown' => ['sideways', JobCriteria::DIRECTION_DESCENDING],
            'empty' => ['', JobCriteria::DIRECTION_DESCENDING],
        ];
    }

    /**
     * @dataProvider numericFieldProvider
     */
    public function testTheTimestampsAndTheStatusAreComparedAsNumbers($field, $expected)
    {
        $this->assertSame($expected, (new JobCriteria(null, $field))->isNumericField());
    }

    public function numericFieldProvider()
    {
        return [
            'created' => ['created', true],
            'started' => ['started', true],
            'updated' => ['updated', true],
            'finished' => ['finished', true],
            'status' => ['status', true],
            'id' => ['id', false],
            'queue' => ['queue', false],
            'worker' => ['worker', false],
        ];
    }

    /**
     * @dataProvider statusProvider
     */
    public function testOnlyAKnownStatusIsAccepted($status, $expected)
    {
        $this->assertSame($expected, (new JobCriteria($status))->getStatus());
    }

    public function statusProvider()
    {
        return [
            'waiting' => [ResqueJob::STATUS_WAITING, ResqueJob::STATUS_WAITING],
            'failed' => [ResqueJob::STATUS_FAILED, ResqueJob::STATUS_FAILED],
            'numeric string from the query' => [(string) ResqueJob::STATUS_RUNNING, ResqueJob::STATUS_RUNNING],
            'no filter' => [null, null],
            'one below the lowest' => [0, null],
            'one above the highest' => [7, null],
            'not a number' => ['failed', null],
            'empty' => ['', null],
        ];
    }

    public function testTheColumnInUseLinksToTheOppositeDirection()
    {
        $criteria = new JobCriteria(null, 'created', JobCriteria::DIRECTION_DESCENDING);

        $this->assertTrue($criteria->isSortedBy('created'));
        $this->assertSame(JobCriteria::DIRECTION_ASCENDING, $criteria->getToggledDirection('created'));
    }

    public function testAnotherColumnLinksToAscending()
    {
        $criteria = new JobCriteria(null, 'created', JobCriteria::DIRECTION_ASCENDING);

        $this->assertFalse($criteria->isSortedBy('queue'));
        $this->assertSame(JobCriteria::DIRECTION_ASCENDING, $criteria->getToggledDirection('queue'));
        $this->assertSame(JobCriteria::DIRECTION_DESCENDING, $criteria->getToggledDirection('created'));
    }

    public function testItIsReadFromTheQueryString()
    {
        $request = Request::create('/jobs?status=6&sort=queue&direction=asc');

        $criteria = JobCriteria::fromRequest($request);

        $this->assertSame(ResqueJob::STATUS_FAILED, $criteria->getStatus());
        $this->assertSame('queue', $criteria->getField());
        $this->assertSame(JobCriteria::DIRECTION_ASCENDING, $criteria->getDirection());
    }

    public function testARequestWithoutParametersUsesTheDefaults()
    {
        $criteria = JobCriteria::fromRequest(Request::create('/jobs'));

        $this->assertNull($criteria->getStatus());
        $this->assertSame('created', $criteria->getField());
        $this->assertTrue($criteria->isDescending());
    }

    public function testAStaleBookmarkDoesNotBreakTheList()
    {
        $criteria = JobCriteria::fromRequest(Request::create('/jobs?status=99&sort=payload&direction=up'));

        $this->assertNull($criteria->getStatus());
        $this->assertSame('created', $criteria->getField());
        $this->assertTrue($criteria->isDescending());
    }

    public function testWithoutAStatusEveryJobMatches()
    {
        $criteria = new JobCriteria();

        $this->assertTrue($criteria->matches($this->createJob(ResqueJob::STATUS_RUNNING)));
        $this->assertTrue($criteria->matches($this->createJob(ResqueJob::STATUS_FAILED)));
    }

    public function testOnlyJobsOfTheSelectedStatusMatch()
    {
        $criteria = new JobCriteria(ResqueJob::STATUS_FAILED);

        $this->assertTrue($criteria->matches($this->createJob(ResqueJob::STATUS_FAILED)));
        $this->assertFalse($criteria->matches($this->createJob(ResqueJob::STATUS_COMPLETE)));
    }

    /**
     * Redis hands the status back as a string.
     */
    public function testAStatusMatchesRegardlessOfItsType()
    {
        $criteria = new JobCriteria(ResqueJob::STATUS_FAILED);

        $this->assertTrue($criteria->matches($this->createJob((string) ResqueJob::STATUS_FAILED)));
    }

    private function createJob($status)
    {
        return new Job('abc123', $status, 'emails', null, null, null, null, null, null, null);
    }
}
