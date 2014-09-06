<?php
namespace Application\Repository;
 
use Doctrine\ORM\EntityRepository;
use Application\Entity;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;


class Activity extends EntityRepository
{
    public function findByClientId($client_id, $array=false, array $params=array()) {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('u.username, u.avatar_name, u.email, u.forename, u.surname, u.userId, u.picture, '
                    . 'a.created, a.data, a.startDt, a.endDt, a.note, '
                    . 'at.name AS atName, at.activityTypeId, '
                    . 'c.clientId, c.name as cName, '
                    . 'p.name AS pName, p.projectId')
            ->from('Application\Entity\Activity', 'a')
            ->innerJoin('a.activityType', 'at')
            ->innerJoin('a.user', 'u')
            ->innerJoin('a.client', 'c')
            ->leftJoin('a.project', 'p')
            ->where('c.clientId = '.$client_id)
            ->orderBy('a.startDt', 'DESC')
            
                ;
        
        if (isset($params['max'])) {
            if (preg_match('/^[\d]+$/',$params['max'])) {
                $queryBuilder->setMaxResults($params['max']);
            }
        }
        
        $query  = $queryBuilder->getQuery();
        
        if ($array===true) {
            return  $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        }
        
        return $query->getResult();

    }
    
    public function findByUserId($user_id, $array=false, array $params=array()) {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('u.username, u.avatar_name, u.email, u.forename, u.surname, u.userId, u.picture, '
                    . 'a.created, a.data, a.startDt, a.endDt, a.note, '
                    . 'at.name AS atName, at.activityTypeId, '
                    . 'c.clientId, c.name as cName, '
                    . 'p.name AS pName, p.projectId')
            ->from('Application\Entity\Activity', 'a')
            ->innerJoin('a.activityType', 'at')
            ->innerJoin('a.user', 'u')
            ->leftJoin('a.client', 'c')
            ->leftJoin('a.project', 'p')
            ->where('a.user = '.$user_id)
            ->orderBy('a.startDt', 'DESC')
            
                ;
        
        if (isset($params['max'])) {
            if (preg_match('/^[\d]+$/',$params['max'])) {
                $queryBuilder->setMaxResults($params['max']);
            }
        }
        
        $query  = $queryBuilder->getQuery();
        
        if ($array===true) {
            return  $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        }
        
        return $query->getResult();

    }
        
    public function findByProjectId($project_id, $array=false, array $params=array()) {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('u.username, u.avatar_name, u.email, u.forename, u.surname, u.userId, u.picture, '
                    . 'a.created, a.data, a.startDt, a.endDt, a.note, '
                    . 'at.name AS atName, at.activityTypeId, '
                    . 'c.clientId, '
                    . 'p.name AS pName, p.projectId')
            ->from('Application\Entity\Activity', 'a')
            ->innerJoin('a.activityType', 'at')
            ->innerJoin('a.user', 'u')
            ->innerJoin('a.client', 'c')
            ->innerJoin('a.project', 'p')
            ->where('p.projectId = '.$project_id)
            ->orderBy('a.startDt', 'DESC')
            
                ;
        
        if (isset($params['max'])) {
            if (preg_match('/^[\d]+$/',$params['max'])) {
                $queryBuilder->setMaxResults($params['max']);
            }
        }
        
        $query  = $queryBuilder->getQuery();
        
        if ($array===true) {
            return  $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        }
        
        return $query->getResult();

    }
        
}

