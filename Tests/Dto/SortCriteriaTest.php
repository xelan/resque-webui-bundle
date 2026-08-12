<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

use Andaris\ResqueWebUiBundle\Dto\Job;
use Andaris\ResqueWebUiBundle\Dto\JobCriteria;
use Andaris\ResqueWebUiBundle\Dto\Queue;
use Andaris\ResqueWebUiBundle\Dto\QueueCriteria;
use Andaris\ResqueWebUiBundle\Dto\SortCriteria;
use Andaris\ResqueWebUiBundle\Dto\Worker;
use Andaris\ResqueWebUiBundle\Dto\WorkerCriteria;

/**
 * The three lists share their ordering, so what holds for one has to hold for
 * the others.
 */
class SortCriteriaTest extends TestCase
{
    /**
     * @dataProvider criteriaProvider
     */
    public function testEveryColumnOfTheListCanBeSortedOn($class, $defaultField, $defaultDirection)
    {
        $fields = constant($class . '::FIELDS');

        $this->assertNotEmpty($fields);

        foreach ($fields as $field => $getter) {
            $criteria = new $class($field);

            $this->assertSame($field, $criteria->getField());
            $this->assertSame($getter, $criteria->getFieldGetter());
        }
    }

    /**
     * @dataProvider criteriaProvider
     */
    public function testTheDefaultsAreUsedWithoutParameters($class, $defaultField, $defaultDirection)
    {
        $criteria = new $class();

        $this->assertSame($defaultField, $criteria->getField());
        $this->assertSame($defaultDirection, $criteria->getDirection());
    }

    /**
     * The field selects a getter, so anything unknown has to fall back to the
     * default rather than reach the entry.
     *
     * @dataProvider criteriaProvider
     */
    public function testAnUnknownFieldFallsBackToTheDefault($class, $defaultField)
    {
        foreach (['payload', 'getPayload', '', null, '1'] as $field) {
            $this->assertSame($defaultField, (new $class($field))->getField(), var_export($field, true));
        }
    }

    /**
     * @dataProvider criteriaProvider
     */
    public function testAnUnknownDirectionFallsBackToTheDefault($class, $defaultField, $defaultDirection)
    {
        foreach (['sideways', '', null] as $direction) {
            $this->assertSame($defaultDirection, (new $class(null, $direction))->getDirection());
        }
    }

    /**
     * @dataProvider criteriaProvider
     */
    public function testEveryNumericFieldIsAlsoASortableField($class)
    {
        $fields = constant($class . '::FIELDS');
        $numeric = constant($class . '::NUMERIC_FIELDS');

        foreach ($numeric as $field) {
            $this->assertArrayHasKey($field, $fields, $field . ' is numeric but cannot be sorted on');
            $this->assertTrue((new $class($field))->isNumericField());
        }
    }

    /**
     * @dataProvider criteriaProvider
     */
    public function testWhatIdentifiesAnEntryIsASortableFieldAsWell($class)
    {
        $fields = constant($class . '::FIELDS');

        $this->assertContains(
            constant($class . '::IDENTITY_GETTER'),
            $fields,
            'the getter breaking ties is not among the sortable fields'
        );
    }

    /**
     * @dataProvider criteriaProvider
     */
    public function testTheColumnInUseLinksToTheOppositeDirection($class, $defaultField)
    {
        $ascending = new $class($defaultField, SortCriteria::DIRECTION_ASCENDING);

        $this->assertTrue($ascending->isSortedBy($defaultField));
        $this->assertSame(SortCriteria::DIRECTION_DESCENDING, $ascending->getToggledDirection($defaultField));

        $descending = new $class($defaultField, SortCriteria::DIRECTION_DESCENDING);

        $this->assertSame(SortCriteria::DIRECTION_ASCENDING, $descending->getToggledDirection($defaultField));
    }

    /**
     * @dataProvider criteriaProvider
     */
    public function testItIsReadFromTheQueryString($class, $defaultField)
    {
        $fields = array_keys(constant($class . '::FIELDS'));
        $other = $fields[count($fields) - 1];

        $criteria = $class::fromRequest(Request::create('/?sort=' . $other . '&direction=desc'));

        $this->assertInstanceOf($class, $criteria);
        $this->assertSame($other, $criteria->getField());
        $this->assertSame(SortCriteria::DIRECTION_DESCENDING, $criteria->getDirection());
    }

    /**
     * @dataProvider criteriaProvider
     */
    public function testAStaleBookmarkDoesNotBreakTheList($class, $defaultField, $defaultDirection)
    {
        $criteria = $class::fromRequest(Request::create('/?sort=nonsense&direction=up'));

        $this->assertSame($defaultField, $criteria->getField());
        $this->assertSame($defaultDirection, $criteria->getDirection());
    }

    /**
     * The fields name the getter they are read through, so a typo in the list
     * would only show when somebody sorts by that column.
     *
     * @dataProvider criteriaProvider
     */
    public function testEveryFieldIsReadThroughAGetterThatExists($class, $defaultField, $defaultDirection, $entry)
    {
        foreach (constant($class . '::FIELDS') as $field => $getter) {
            $this->assertTrue(
                method_exists($entry, $getter),
                sprintf('%s has no %s() for the field %s', $entry, $getter, $field)
            );
        }

        $this->assertTrue(
            method_exists($entry, constant($class . '::IDENTITY_GETTER')),
            sprintf('%s has no %s() to break ties with', $entry, constant($class . '::IDENTITY_GETTER'))
        );
    }

    /**
     * A query string carries arrays just as happily as it carries strings, and
     * an array is not a valid array key: reading one used to end the request
     * with a TypeError rather than falling back to the default.
     *
     * @dataProvider criteriaProvider
     */
    public function testAnArrayInTheQueryStringFallsBackToTheDefault($class, $defaultField, $defaultDirection)
    {
        $queries = ['sort[]=id', 'direction[]=asc', 'sort[]=id&direction[]=desc', 'sort[][]=deep'];

        foreach ($queries as $query) {
            $criteria = $class::fromRequest(Request::create('/?' . $query));

            $this->assertSame($defaultField, $criteria->getField(), $query);
            $this->assertSame($defaultDirection, $criteria->getDirection(), $query);
        }
    }

    /**
     * @dataProvider criteriaProvider
     */
    public function testTheFieldIsAlwaysOneOfTheWhitelistedGetters($class)
    {
        $hostile = ['getPayload', '__construct', '__destruct', 'getId
    public function criteriaProvider()evil', '../../etc/passwd', str_repeat('a', 5000)];

        foreach ($hostile as $field) {
            $criteria = new $class($field);

            $this->assertContains(
                $criteria->getFieldGetter(),
                constant($class . '::FIELDS'),
                'the getter escaped the whitelist'
            );
        }
    }

    public function criteriaProvider()
    {
        return [
            'jobs' => [JobCriteria::class, 'created', SortCriteria::DIRECTION_DESCENDING, Job::class],
            'workers' => [WorkerCriteria::class, 'id', SortCriteria::DIRECTION_ASCENDING, Worker::class],
            'queues' => [QueueCriteria::class, 'name', SortCriteria::DIRECTION_ASCENDING, Queue::class],
        ];
    }
}
