<?php
namespace AutoBundle\Repository;

use AutoBundle\Helper\Paginator;

use Doctrine\ORM\EntityRepository;

/**
 * Magic Repository
 */
abstract class AbstractRepository extends EntityRepository
{
    /** @var   array  Table field name aliases, defined as aliasFieldName => actualFieldName */
    protected $aliasFields = [];

    /** @var  Paginator */
    protected $paginator;

    /** @var array Array alreay join table */
    protected $withs = [];

    protected $orderWithjoin = null;

    /**
     * Get the real name of a field name based on its alias. If the field is not aliased $alias is returned
     *
     * @param   string $alias The field to get an alias for
     *
     * @return  string  The real name of the field
     */
    public function getFieldAlias($alias)
    {
        if (array_key_exists($alias, $this->aliasFields)) {
            return $this->aliasFields[$alias];
        } else {
            return $alias;
        }
    }

    /**
     * Search, filter, order and create the pagination
     *
     * @param string $search
     * @param array  $filters
     * @param array  $orderBy
     * @param int    $limit
     * @param int    $offset
     * @param mixed  $only
     *
     * @return array
     */
    public function searchAndfindBy($search = null, $filters = [], $orderBy = [], $limit = null, $offset = null, $only = null)
    {
        $qb = $this->createQueryBuilder('magic');

        if ($only) {

            if (!is_array($only)) {
                $only = [$only];
            }

            $qb->select('magic.'.implode(', magic.', $only));
        }

        if (!empty($search['value'])) {
            $utf8 = [
                '/[áàâãªä]/u' => 'a',
                '/[ÁÀÂÃÄ]/u'  => 'A',
                '/[ÍÌÎÏ]/u'   => 'I',
                '/[íìîï]/u'   => 'i',
                '/[éèêë]/u'   => 'e',
                '/[ÉÈÊË]/u'   => 'E',
                '/[óòôõºö]/u' => 'o',
                '/[ÓÒÔÕÖ]/u'  => 'O',
                '/[úùûü]/u'   => 'u',
                '/[ÚÙÛÜ]/u'   => 'U',
                '/ç/'         => 'c',
                '/Ç/'         => 'C',
                '/ñ/'         => 'n',
                '/Ñ/'         => 'N',
            ];

            $val = preg_replace(array_keys($utf8), array_values($utf8), $search['value']);
            $val = strtolower($val);

            switch ($search['type']) {
                case 'startWith':
                    $val = $val.'%';
                    break;
                case 'endWith'  :
                    $val = '%'.$val;
                    break;
                case 'exactWord':
                    $val = $val;
                    break;
                case 'contains' :
                    $val = '%'.$val.'%';
                    break;
                default         :
                    $val = '%'.$val.'%';
            }

            $this->searchQuery($val, $qb);
        }

        $this->filtersQuery($filters, $qb);

        if ((isset($offset)) && (isset($limit))) {
            $this->paginator = new Paginator($qb, $limit, $offset);

            if ($limit > 0) {
                $qb->setFirstResult($offset);
                $qb->setMaxResults($limit);
            }
        }

        foreach ($orderBy as $key => $value) {
            if (isset($this->orderWithjoin) && array_key_exists($key, $this->orderWithjoin)) {
                $qb->add('orderBy', $this->orderWithjoin[$key].' '.$value);
            } else {
                $qb->add('orderBy', 'magic.'.$key.' '.$value);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string                     $search
     * @param \Doctrine\ORM\QueryBuilder $qb
     *
     * @return void
     */
    protected function searchQuery($search, $qb)
    {
        //
    }

    /**
     * @param                            $filters
     * @param \Doctrine\ORM\QueryBuilder $qb
     *
     * @return void
     */
    protected function filtersQuery($filters, $qb)
    {
        if (!empty($filters)) {
            foreach ($filters as $name => $filter) {
                if (is_array($filter)) {
                    $qb->andWhere('magic.'.$name.' IN ('.implode(',', $filter).')');
                } else {
                    $qb->andWhere('magic.'.$name.' = \''.$filter.'\'');
                }
            }
        }
    }

    /**
     * Manage Relation +1, mainly used for filters
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string                     $relation
     * @param string|null                $alias
     * @param string|null                $groupBy
     */
    protected function with($qb, $relation, $alias = null, $groupBy = null)
    {
        if (!$alias) {
            //TODO: generate alias based on relation name (eg. magic.revisions -> revisions or magicRevisions)
        }

        if (isset($this->withs[$alias])) {
            return;
        }

        $qb->leftJoin($relation, $alias);

        if (isset($groupBy)) {
            $qb->groupBy($groupBy);
        }

        $this->withs[$alias] = 1;
    }

    /**
     * @return Paginator
     */
    public function getPaginator()
    {
        if (!isset($this->paginator)) {
            $this->paginator = new Paginator($this->createQueryBuilder('magic'));
        }

        return $this->paginator;
    }

    /**
     * @return mixed
     */
    public function count()
    {
        $count = $this->createQueryBuilder('magic')
            ->select('count(magic.id)')
            ->setMaxResults(1)
            ->getQuery()->getSingleScalarResult();;

        return $count;
    }
}
