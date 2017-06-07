<?php

declare(strict_types=1);

namespace Paymaxi\Component\Query;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Paymaxi\Component\Query\Filter\FilterInterface;
use Paymaxi\Component\Query\Sort\SortInterface;

/**
 * Class CriteriaQueryBuilder
 */
final class CriteriaQueryBuilder implements CriteriaQueryBuilderInterface
{
    /** @var SortInterface[] */
    protected $sortedFields = [];

    /** @var FilterInterface[] */
    private $filters = [];

    /** @var Criteria */
    private $criteria;

    /** @var array */
    private $filterParams;

    /** @var array */
    private $sortingFields;

    /** @var array */
    private $defaultOrder;

    /** @var \Doctrine\ORM\QueryBuilder */
    private $qb;

    /** @var bool */
    private $cached = false;

    /**
     * @param EntityRepository $repository
     * @param array $filterParams
     * @param array $sortingFields
     *
     * @internal param ApiManagerInterface $manager
     */
    public function __construct(EntityRepository $repository, array $filterParams, array $sortingFields)
    {
        $this->qb = $repository->createQueryBuilder('e');
        $this->criteria = new Criteria();
        $this->filterParams = $filterParams;
        $this->sortingFields = $sortingFields;
    }

    /**
     * @param FilterInterface $filter
     *
     * @return $this
     */
    public function addFilter(FilterInterface $filter)
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @param SortInterface $sort
     *
     * @return $this
     */
    public function addSorting(SortInterface $sort)
    {
        $this->sortedFields[] = $sort;

        return $this;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQb()
    {
        if (!$this->cached) {
            $this->cached = true;
            $this->qb->addCriteria($this->buildCriteria());
        }

        return $this->qb;
    }

    /**
     * @return Criteria
     */
    private function buildCriteria()
    {
        $this->applyFilters();
        $this->applySorting();

        return $this->criteria;
    }

    private function applyFilters()
    {
        foreach ($this->filterParams as $field => $value) {
            foreach ($this->filters as $filter) {
                if ($filter->supports($field)) {
                    $filter->apply($this->qb, $this->criteria, $value);
                }
            }
        }
    }

    private function applySorting()
    {
        if (0 === count($this->sortingFields)) {
            $this->criteria->orderBy($this->getDefaultOrder());
        }

        foreach ($this->sortingFields as $field => $order) {
            foreach ($this->sortedFields as $sort) {
                if ($sort->supports($field)) {
                    $sort->apply($this->qb, $this->criteria, $order);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getDefaultOrder()
    {
        return !empty($this->defaultOrder) ? $this->defaultOrder : ['created' => 'DESC'];
    }

    /**
     * @param array $defaultOrder
     */
    public function setDefaultOrder(array $defaultOrder)
    {
        $this->defaultOrder = $defaultOrder;
    }
}
