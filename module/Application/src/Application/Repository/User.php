<?php
namespace Application\Repository;
 
use Doctrine\ORM\EntityRepository;
use Application\Entity;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;


class User extends EntityRepository
{
    /**
     * find user by email address - used primarily for oAuth2
     * @param type $email
     * @param array $params
     * @return Application\Entity\User
     */
    public function findByEmail($email, array $params=array()) {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder
            ->select('u')
            ->from('Application\Entity\User', 'u')
            ->where('u.email=\''.$email.'\'')
            
                ;
        
        $query  = $queryBuilder->getQuery();
        
        return $query->getSingleResult();

    }
    
    
    

}

