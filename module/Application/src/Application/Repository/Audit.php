<?php
namespace Application\Repository;
 
use Doctrine\ORM\EntityRepository;
use Application\Entity;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;


class Audit extends EntityRepository
{
    public function findByClientId($client_id, $array=false, array $params=array()) {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('u.username, u.avatar_name, u.email, u.forename, u.surname, '
                    . 'a.created, a.data, '
                    . 'at.name AS atName, at.icon, at.box, at.auditTypeId, '
                    . 'c.name AS cName, c.clientId, '
                    . 'p.name AS pName, p.projectId, '
                    . 's.name AS sName, s.spaceId, '
                    . 'd.name AS dName, '
                    . 'pr.model')
            ->from('Application\Entity\Audit', 'a')
            ->innerJoin('a.auditType', 'at')
            ->innerJoin('a.user', 'u')
            ->innerJoin('a.client', 'c')
            ->leftJoin('a.project', 'p')
            ->leftJoin('a.space', 's')
            ->leftJoin('a.documentCategory', 'd')
            ->leftJoin('a.product', 'pr')
            ->where('c.clientId = '.$client_id)
            ->orderBy('a.created', 'DESC')
            
                ;
        
        if (isset($params['max'])) {
            if (preg_match('/^[\d]+$/',$params['max'])) {
                $queryBuilder->setMaxResults($params['max']);
            }
        }
        
        if (isset($params['auto'])) {
            $queryBuilder->andWhere('at.auto = '.(empty($params['auto'])?0:1));
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
            ->select('u.username, u.avatar_name, u.email, u.forename, u.surname, '
                    . 'a.created, a.data, '
                    . 'at.name AS atName, at.icon, at.box, at.auditTypeId, '
                    . 'c.clientId, '
                    . 'p.name AS pName, p.projectId, '
                    . 's.name AS sName, s.spaceId, '
                    . 'd.name AS dName, '
                    . 'pr.model')
            ->from('Application\Entity\Audit', 'a')
            ->innerJoin('a.auditType', 'at')
            ->innerJoin('a.user', 'u')
            ->innerJoin('a.client', 'c')
            ->innerJoin('a.project', 'p')
            ->leftJoin('a.space', 's')
            ->leftJoin('a.documentCategory', 'd')
            ->leftJoin('a.product', 'pr')
            ->where('p.projectId = '.$project_id)
            ->orderBy('a.created', 'DESC')
            
                ;
        
        if (isset($params['max'])) {
            if (preg_match('/^[\d]+$/',$params['max'])) {
                $queryBuilder->setMaxResults($params['max']);
            }
        }
        
        if (isset($params['auto'])) {
            $queryBuilder->andWhere('at.auto = '.(empty($params['auto'])?0:1));
        }
        
        $query  = $queryBuilder->getQuery();
        
        if ($array===true) {
            return  $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        }
        
        return $query->getResult();

    }
    
    
    

}

