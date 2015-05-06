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
            $reportsFiltered[$report->getGroup()->getName()]['data'][$report->getReportId()] = array(
                $report->getName(),
                $report->getDescription(),
            );
        }

        $this->getView()->setVariable('groups', $reportsFiltered);
        return $this->getView();
    }
    
    private function getReportData(\Report\Entity\Report $report, $options=array()) {
        $data = array();
        if ($report->getReportId()==5) {
            
            $sql = "SELECT 
c.`client_id`, p.`project_id`,
c.`name` as `cname`,
p.`name` as `pname`,
t1.`price`,
p.`propertyCount`,
ROUND(t1.`price`/p.`propertyCount`, 2) as `ppp`
FROM `Project` p 
INNER JOIN `Client` c ON c.`client_id` = p.`client_id`
INNER JOIN (
	SELECT SUM(ROUND((sys.`quantity` * sys.`ppu`)*(1-(p.`mcd` * pr.`mcd`)), 2)) AS `price`, p.`project_id`
	FROM `System` sys
    INNER JOIN `Product` pr ON pr.`product_id` = sys.`product_id`
	inner Join `Space` s on s.`space_id` = sys.`space_id`
	INNER JOIN `Project` p ON p.`project_id` = s.`project_id`
	WHERE s.`deleted`!=1 
	GROUP BY s.`project_id`
) t1 ON t1.`project_id` = p.`project_id` 
WHERE 
    p.`propertyCount`>1 AND 
    p.`propertyCount` IS NOT NULL AND
    p.`project_status_id`=1 
ORDER BY c.`client_id`, p.`client_id` ASC
";
            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            $stmt->execute();
            
            $data = $stmt->fetchAll();
            if (!empty($options['headers'])) {
                $tmp = array();
                $tmp[] = array('"Project Reference"', '"Client Name"', '"Project Name"', 'Value', '"Property Count"', '"Price Per Property"');
                foreach ($data as $item) {
                    $tmp[] = array (
                        $item['client_id'].'-'.$item['project_id'],
                        '"'.$item['cname'].'"',
                        '"'.$item['pname'].'"',
                        $item['price'],
                        $item['propertyCount'],
                        $item['ppp'],
                    );
                }
                
                return $tmp;
            }

        } else {
            throw new \Exception ('Unsupported report');
        }
        
        return $data;
    }
    
    public function downloadAction() {
        $group = $this->params()->fromRoute('group', false);
        $rid = $this->params()->fromRoute('report', false);
        
        if (empty($group)) {
            throw new \Exception('illegal group route');
        }
        
        if (empty($rid)) {
            throw new \Exception('illegal report route');
        }
        
        $report = $this->getEntityManager()->find('Report\Entity\Report', $rid);
        
        $data = $this->getReportData($report, array('headers'=>true));
        
        $filename = strtolower($report->getName()).' report.csv';
        
        $response = $this->prepareCSVResponse($data, $filename);
        
        return $response;
    }
    
    public function viewAction() {
        $group = $this->params()->fromRoute('group', false);
        $rid = $this->params()->fromRoute('report', false);
        
        if (empty($group)) {
            throw new \Exception('illegal group route');
        }
        
        if (empty($rid)) {
            throw new \Exception('illegal report route');
        }
        
        $report = $this->getEntityManager()->find('Report\Entity\Report', $rid);
        
        $data = $this->getReportData($report); 
        
        $this->getView()
            ->setVariable('report', $report)
            ->setVariable('partialScript', strtolower(preg_replace('/[ .-]/i', '', $report->getName())));
        
        
        
        $this->getView()->setVariable('data', $data);
        return $this->getView();
    }
    
         
}
