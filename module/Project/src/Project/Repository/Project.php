<?php

namespace Project\Repository;
 
use Doctrine\ORM\EntityRepository;
use Project\Entity;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;


class Project extends EntityRepository
{
    public function findByProjectId($project_id, array $params=array()) {
        $project = $this->_em->find('Project\Entity\Project', $project_id);
        if (!($project instanceof \Project\Entity\Project)) {
            return false;
        }
        
        // check the client_id for matches
        if (!empty($params['client_id'])) {
            if ($params['client_id'] != $project->getClient()->getClientId()) {
                return false;
            }
        }
        
        return $project;
    }

    public function findByClientId($clientId) {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('p')
            ->from('Project\Entity\Project', 'p')
            ->join('p.status', 's')
            ->where('p.client=?1')
            ->setParameter(1, $clientId);
        
        $query  = $qb->getQuery();      
        return $query->getResult();
    } 
    
    public function findPaginateByClientId($client_id, $length=10, $start=1, array $params=array()) {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('p')
            ->from('Project\Entity\Project', 'p')
            ->join('p.status', 's')
            ->where('p.client=?1')
            ->setParameter(1, $client_id);
        
        /**
         * check for project or job parameter
         */
        
        if (!empty($params['project'])) {
            $queryBuilder
                ->andWhere('s.job=0')
                ->andWhere('p.cancelled!=1')
                ->andWhere('s.weighting<1');
        }  elseif (!empty($params['job'])) {
            $queryBuilder
                ->andWhere('(s.job=1) OR (s.job=0 AND s.weighting=1 AND s.halt=1)');
        }
        

        /* 
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            if (preg_match('/^[\d]+$/', trim($keyword))) {
                $queryBuilder->andWhere('p.projectId LIKE :pid')
                ->setParameter('pid', '%'.$keyword.'%');
            } else {
                $queryBuilder->andWhere('p.name LIKE :name')
                ->setParameter('name', '%'.trim(preg_replace('/[*]+/','%',$keyword),'%').'%');
            }
        }        
        
        /*
         * Ordering
         */
        if (!empty($params['orderBy'])) {
            foreach ($params['orderBy'] as $name=>$dir) {
                switch($name){
                    case 'id':
                        $queryBuilder->add('orderBy', 'p.projectId '.$dir);
                        break;
                    case 'name':
                        $queryBuilder->add('orderBy', 'p.name '.$dir);
                        break;
                    case 'status':
                        $queryBuilder->add('orderBy', 's.statusId '.$dir);
                        break;
                }
            }
        }
        
        /**/  
        // Create the paginator itself
        $paginator = new Paginator(
            new DoctrinePaginator(new ORMPaginator($queryBuilder))
        );

        $start = (floor($start / $length)+1);
        
        
        $paginator
            ->setCurrentPageNumber($start)
            ->setItemCountPerPage($length);
        
        return $paginator;
        
    } 
    
    
    public function findByUserId($user_id, $array=false, array $params=array()) {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('p')
            ->from('Project\Entity\Project', 'p')
            ->innerJoin('p.client', 'c')
            ->innerJoin('p.status', 's')
            ->where('c.user = '.$user_id)
            ->orderBy('p.created', 'DESC')
            
                ;
        
        if (isset($params['project'])) {
            if ($params['project']==true) {
                $queryBuilder
                        ->andWhere('s.job=0')
                        ->andWhere('s.halt=0')
                        ->andWhere('p.cancelled=false');
            }
        }
        
        
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

