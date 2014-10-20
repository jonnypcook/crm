<?php

namespace Contact\Repository;
 
use Doctrine\ORM\EntityRepository;
use Contact\Entity;
 
class Contact extends EntityRepository
{
    public function findByClientId($client_id, $array=false, array $params=array()) {
        // First get the EM handle
        // and call the query builder on it
        $query = $this->_em->createQuery("SELECT c "
                . "FROM Contact\Entity\Contact c "
                . "JOIN c.client cl "
                . "WHERE cl.clientId=".$client_id." "
                . "ORDER BY c.forename ASC, c.surname ASC");
        
        if ($array===true) {
            return  $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        }
        
        return $query->getResult();
    }

}

