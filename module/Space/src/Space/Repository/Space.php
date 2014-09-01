<?php

namespace Space\Repository;
 
use Doctrine\ORM\EntityRepository;
use Space\Entity;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;


class Space extends EntityRepository
{
    public function findByProjectId($project_id, $params=array()) {
        $query = $this->_em->createQuery("SELECT s FROM Space\Entity\Space s JOIN s.project p WHERE p.projectId=".$project_id.(!empty($params['root'])?' AND s.root=1 ':'')." ORDER BY s.building ASC");
        return $query->getResult();
    }
    
    public function findByBuildingId($building_id, $project_id, $array=false) {
        // First get the EM handle
        // and call the query builder on it
        $deleted = !empty($array['deleted']);
        $query = $this->_em->createQuery("SELECT s FROM Space\Entity\Space s JOIN s.project p JOIN s.building b WHERE p.projectId=".$project_id." AND b.buildingId=".$building_id.($deleted?'':' AND s.deleted != true')." ORDER BY s.building ASC");

        if ($array===true) {
            return  $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        }
        
        return $query->getResult();
    }

}

