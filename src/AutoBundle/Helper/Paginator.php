<?php
namespace AutoBundle\Helper;

use Doctrine\ORM\QueryBuilder;

class Paginator
{
	/** @var QueryBuilder  */
	protected $qb;

	/** @var int  */
	protected $limit;

	/** @var int  */
	protected $offset;

	/** @var int  */
	protected $totalCount;

	/** @var int  */
	protected $searchCount;

	/** @var int  */
	protected $currentPage;

	/** @var int  */
	protected $totalPages;

	/** @var array  */
	protected $options;

	/**
	 * @param QueryBuilder  $queryBuilder
	 * @param int           $limit
	 * @param int           $offset
	 * @param array         $options
	 */
	function __construct($queryBuilder, $limit = 20, $offset = 0, $options = [10, 20, 30, 50, 100, 500])
	{
		$this->qb = clone $queryBuilder;
		$this->qb->select('count(magic.id)');

		$this->limit  = $limit;
		$this->offset = $offset;

		$this->options = $options;

		$this->getTotalCount();
		$this->getSearchCount();
	}

	/**
	 * @param $limit
	 *
	 * @return $this
	 */
	public function setLimit($limit)
	{
		$this->limit = $limit;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * @param $offset
	 *
	 * @return $this
	 */
	public function setOffset($offset)
	{
		$this->offset = $offset;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * @return int|mixed
	 */
	public function getTotalCount()
	{
		if (!isset($this->totalCount))
		{
			$reset = clone $this->qb;
			$this->totalCount = $reset
				->resetDQLPart('join')
				->resetDQLPart('where')
				->resetDQLPart('groupBy')
				->getQuery()->getSingleScalarResult();
		}

		return $this->totalCount;
	}

	/**
	 * @return int|mixed
	 */
	public function getSearchCount()
	{
		if (!isset($this->searchCount))
		{
			$reset = clone $this->qb;
			$this->searchCount = $reset
				->resetDQLPart('groupBy')
				->getQuery()->getSingleScalarResult();
		}

		return $this->searchCount;
	}

	/**
	 * @return int
	 */
	public function getCurrentPage()
	{
		if (!$this->currentPage)
		{
			$this->currentPage = ($this->offset / $this->limit) + 1;
		}

		return $this->currentPage;
	}

	/**
	 * @return int
	 */
	public function getTotalPages()
	{
		if (!$this->totalPages)
		{
			if (($this->limit == 0) || ($this->limit > $this->searchCount))
			{
				$this->totalPages = 1;
			}
			else
			{
				$this->totalPages = ceil($this->searchCount / $this->limit);
			}
		}

		return $this->totalPages;
	}

	/**
	 * @param $options
	 *
	 * @return $this
	 */
	public function setOptions($options)
	{
		$this->options = $options;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}
}