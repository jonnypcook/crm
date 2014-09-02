<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\User,    Application\Entity\Address,    Application\Entity\Projects;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

class DashboardController extends AuthController
{
    /**
     * activity add action
     * @return \Application\Controller\JsonModel
     * @throws \Exception
     */
    public function activityAction() {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new \Exception('illegal message');
        }

        try {
            if (!$this->getRequest()->isPost()) {
                throw new \Exception('illegal method');
            }

            $post = $this->getRequest()->getPost();
            
            $info=array();
            $errs = array();
            $args = array();
            
            // check message
            if (empty($post['note'])) {
                $errs['note'] = 'no note supplied';
            }
            
            // check type
            $activityType = $this->getEntityManager()->find('Application\Entity\ActivityType', empty($post['activityTypeId'])?500:$post['activityTypeId']);
            if (empty($activityType)) {
                $errs['activityTypeId'] = 'Illegal Activity Id';
            } else { // duration config
                if (!empty($post['startDt']) && !empty($post['startTm'])) {
                    $dtTmStr = $post['startDt'].' '.$post['startTm'];
                    $args['startDt'] = date_create_from_format('d/m/Y H:i', $dtTmStr);
                    
                    if (!empty($post['endDt']) && !empty($post['endTm'])) {
                        $dtTmStr = $post['endDt'].' '.$post['endTm'];
                        $args['endDt'] = date_create_from_format('d/m/Y H:i', $dtTmStr);
                    } else {
                        if (!isset($post['duration'])) {
                            $duration = $activityType->getMins();
                        } else {
                            $duration = (int)$post['duration'];
                        }
                        $date = new \DateTime();
                        $args['endDt'] = $date->setTimestamp($args['startDt']->getTimestamp()+($duration*60)); 
                    }
                    
                } else {
                    $args['startDt'] = new \DateTime();
                    if (!isset($post['duration'])) {
                        $duration = $activityType->getMins();
                    } else {
                        $duration = (int)$post['duration'];
                    }
                    
                    $date = new \DateTime();
                    $args['endDt'] = $date->setTimestamp($args['startDt']->getTimestamp()+($duration*60)); 
                }
                

            }
            
            // check for project/client specific ownership
            if (!empty($post['projectId'])) {
                $project = $this->getEntityManager()->find('Project\Entity\Project', $post['projectId']);
                if (empty($project)) {
                    $errs['projectId'] = 'Illegal Project Id';
                }
                $args['project']=$project->getProjectId();
                $args['client']=$project->getClient()->getClientId();
            } elseif (!empty($post['clientId'])) {
                $client = $this->getEntityManager()->find('Client\Entity\Client', $post['clientId']);
                if (empty($client)) {
                    $errs['clientId'] = 'Illegal Client Id';
                }
                $args['client']=$client->getClientId();
            } 
            
            if (empty($errs)) {
                $activity = $this->AuditPlugin()->activity($activityType->getActivityTypeId(), $this->getUser()->getUserId(), $post['note'], $args);
                $picture = $activity->getUser()->getPicture();
                $info['activity'] = array (
                    'id' => $activity->getActivityId(),
                    'type' => $activity->getActivityType()->getName(),
                    'start' => $activity->getStartDt()->format('g:ia, jS F Y'),
                    'end' => $activity->getEndDt()->format('g:ia, jS F Y'),
                    'duration' => ($activity->getEndDt()->getTimestamp()-$activity->getStartDt()->getTimestamp())/60,
                    'note' => $activity->getNote(),
                    'user' => ucwords($activity->getUser()->getForename().' '.$activity->getUser()->getSurname()),
                    'me' => ($activity->getUser()->getUserId()==$this->getUser()->getUserId())?true:false,
                    'picture'=>empty($picture)?'default':$picture,
                );
                
                $prj = $activity->getProject();
                $clt = $activity->getClient();
                if (!empty($prj)) {
                    $info['activity']['projectName']=$prj->getName();
                    $info['activity']['projectId']=$prj->getProjectId();
                    $info['activity']['clientId']=$prj->getClient()->getClientId();
                } elseif (!empty($clt)) {
                    $info['activity']['clientName']=$clt->getName();
                    $info['activity']['clientId']=$clt->getClientId();
                }
                
                $data = array('err'=>false, 'info'=>$info);
            } else {
                $data = array('err'=>true, 'info'=>$errs);
            }

            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    /**
     * main Dashboard action
     * @return \Zend\View\Model\ViewModel
     */
    public function indexAction()
    {
        //$service = $this->getServiceExample()->SomeFunctionNameHere();die('STOP');
        
        // Getting the view helper manager from the application service manager

        return new ViewModel();
    }
    
    
    
}
