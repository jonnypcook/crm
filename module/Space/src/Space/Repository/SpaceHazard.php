<?php

namespace Space\Repository;
 
use Doctrine\ORM\EntityRepository;
use Space\Entity;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;


class SpaceHazard extends EntityRepository
{
    public function findBySpaceId($space_id, $array = false, array $config=array()) {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('sh.location, h.name, h.hazardId')
            ->from('Space\Entity\SpaceHazard', 'sh')
            ->innerJoin('sh.hazard', 'h')
            ->where("sh.space = {$space_id}");
      
        
        $query  = $queryBuilder->getQuery();
        
        if ($array===true) {
            return  $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        }
        
        return $query->getResult();
    }

}

