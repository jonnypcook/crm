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
    
    public function mailAction() {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new \Exception('illegal message');
        }

        try {
            if (!$this->getRequest()->isPost()) {
                throw new \Exception('illegal method');
            }
            
            $data = array();
            
            $client = $this->getGoogle();
            $mail = new \Google_Service_Gmail($client);
            
            // thread = 148a35547e4fc5ac, 14468d20173c66dd
            //$data = $mail->users_messages->listUsersMessages('jonny.p.cook@8point3led.co.uk', array (
            $openThreads = $mail->users_threads->listUsersThreads($this->getUser()->getEmail(), array (
                'q'=>'label:inbox is:unread',
                'includeSpamTrash'=>'false',
                'maxResults'=>3,
            ));

            $data['count'] = $openThreads->resultSizeEstimate;
            $data['msg'] = array();
            foreach ($openThreads as $thread) {
                $messages = $mail->users_threads->get($this->getUser()->getEmail(), $thread->id, array('fields'=>'messages'));
                foreach ($messages as $message) {
                    $msg = array();
                    foreach ($message->payload->headers as $header) {
                        switch (strtolower($header->name)) {
                            case 'from':
                                $msg[strtolower($header->name)] = preg_replace('/[ ]*[<][^>]+[>]$/', '', $header->value);
                                break;
                            case 'subject':
                                $msg[strtolower($header->name)] = $header->value;
                                break;
                            case 'date':
                                $tm = strtotime($header->value);
                                
                                $hrs = floor((time()-$tm)/(60*60));
                                if ($hrs==0) {
                                    $tmMsg = 'Just Now';
                                } elseif ($hrs<24) {
                                    $tmMsg = $hrs.' hours ago';
                                } else {
                                    $tmMsg = floor($hrs/24).' days ago';
                                }
                                
                                $msg[strtolower($header->name)] = $tmMsg;
                                break;
                        }
                    }
                    if (!empty($msg)) {
                        $data['msg'][] = $msg;
                    }
                    break;
                }
            }            
            /**/
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    public function test1Action() { 
        
        $client = $this->getGoogle();
        $mail = new \Google_Service_Gmail($client);
        
        // thread = 148a35547e4fc5ac, 14468d20173c66dd
        //$data = $mail->users_messages->listUsersMessages('jonny.p.cook@8point3led.co.uk', array (
        $data = $mail->users_threads->listUsersThreads('jonny.p.cook@8point3led.co.uk', array (
            'q'=>'label:inbox is:unread',
            'includeSpamTrash'=>'false',
            'maxResults'=>3
        ));
        
        $threads = array();
        echo $data->resultSizeEstimate;
        foreach ($data as $thread) {
            $messages = $mail->users_threads->get('jonny.p.cook@8point3led.co.uk', $thread->id, array('fields'=>'messages'));
            foreach ($messages as $message) {
                foreach ($message->payload->headers as $header) {
                    if ($header->name=='Date') {
                        echo $header->name,' = ', $header->value,'<br />';;
                        echo date('Y-m-d H:i:s', strtotime($header->value));
                    }
                }
                break;
            }
            echo '<hr />';
        }
            die();
        
        
        //$obj = new \Google_Service_Gmail_ListThreadsResponse();
        //$obj->count();
        
        die();
        
        
        die('blocked - add cal ev below');
        $client = $this->getGoogle();
        
        $cal = new \Google_Service_Calendar($client);

        $event = new \Google_Service_Calendar_Event();
        $event->setSummary('Halloween');
        $event->setLocation('The Neighbourhood');
        $start = new \Google_Service_Calendar_EventDateTime();
        $start->setDateTime('2014-10-12T08:15:00+01:00');
        $event->setStart($start);
        $end = new \Google_Service_Calendar_EventDateTime();
        $end->setDateTime('2014-10-12T11:43:00+01:00');
        $event->setEnd($end);
        $createdEvent = $cal->events->insert('jonny.p.cook@8point3led.co.uk', $event); //Returns array not an object

        echo $createdEvent->id;        
        die('blocked');/**/
        try {
            // grab local config
            $client = $this->getGoogle();
            
            // calendar
            $cal = new \Google_Service_Calendar($client);
            $evts = $cal->events->listEvents('jonny.p.cook@8point3led.co.uk', array(
                'timeMin'=>'2014-09-01T00:00:00Z'
            ));
            
            echo '<pre>', print_r($evts, true), '</pre>';
            
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }/**/
        
        die('stop');
    }
    
    /**
     * main Dashboard action
     * @return \Zend\View\Model\ViewModel
     */
    public function indexAction()
    {
        $info = array();

        // find the number of active projects that a user has
        $dql = 'SELECT COUNT(p) FROM Project\Entity\Project p JOIN p.client c JOIN p.status s WHERE c.user = :uid AND s.job=0 AND s.halt=0';
        $q = $this->getEntityManager()->createQuery($dql);
        $q->setParameters(array('uid' => $this->getUser()->getUserId()));

        $info['activeProjects'] = $q->getSingleScalarResult();
        
        // find the number of active jobs that a user has
        $dql = 'SELECT COUNT(p) FROM Project\Entity\Project p JOIN p.client c JOIN p.status s WHERE c.user = :uid AND s.job=1 AND s.halt=0';
        $q = $this->getEntityManager()->createQuery($dql);
        $q->setParameters(array('uid' => $this->getUser()->getUserId()));

        $info['activeJobs'] = $q->getSingleScalarResult();
        
        // find the number of cancelled projects that a user has
        $dql = 'SELECT COUNT(p) FROM Project\Entity\Project p JOIN p.client c JOIN p.status s WHERE c.user = :uid AND p.cancelled=true';
        $q = $this->getEntityManager()->createQuery($dql);
        $q->setParameters(array('uid' => $this->getUser()->getUserId()));

        $info['cancelledProjects'] = $q->getSingleScalarResult();
        
        // find the number of clients that a user has
        $dql = 'SELECT COUNT(c) FROM Client\Entity\Client c WHERE c.user = :uid';
        $q = $this->getEntityManager()->createQuery($dql);
        $q->setParameters(array('uid' => $this->getUser()->getUserId()));

        $info['activeClients'] = $q->getSingleScalarResult();
        

        // find the number of cancelled projects that a user has
        $tm = mktime(0,0,0,date('m'), date('d')-14, date('Y'));
        $dql = 'SELECT COUNT(a) FROM Application\Entity\Activity a WHERE a.user = :uid AND a.startDt>=\''.date('Y-m-d H:i:s',$tm).'\'';
        $q = $this->getEntityManager()->createQuery($dql);
        $q->setParameters(array('uid' => $this->getUser()->getUserId()));

        
        // find last 3 projects modified
        $projects = $this->getEntityManager()->getRepository('Project\Entity\Project')->findByUserId($this->getUser()->getUserId(), false, array(
            'max' => 3,
            'auto'=> true,
        ));

        $info['activityCount'] = $q->getSingleScalarResult();
        
        $activities = $this->getEntityManager()->getRepository('Application\Entity\Activity')->findByUserId($this->getUser()->getUserId(), true, array(
            'max' => 8,
            'auto'=> true,
            'project' => true,
        ));

        $formActivity = new \Application\Form\ActivityAddForm($this->getEntityManager(), array());
        
        $formActivity
                ->setAttribute('action', '/dashboard/activity/')
                ->setAttribute('class', 'form-nomargin');
        
        $formCalendarEvent = new \Application\Form\CalendarEventAddForm();
        $formCalendarEvent 
                ->setAttribute('action', '/calendar/addevent/')
                ->setAttribute('class', 'form-nomargin');

        $this->getView()
                ->setVariable('projects', $projects)
                ->setVariable('info', $info)
                ->setVariable('activities', $activities)
                ->setVariable('user', $this->getUser())
                ->setVariable('formActivity', $formActivity)
                ->setVariable('formCalendarEvent', $formCalendarEvent)
                ;
        
        return $this->getView();
    }
    
    
    
}
