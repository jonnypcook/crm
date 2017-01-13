<?php
namespace Project\Controller;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;

use Zend\Mvc\MvcEvent;

use Zend\View\Model\JsonModel;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;



class ProjectItemTelemetryController extends ProjectSpecificController
{
    public function onDispatch(MvcEvent $e) {
        $cid = (int) $this->params()->fromRoute('cid', 0);
        $pid = (int) $this->params()->fromRoute('pid', 0);

        if (empty($cid)) {
            return $this->redirect()->toRoute('clients');
        }

        if (empty($pid)) {
            return $this->redirect()->toRoute('clients');
        }

        if (!($project=$this->getEntityManager()->getRepository('Project\Entity\Project')->findByProjectId($pid, array('client_id'=>$cid)))) {
            return $this->redirect()->toRoute('client', array('id'=>$cid));
        }

        if (!$project->getLipProject()) {
            return $this->redirect()->toRoute('project', array('cid'=>$cid, 'pid'=>$pid));
        }

        return parent::onDispatch($e);
    }

    /**
     * @return \Application\Entity\LiteipProject
     */
    public function getLiteIpProject () {
        return $this->getProject()->getLipProject();
    }

    /**
     * get LiteIp Service
     * @return \Application\Service\LiteIpService
     */
    public function getLiteIpService() {
        return $this->getServiceLocator()->get('LiteIpService');
    }

    public function synchronizeProjectAction() {
        if (!$this->request->isXmlHttpRequest()) {
            throw new \Exception('illegal request type');
        }

        $this->getLiteIpService()->synchronizeProjectsData(true);

        return new JsonModel(array('error' => false));
    }


    /**
     * get liteip drawing image action
     * @return \Zend\Http\Response\Stream
     */
    public function drawingimageAction() {
        try
        {
            $drawingId = $this->params()->fromQuery( 'drawingId', false );

            $filename = $this->getLiteIpService()->findDrawingUrl($drawingId);

            if ($filename === false) {
                throw new \Exception('Could not find drawing');
            }

            if ( !file_exists( $filename ) )
            {
                throw new \Exception( 'file does not exist: ' . $filename);
            }

            $imageType = strtolower(preg_replace( '/^[\s\S]+[.]([^.]+)$/', '$1', $filename ));

            $response = new \Zend\Http\Response\Stream();
            $response->setStream( fopen( $filename, 'r' ) );
            $response->setStatusCode( 200 );

            $headers = new \Zend\Http\Headers();
            $headers->addHeaderLine( 'Content-Type', "image/{$imageType}" )
                ->addHeaderLine( 'Content-Length', filesize( $filename ) );

            $response->setHeaders( $headers );

            return $response;
        }
        catch ( \Exception $ex )
        {
            echo $ex->getMessage();
            exit; // just exit as file does not exist
        }
    }

    /**
     * @return JsonModel
     * @throws \Exception
     */
    public function refreshDevicesAction() {
        if (!$this->request->isXmlHttpRequest()) {
            throw new \Exception('illegal request type');
        }

        $drawingId = $this->params()->fromQuery('fDrawingId', false);

        if ($drawingId !== false) {
            $this->getLiteIpService()->synchronizeDevicesData($drawingId);
        }

        return new JsonModel(array('error' => false));
    }

    /**
     * list devices for liteip drawing
     * @return JsonModel
     * @throws \Exception
     */
    public function devicelistAction() {
        $em = $this->getEntityManager();

        if (!$this->request->isXmlHttpRequest()) {
            throw new \Exception('illegal request type');
        }

        $drawingId = $this->params()->fromQuery('fDrawingId', 1);
        $plotMode = $this->params()->fromQuery('plot', false);
        $queryBuilder = $em->createQueryBuilder();

        if ($plotMode) {
            $queryBuilder
                ->select('d')
                ->from('Application\Entity\LiteipDevice', 'd')
                ->where('d.drawing = :did')
                ->setParameter('did', $drawingId);

            $deviceData = array();
            $devices = $queryBuilder->getQuery()->getResult();

            foreach($devices as $device) {
                $deviceData[] = array(
                    $device->getDeviceID(),
                    $device->getDeviceSN(),
                    $device->IsIsE3(),
                    !empty($device->getStatus()) ? $device->getStatus()->isFault() : false,
                    !empty($device->getStatus()) ? $device->getStatus()->getName() : 'n\a',
                    !empty($device->getLastE3StatusDate()) ? $device->getLastE3StatusDate()->format('Y-m-d H:i:s') : '',
                    $device->getPosLeft(),
                    $device->getPosTop()
                );
            }

            return new JsonModel(array('error' => false, 'devices' => $deviceData));
        }

        $queryBuilder
            ->select('d')
            ->from('Application\Entity\LiteipDevice', 'd')
            ->where('d.drawing = :did')
            ->setParameter('did', $drawingId);



        /*
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */
        $devices = trim($this->params()->fromQuery('sSearch',''));
        if (!empty($devices)) {
            $devices = explode(',', preg_replace('/[,]+/', ',', str_replace(' ', ',',trim($devices))));
            $queryBuilder->andWhere($queryBuilder->expr()->in('d.DeviceID', '?1'))
                ->setParameter(1, $devices);
        }


        /*
         * Ordering
         */
        $aColumns = array('d.DeviceID','d.IsE3','d.status');
        $orderByP = $this->params()->fromQuery('iSortCol_0',false);
        $orderBy = array();
        if ($orderByP!==false)
        {
            for ( $i=0 ; $i<intval($this->params()->fromQuery('iSortingCols',0)) ; $i++ )
            {
                $j = $this->params()->fromQuery('iSortCol_'.$i);

                if ( $this->params()->fromQuery('bSortable_'.$j, false) == "true" )
                {
                    $dir = $this->params()->fromQuery('sSortDir_'.$i,'ASC');
                    if (is_array($aColumns[$j])) {
                        foreach ($aColumns[$j] as $ac) {
                            $orderBy[] = $ac." ".$dir;
                        }
                    } else {
                        $orderBy[] = $aColumns[$j]." ".($dir);
                    }
                }
            }

        }

        if (empty($orderBy)) {
            $orderBy[] = 'd.DeviceID ASC';
        }

        foreach ($orderBy as $ob) {
            $queryBuilder->add('orderBy', $ob);
        }

        /**/

        // Create the paginator itself
        $paginator = new Paginator(
            new DoctrinePaginator(new ORMPaginator($queryBuilder))
        );

        $length = $this->params()->fromQuery('iDisplayLength', 10);
        $start = $this->params()->fromQuery('iDisplayStart', 1);
        $start = (floor($start / $length)+1);


        $paginator
            ->setCurrentPageNumber($start)
            ->setItemCountPerPage($length);

        $data = array(
            "sEcho" => intval($this->params()->fromQuery('sEcho', false)),
            "iTotalDisplayRecords" => $paginator->getTotalItemCount(),
            "iTotalRecords" => $paginator->getcurrentItemCount(),
            "aaData" => array()
        );/**/


        foreach ($paginator as $page) {
            //$url = $this->url()->fromRoute('client',array('id'=>$page->getclientId()));
            $data['aaData'][] = array (
                '<a class="serial-trigger" data-device-serial="' . $page->getDeviceSN() . '">' . $page->getDeviceSN() . '</a>',
                $page->isIsE3() ? 'Yes' : 'No',
                empty($page->getStatus()) ? 'n\a' : $page->getStatus()->getName(),
                $page->getDrawing()->getDrawing(),
                $page->getDrawing()->getProject()->getProjectDescription()
            );
        }

        return new JsonModel($data);/**/
    }


    /**
     * site plan (liteip) action
     * @return \Zend\View\Model\ViewModel
     */
    public function siteplanAction()
    {
        $this->setCaption('Device Layout');

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('d')
            ->from('Application\Entity\LiteipDrawing', 'd')
            ->where('d.project=?1')
            ->andWhere('d.Activated=true')
            ->add('orderBy', 'd.Drawing ASC')
            ->setParameter(1, $this->getLiteIpProject()->getProjectID());

        $drawings = $qb->getQuery()->getResult();

        return $this->getView()->setVariable('drawings', $drawings);
    }

    /**
     * em report (liteip) action
     * @return \Zend\View\Model\ViewModel
     */
    public function emergencyAction()
    {
        $this->setCaption('Emergency Report');

        return $this->getView();
    }

    /**
     * emergency report
     * @return JsonModel
     */
    public function emergencyReportAction() {
        try {
            $em = $this->getEntityManager();
            $devicesPolled = 0;
            $errors = array();
            $warnings = array();
            $synchronize = $this->params()->fromQuery('synchronize', false);


            if (!$this->request->isXmlHttpRequest()) {
                throw new \Exception('illegal request type');
            }

            if (!empty($synchronize)) {
                $this->getLiteIpService()->synchronizeDevicesData(false, $this->getProject()->getLipProject()->getProjectID());
            }

            $now = new \DateTime('now');
            $qb = $em->createQueryBuilder();
            $qb->select('d')
                ->from('Application\Entity\LiteipDevice', 'd')
                ->innerJoin('d.drawing', 'dr')
                ->where('dr.project=?1')
                ->andWhere('d.IsE3=true')
                ->setParameter(1, $this->getProject()->getLipProject()->getProjectID());

            $devices = $qb->getQuery()->getResult();

            foreach ($devices as $device) {

                $devicesPolled++;
                if ($device->getStatus()->isFault()) {
                    $errors[$device->getDrawing()->getDrawing(true)][] =  array(
                        $device->getDeviceID(),
                        $device->getDeviceSN(),
                        $device->getStatus()->getDescription(),
                        empty($device->getLastE3StatusDate()) ? '' : $device->getLastE3StatusDate()->format('d/m/Y H:i:s')
                    );
                }

                $timestamp = empty($device->getLastE3StatusDate()) ? 0 : $device->getLastE3StatusDate()->getTimestamp();
                $diff = $now->getTimestamp() - $timestamp;
                if(floor($diff / (60 * 60 * 24)) > 0) { // if not tested for 24 hours
                    $warnings[$device->getDrawing()->getDrawing(true)][] = array(
                        $device->getDeviceID(),
                        $device->getDeviceSN(),
                        floor($diff / (60 * 60 * 24)),
                        empty($device->getLastE3StatusDate()) ? '' : $device->getLastE3StatusDate()->format('d/m/Y H:i:s')
                    );
                }
            }

            return new JsonModel(array('error' => false, 'report' => array(
                'errors' => $errors,
                'warnings' => $warnings,
                'count' => array (
                    'errors' => count($errors),
                    'warnings' => count($warnings),
                    'devices' => $devicesPolled
                )
            )));
        } catch (\Exception $ex) {
            return new JsonModel(array('error' => true, 'info' => $ex->getMessage()));
        }


    }


}