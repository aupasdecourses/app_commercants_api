<?php
namespace AppBundle\Repository;

use AutoBundle\Repository\AbstractRepository;

class ProductRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected function searchQuery($search, $qb)
    {
        $qb->andWhere('magic.name LIKE \'%' . str_replace('\'', '\'\'', $search) . '%\'');
    }
}
