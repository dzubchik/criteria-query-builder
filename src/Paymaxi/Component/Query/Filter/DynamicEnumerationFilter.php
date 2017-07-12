<?php
declare(strict_types=1);

namespace Paymaxi\Component\Query\Filter;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Paymaxi\Component\Query\Validator\Adapter\ArrayAdapter;
use Paymaxi\Component\Query\Validator\ValidatorInterface;

/**
 * Class DynamicEnumerationFilter
 *
 * @package Paymaxi\Component\Query\Filter
 */
final class DynamicEnumerationFilter extends AbstractFilter
{
    /** @var callable */
    private $dynamicFilter;

    /** @var string */
    private $delimiter;

    /**
     * DynamicFilter constructor.
     *
     * @param string $queryField
     * @param callable $dynamicFilter
     * @param string $delimiter
     */
    public function __construct(
        string $queryField,
        callable $dynamicFilter,
        string $delimiter = ','
    ) {
        parent::__construct($queryField);

        $this->dynamicFilter = $dynamicFilter;
        $this->delimiter = $delimiter;
    }

    /**
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator)
    {
        parent::setValidator(new ArrayAdapter($validator));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Criteria $criteria
     * @param $value
     *
     * @return void
     */
    public function apply(QueryBuilder $queryBuilder, Criteria $criteria, $value)
    {
        $values = explode($this->delimiter, $value);

        if (!$this->validate($values)) {
            $this->thrower->invalidValueForKey($this->getQueryField());
        }

        call_user_func($this->dynamicFilter, $queryBuilder, $values);
    }
}