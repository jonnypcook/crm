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
    
    public function test1Action() {
        try {
            $config = $this->getServiceLocator()->get('Config');
            
            $client = new \Google_Client();
            $client->setAccessToken($this->getUser()->getToken_access());
            
            if ($client->isAccessTokenExpired()) {
                die('access token expired');
            }
            
            
            // We got an access token, let's now get the user's details
            $plus = new \Google_Service_Oauth2($client);
            $me = $plus->userinfo_v2_me->get();
            
            // calendar
            $cal = new \Google_Service_Calendar($client);
            $evts = $cal->events->listEvents('jonny.p.cook@8point3led.co.uk', array(
                'timeMin'=>'2014-09-01T00:00:00Z'
            ));
            
            echo '<pre>', print_r($evts, true), '</pre>';
            
        } catch (\Exception $ex) {

        }
        
        die('stop');
    }
    
    /**
     * main Dashboard action
     * @return \Zend\View\Model\ViewModel
     */
    public function indexAction()
    {
        /*try {
            $config = $this->getServiceLocator()->get('Config');
            $token = new \League\OAuth2\Client\Token\AccessToken(array(
                'access_token'=>'ya29.fABkp6YUzEFuqrsKOTr3FKPKPXGxsZkvqEF0pwTMi8D6vjf-ifo3hUry',
            ));

        
        $provider = new \League\OAuth2\Client\Provider\Google($config['oagoogle']['provider']+array('scopes'=>array(
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/userinfo.email',            
            'https://www.googleapis.com/auth/calendar',
            'https://www.googleapis.com/auth/calendar.readonly',
        )));
        $userDetails = $provider->getUserDetails($token);
        
        print_r($userDetails);
        }catch(\Exception $e) {
            echo $e->getMessage();
        }
        die();
        /*
        try {
            $client = new \Google_Client();
            //$client->setApplicationName("Client_Library_Examples");
            //$client->setDeveloperKey("AIzaSyCfXumYi3zTmnpT06D13zaewG86H_sr-ok");
            $client->setAccessToken(json_encode(array('access_token'=>'ya29.fABkp6YUzEFuqrsKOTr3FKPKPXGxsZkvqEF0pwTMi8D6vjf-ifo3hUry')));
            if ($client->isAccessTokenExpired()) {
                echo 'Access Token Expired'; // Debug
                die();
            }
            $service = new \Google_Service_Calendar($client);
            $results = $service->events->listEvents('jonny.p.cook@8point3led.co.uk');

            $this->debug()->dump($results);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die('<br />error');
        }

/*League\OAuth2\Client\Token\AccessToken Object
(
    [accessToken] => ya29.ewCBK2dj2MNpPt4wuTlyHuEI0GRXub4zhT5Eov1UV6c_39iSWKeQuAUc
    [expires] => 1410293617
    [refreshToken] => 
    [uid] => 
)        
        die('<BR>END');/**/
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

        $info['activityCount'] = $q->getSingleScalarResult();
        
        $activities = $this->getEntityManager()->getRepository('Application\Entity\Activity')->findByUserId($this->getUser()->getUserId(), true, array(
            'max' => 8,
            'auto'=> true,
        ));

        $formActivity = new \Application\Form\ActivityAddForm($this->getEntityManager(), array());
        
        $formActivity
                ->setAttribute('action', '/dashboard/activity/')
                ->setAttribute('class', 'form-nomargin');

        $this->getView()
                ->setVariable('info', $info)
                ->setVariable('activities', $activities)
                ->setVariable('user', $this->getUser())
                ->setVariable('formActivity', $formActivity);
        
        return $this->getView();
    }
    
    
    
}
