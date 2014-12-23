<?php
namespace Report\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\User,    Application\Entity\Address,    Application\Entity\Projects;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

use Application\Controller\AuthController;

class ReportController extends AuthController
{
    
    public function indexAction()
    {
        $this->setCaption('System Reports');
        
        $em = $this->getEntityManager();
        
        $qb = $em->createQueryBuilder();
        $qb
            ->select('r')
            ->from('Report\Entity\Report', 'r')
            ->join('r.group', 'g')
            ->orderBy('g.groupId', 'ASC');
            
        
        $query  = $qb->getQuery();
        $reports = $query->getResult();
        $reportsFiltered = array();
        foreach ($reports as $report) {
            if (empty($reportsFiltered[$report->getGroup()->getName()])) {
                $reportsFiltered[$report->getGroup()->getName()] = array(
                    'icon'=>$report->getGroup()->getIcon(),
                    'colour'=>$report->getGroup()->getColour(),
                    'data'=>array()
                );
            }
            $reportsFiltered[$report->getGroup()->getName()]['data'][] = array(
                $report->getName(),
                $report->getDescription(),
            );
        }

        $this->getView()->setVariable('groups', $reportsFiltered);
        return $this->getView();
    }
    
    public function viewAction() {
        $group = $this->params()->fromRoute('group', false);
        $report = $this->params()->fromRoute('report', false);
        
        if (empty($group)) {
            throw new \Exception('illegal group route');
        }
        
        if (empty($report)) {
            throw new \Exception('illegal report route');
        }
        
        
    }
    
         
}
