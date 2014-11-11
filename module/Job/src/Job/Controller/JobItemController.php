<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Job\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\User,    Application\Entity\Address,    Application\Entity\Projects;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;


class JobitemController extends JobSpecificController
{
    
    public function indexAction()
    {
        $this->setCaption('Job Dashboard');
        
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT p.model, p.eca, pt.service, pt.name AS productType, s.ppu, '
                . 'SUM(s.quantity) AS quantity, '
                . 'SUM(s.ppu*s.quantity) AS price '
                . 'FROM Space\Entity\System s '
                . 'JOIN s.space sp '
                . 'JOIN s.product p '
                . 'JOIN p.type pt '
                . 'WHERE sp.project='.$this->getProject()->getProjectId().' '
                . 'GROUP BY s.product');
        $system = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        $query = $em->createQuery('SELECT count(d) '
                . 'FROM Project\Entity\DocumentList d '
                . 'WHERE '
                . 'd.project='.$this->getProject()->getProjectId().' AND '
                . 'd.category IN (1, 2, 3)'
                );
        $proposals = $query->getSingleScalarResult();
        
        
        $audit = $em->getRepository('Application\Entity\Audit')->findByProjectId($this->getProject()->getProjectId(), true, array(
            'max' => 8,
            'auto'=> true,
        ));
        
        $activities = $em->getRepository('Application\Entity\Activity')->findByProjectId($this->getProject()->getProjectId(), true, array(
            'max' => 8,
            'auto'=> true,
        ));

        $formActivity = new \Application\Form\ActivityAddForm($em, array(
            'projectId'=>$this->getProject()->getProjectId(),
        ));
        
        $formActivity
                ->setAttribute('action', '/activity/add/')
                ->setAttribute('class', 'form-nomargin');
        
        $contacts = $this->getProject()->getContacts();
        
        $this->getView()
                ->setVariable('contacts', $contacts)
                ->setVariable('proposals', $proposals)
                ->setVariable('formActivity', $formActivity)
                ->setVariable('user', $this->getUser())
                ->setVariable('audit', $audit)
                ->setVariable('activities', $activities)
                ->setVariable('system', $system);
        
		return $this->getView();
        
    }
    
    
    
    
    
}
