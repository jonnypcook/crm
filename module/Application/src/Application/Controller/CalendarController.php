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


class CalendarController extends AuthController
{
    
    public function indexAction()
    {
        $this->setCaption('Calendar');
        $formCalendarEvent = new \Application\Form\CalendarEventAddForm();
        $formCalendarEvent 
                ->setAttribute('action', '/calendar/addevent/')
                ->setAttribute('class', 'form-horizontal');

        $this->getView()
                ->setVariable('formCalendarEvent',$formCalendarEvent);
        return $this->getView();
    }
    
    public function addEventAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $postData = $this->params()->fromPost();
            $formCalendarEvent = new \Application\Form\CalendarEventAddForm();
            $formCalendarEvent->setInputFilter(new \Application\Form\CalendarEventAddFilter());
            $formCalendarEvent->setData($postData);
            if ($formCalendarEvent->isValid()) {
                $client = $this->getGoogle();

                $cal = new \Google_Service_Calendar($client);

                $event = new \Google_Service_Calendar_Event();
                $event->setSummary($formCalendarEvent->get('title')->getValue());
                
                if (!empty($formCalendarEvent->get('location')->getValue())) {
                    $event->setLocation($formCalendarEvent->get('location')->getValue());
                }
                
                $allDay = empty($formCalendarEvent->get('calStartTm')->getValue());
                if ($allDay) {
                    $tmS = strtotime($formCalendarEvent->get('calStartDt')->getValue());
                    $start = new \Google_Service_Calendar_EventDateTime();
                    $start->setDate(date('Y-m-d', $tmS));
                    $event->setStart($start);
                    $tmE = strtotime($formCalendarEvent->get('calEndDt')->getValue());
                    $end = new \Google_Service_Calendar_EventDateTime();
                    $end->setDate(date('Y-m-d', $tmE));
                    $event->setEnd($end);                    
                } else {
                    $tmS = strtotime($formCalendarEvent->get('calStartDt')->getValue().' '.$formCalendarEvent->get('calStartTm')->getValue());
                    $start = new \Google_Service_Calendar_EventDateTime();
                    $start->setDateTime(date('c', $tmS));
                    $event->setStart($start);
                    $tmE = strtotime($formCalendarEvent->get('calEndDt')->getValue().' '.$formCalendarEvent->get('calEndTm')->getValue());
                    $end = new \Google_Service_Calendar_EventDateTime();
                    $end->setDateTime(date('c', $tmE));
                    $event->setEnd($end);                    
                    
                }
                $createdEvent = $cal->events->insert($this->getUser()->getEmail(), $event); //Returns array not an object
                $data = array('info'=>array(
                    'id'=>$createdEvent->id,
                    'title'=>$formCalendarEvent->get('title')->getValue(),
                    'start'=>date('Y-m-d'.($allDay?'':' H:i'),$tmS),
                    'end'=>date('Y-m-d'.($allDay?'':' H:i'),$tmE),
                ));
            } else {
                $data = array('err'=>true, 'info'=>$formCalendarEvent->getMessages(), 'data'=>$postData);
            }
            
            
            //echo '<pre>', print_r($data, true), '</pre>';
            //echo '<pre>', print_r($evts, true), '</pre>';
        
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel($data);/**/
    
        
    }
    
    /**
     * main Dashboard action
     * @return \Zend\View\Model\ViewModel
     */
    public function eventListAction()
    {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            $start = $this->params()->fromQuery('start', false);
            $end = $this->params()->fromQuery('end', false);
        
            if (empty($start) || empty($end)) {
                throw new \Exception('missing parameters');
            }
            
            $start = strtotime($start);
            $end = strtotime($end);

            if ($start>$end) {
                throw new \Exception('invalid parameters');
            }

            // grab local config
            $client = $this->getGoogle();
            
            // calendar
            $cal = new \Google_Service_Calendar($client);
            $evts = $cal->events->listEvents('jonny.p.cook@8point3led.co.uk', array(
                'timeMin'=>date('c', $start),
                'timeMax'=>date('c', $end),
            ));
            
            if (!($evts instanceof \Google_Service_Calendar_Events)) {
                throw new Exception('no results');
            }
            
            $data = array();
            if ($evts->count()) {
                foreach ($evts as $event) {
                    if (!empty($event->getstart()->getdate())) {
                        $start = $event->getstart()->getdate();
                        $end = $event->getend()->getdate();
                    } else {
                        $start = $event->getstart()->getdatetime();
                        $end = $event->getend()->getdatetime();
                    }

                    $data[] = array (
                        'title'=>$event->getSummary(),
                        'start'=>$start,
                        'end'=>$end,
                        //'description'=>'this is a test desc'
                        //'url'=>'a url',
                    );/**/
                }
            }
            
            
            //echo '<pre>', print_r($data, true), '</pre>';
            //echo '<pre>', print_r($evts, true), '</pre>';
        
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel($data);/**/
    }
    
    
    
}
