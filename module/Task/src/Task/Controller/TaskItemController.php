<?php
namespace Task\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\User,    Application\Entity\Address,    Application\Entity\Projects;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

use Application\Controller\AuthController;

class TaskitemController extends AuthController
{
    
    /**
     *
     * @var Task\Entity\Task
     */
    private $task;
    
    public function getTask() {
        return $this->task;
    }

    public function setTask(\Task\Entity\Task $task) {
        $this->task = $task;
        $this->getView()->setVariable('task', $task);
        return $this;
    }

        
    public function onDispatch(MvcEvent $e) {
        $tid = (int) $this->params()->fromRoute('tid', 0);

        if (empty($tid)) {
            return $this->redirect()->toRoute('task');
        } 
        
        $task = $this->getEntityManager()->find('Task\Entity\Task', $tid);

        if (!($task instanceof \Task\Entity\Task)) {
            return $this->redirect()->toRoute('task');
        }
        
        if (!$this->isGranted('admin.all')) {
            // check to see if user can view this item
            if ($task->getUser()->getUserId() != $this->identity()->getUserId()) {
                $passed = false;
                foreach ($task->getUsers() as $user) {
                    if ($user->getUserId() == $this->identity()->getUserId()) {
                        $passed = true;
                        break;
                    }
                }
                if (!$passed) {
                    return $this->redirect()->toRoute('tasks');
                }
            }
        }
        
        $this->setTask($task);
        
        return parent::onDispatch($e);
    }
    
    public function indexAction()
    {
        $this->setCaption('Task Item: #'.str_pad($this->getTask()->getTaskId(), 5, "0", STR_PAD_LEFT));
        
        $formAddActivityNote = new \Task\Form\AddTaskActivityForm();
        $formAddActivityNote
                //->setAttribute('class', 'form-horizontal')
                ->setAttribute('action', '/task-'.$this->getTask()->getTaskId().'/addactivity/');
        
        $this->getView()->setVariable('formAddActivityNote', $formAddActivityNote);
        
        return $this->getView();
    }
    
    public function addActivityAction() {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception('illegal request format');
            }
            
            $post = $this->params()->fromPost();
            
            $activity = new \Application\Entity\Activity();

            $form = new \Task\Form\AddTaskActivityForm();
            $form->setInputFilter(new \Task\Form\AddTaskActivityFilter());
            
            // add additional items
            $form->setData($post);
            
            if ($form->isValid()) {
                $post['activityType'] = 20;
                $hydrator = new DoctrineHydrator($this->getEntityManager(),'Application\Entity\Activity');
                $hydrator->hydrate($post, $activity);
                
                $startDt = new \DateTime();
                $date = new \DateTime();
                $endDt = $date->setTimestamp($startDt->getTimestamp()+($form->get('duration')->getValue()*60)); 
                
                $activity
                    ->setStartDt($startDt)
                    ->setEndDt($endDt)
                    ->setUser($this->getUser())
                    ->setClient($this->getTask()->getClient())
                    ->setProject($this->getTask()->getProject())
                ;/**/
                
                $this->getEntityManager()->persist($activity);
                
                $this->getTask()->getActivities()->add($activity);
                $this->getEntityManager()->persist($this->getTask());
                
                $this->getEntityManager()->flush();
                
                $data = array('err'=>false);
                
                $this->flashMessenger()->addMessage(array(
                    'The task activity has been added successfully.', 'Success!'
                ));
            } else {
                $data = array('err'=>true, 'info'=>$form->getMessages());
            }/**/
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?($dropzone?array():array('err'=>true)):$data);/**/
    }
    
    function completeAction() {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception('illegal request format');
            }
            
            $activity = new \Application\Entity\Activity();
            $post['activityType'] = 21; // task completed
            $hydrator = new DoctrineHydrator($this->getEntityManager(),'Application\Entity\Activity');
            $hydrator->hydrate($post, $activity);
                
            $startDt = new \DateTime();
            $date = new \DateTime();
            $endDt = $date->setTimestamp($startDt->getTimestamp()+(5*60)); 
                
            $activity
                ->setStartDt($startDt)
                ->setEndDt($endDt)
                ->setUser($this->getUser())
                ->setClient($this->getTask()->getClient())
                ->setProject($this->getTask()->getProject())
            ;/**/
            
            $hydrator = new DoctrineHydrator($this->getEntityManager(),'Task\Entity\Task');
            $hydrator->hydrate(array('taskStatus'=>3, 'progress'=>100), $this->getTask());
            
                
            $this->getEntityManager()->persist($activity);
            $this->getTask()->getActivities()->add($activity);
            $this->getEntityManager()->persist($this->getTask());

            $this->getEntityManager()->flush();

            $data = array('err'=>false);

            $this->flashMessenger()->addMessage(array(
                'The task activity has been marked as completed.', 'Success!'
            ));
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?($dropzone?array():array('err'=>true)):$data);/**/
    }
    
     
}
