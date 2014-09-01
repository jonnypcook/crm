<?php

namespace Product\Repository;
 
use Doctrine\ORM\EntityRepository;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;


class Product extends EntityRepository
{
    public function findByType($typeId, $params=array()) {
        // First get the EM handle
        // and call the query builder on it
        $query = $this->_em->createQuery("SELECT ".
                "p "
                . "FROM Product\Entity\Product p "
                . "WHERE p.type = {$typeId}");
        
        if (!empty($params['array'])) {
            return  $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        }
        
        return $query->getResult();
    }
    
    
}

