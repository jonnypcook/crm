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
            
            $googleService = $this->getGoogleService();
            
            if (!$googleService->hasGoogle()) {
                throw new \Exception ('the service is not enabled for this user');
            }
            
            $postData = $this->params()->fromPost();
            $formCalendarEvent = new \Application\Form\CalendarEventAddForm();
            $formCalendarEvent->setInputFilter(new \Application\Form\CalendarEventAddFilter());
            $formCalendarEvent->setData($postData);
            if ($formCalendarEvent->isValid()) {
                $config = array();
                
                // event location
                if (!empty($formCalendarEvent->get('location')->getValue())) {
                    $config['location'] = $formCalendarEvent->get('location')->getValue();
                }
                
                // event timings
                if (empty($formCalendarEvent->get('calStartTm')->getValue())) {
                    $config['allday'] = true;
                    $tmStart = strtotime($formCalendarEvent->get('calStartDt')->getValue());
                    $tmEnd = strtotime($formCalendarEvent->get('calEndDt')->getValue());
                } else {
                    $tmStart = strtotime($formCalendarEvent->get('calStartDt')->getValue().' '.$formCalendarEvent->get('calStartTm')->getValue());
                    $tmEnd = strtotime($formCalendarEvent->get('calEndDt')->getValue().' '.$formCalendarEvent->get('calEndTm')->getValue());
                }
                
                $data = $googleService->addCalendarEvent($formCalendarEvent->get('title')->getValue(), $tmStart, $tmEnd, $config);
                
                
            } else {
                $data = array('err'=>true, 'info'=>$formCalendarEvent->getMessages(), 'data'=>$postData);
            }
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
            
            $googleService = $this->getGoogleService();
            
            if (!$googleService->hasGoogle()) {
                throw new \Exception ('the service is not enabled for this user');
            }

            $data = $googleService->findCalendarEvents(array (
                'start' => strtotime($start),
                'end' => strtotime($end),
            ));
            
        
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel($data);/**/
    }
    
    
    
}
