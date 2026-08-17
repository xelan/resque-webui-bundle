<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Dto;

use Symfony\Component\HttpFoundation\Request;

/**
 * The ordering of one of the lists.
 *
 * The field ends up selecting a getter and is taken from the query string, so
 * it is checked against the whitelist of the list in question. An unknown value
 * falls back to the default instead of being rejected, which keeps a stale
 * bookmark working.
 *
 * Subclasses describe their list through the FIELDS, NUMERIC_FIELDS,
 * IDENTITY_GETTER, DEFAULT_FIELD and DEFAULT_DIRECTION constants. Those cannot
 * be declared abstract on the PHP versions in use, so a subclass leaving one
 * out is only found once it is asked to sort. INVERTED_FIELDS is the exception:
 * it names what few fields run the other way round and defaults to none.
 *
 * @internal the lists of the bundle are rendered through this; it is not an
 *           extension point and may change without notice
 */
abstract class SortCriteria
{
    const DIRECTION_ASCENDING = 'asc';
    const DIRECTION_DESCENDING = 'desc';

    /**
     * The fields whose stored value runs opposite to what the column shows,
     * such as a start time behind a duration: the older the timestamp, the
     * longer the entry has been running. Sorting one of these orders by what
     * is on screen rather than by what is behind it.
     */
    const INVERTED_FIELDS = [];

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $direction;

    /**
     * Constructor.
     *
     * @param string $field     one of the FIELDS keys
     * @param string $direction one of the DIRECTION_* constants
     */
    public function __construct($field = null, $direction = null)
    {
        $fields = static::FIELDS;
        $directions = [self::DIRECTION_ASCENDING, self::DIRECTION_DESCENDING];

        // a query string carries arrays just as happily as it carries strings,
        // and an array is neither a valid array key nor a field of a list
        $isKnownField = is_string($field) && array_key_exists($field, $fields);
        $isKnownDirection = is_string($direction) && in_array($direction, $directions, true);

        $this->field = $isKnownField ? $field : static::DEFAULT_FIELD;
        $this->direction = $isKnownDirection ? $direction : static::DEFAULT_DIRECTION;
    }

    /**
     * Reads the ordering from the query string of a request.
     *
     * @param Request $request
     *
     * @return static
     */
    public static function fromRequest(Request $request)
    {
        return new static($request->query->get('sort'), $request->query->get('direction'));
    }

    /**
     * Returns the field the list is ordered by.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Returns the getter of the field the list is ordered by.
     *
     * @return string
     */
    public function getFieldGetter()
    {
        $fields = static::FIELDS;

        return $fields[$this->field];
    }

    /**
     * Returns the direction the list is ordered in.
     *
     * @return string
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Returns whether the list is ordered from the highest value downwards.
     *
     * @return bool
     */
    public function isDescending()
    {
        return $this->direction === self::DIRECTION_DESCENDING;
    }

    /**
     * Returns whether the values of the field are compared as numbers.
     *
     * @return bool
     */
    public function isNumericField()
    {
        return in_array($this->field, static::NUMERIC_FIELDS, true);
    }

    /**
     * Returns whether the values of the field run opposite to the column they
     * are shown in.
     *
     * @return bool
     */
    public function isInvertedField()
    {
        return in_array($this->field, static::INVERTED_FIELDS, true);
    }

    /**
     * Returns whether the list is currently ordered by a field.
     *
     * @param string $field
     *
     * @return bool
     */
    public function isSortedBy($field)
    {
        return $this->field === $field;
    }

    /**
     * Returns the direction a column header has to link to: the opposite one
     * for the column in use, ascending for every other column.
     *
     * @param string $field
     *
     * @return string
     */
    public function getToggledDirection($field)
    {
        if (!$this->isSortedBy($field)) {
            return self::DIRECTION_ASCENDING;
        }

        return $this->isDescending() ? self::DIRECTION_ASCENDING : self::DIRECTION_DESCENDING;
    }

    /**
     * Orders the entries by the field of the criteria.
     *
     * Entries without a value for that field are always put last, no matter
     * which direction is asked for; an entry that never reached that stage is
     * of no interest at the top of the list. An inverted field is compared the
     * other way round, so that the order follows the column rather than the
     * value behind it. Entries that compare equal are
     * ordered by what identifies them, so that the result does not depend on
     * the sort implementation of the PHP version in use.
     *
     * @param object[] $entries
     *
     * @return object[]
     */
    public function sort(array $entries)
    {
        $getter = $this->getFieldGetter();
        $identity = static::IDENTITY_GETTER;
        $numeric = $this->isNumericField();
        $inverted = $this->isInvertedField();
        $descending = $this->isDescending();

        usort($entries, function ($left, $right) use ($getter, $identity, $numeric, $inverted, $descending) {
            $leftValue = $left->{$getter}();
            $rightValue = $right->{$getter}();

            $leftIsEmpty = ($leftValue === null || $leftValue === '');
            $rightIsEmpty = ($rightValue === null || $rightValue === '');

            if ($leftIsEmpty || $rightIsEmpty) {
                if ($leftIsEmpty && $rightIsEmpty) {
                    return strcmp((string) $left->{$identity}(), (string) $right->{$identity}());
                }

                return $leftIsEmpty ? 1 : -1;
            }

            $result = $numeric
                ? ((float) $leftValue <=> (float) $rightValue)
                : strcmp((string) $leftValue, (string) $rightValue);

            // the column shows the opposite of what is compared here, so the
            // outcome is turned around before the direction is applied to it
            if ($inverted) {
                $result = -$result;
            }

            if ($result === 0) {
                return strcmp((string) $left->{$identity}(), (string) $right->{$identity}());
            }

            return $descending ? -$result : $result;
        });

        return $entries;
    }
}
