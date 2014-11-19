<?php
namespace Project\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Entity\User; 
use Application\Controller\AuthController;

use Project\Form\SetupForm;
use Space\Form\SpaceCreateForm;

use Zend\Mvc\MvcEvent;

use Zend\View\Model\JsonModel;

use Zend\Http\Request;
use Zend\Http\Client;
use Zend\Stdlib\Parameters;

use DOMPDFModule\View\Model\PdfModel;

use Project\Service\DocumentService;

class ProjectitemdocumentController extends ProjectSpecificController
{
    
    public function __construct(DocumentService $ds) {
        parent::__construct();
        $this->setDocumentService($ds);
    }

    
    public function indexAction()
    {
        $this->setCaption('Document Generator');
        $bitwise = '(BIT_AND(d.compatibility, 1)=1 '.($this->getProject()->hasState(10)?'OR BIT_AND(d.compatibility, 4)=4':'').')';
        $query = $this->getEntityManager()->createQuery('SELECT d.documentCategoryId, d.name, d.description, d.config, d.partial, d.grouping FROM Project\Entity\DocumentCategory d WHERE d.active = true AND '.$bitwise.' ORDER BY d.grouping');
        $documents = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        $formEmail = new \Project\Form\DocumentEmailForm($this->getEntityManager());
        
        $this->getView()
                ->setVariable('formEmail', $formEmail)
                ->setVariable('documents', $documents);
        
		return $this->getView();
    }
    
    /**
     * action to get relevant form wizard config
     * @return \Zend\View\Model\JsonModel
     */
    public function wizardAction() {
        $categoryId = $this->params()->fromPost('documentId', false);
        if (empty($categoryId)) {
            throw new \Exception('Illegal reuquest');
        }
        // grab document
        $bitwise = '(BIT_AND(d.compatibility, 1)=1 '.($this->getProject()->hasState(10)?'OR BIT_AND(d.compatibility, 4)=4':'').')';
        $query = $this->getEntityManager()->createQuery('SELECT d.config FROM Project\Entity\DocumentCategory d WHERE d.active = true AND '.$bitwise.' AND d.documentCategoryId='.$categoryId);
        $category = $query->getSingleResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        if (empty($category)) {
            throw new \Exception('document does not exist or is incompatible');
        }
        
        $config = json_decode($category['config'], true);
        if (empty($config)) {
            $config = array();
        }

        $form = new \Project\Form\DocumentWizardForm($this->getEntityManager(), $this->getProject(), $config);
        
        // set defaults
        foreach ($config as $name => $value) {
            switch ($name) {
                case 'user':
                    $form->get('user')->setValue($this->getProject()->getClient()->getUser()->getUserId());
                    break;
            }
        }
        
        $htmlViewPart = new ViewModel();
        $htmlViewPart->setTerminal(true)
            ->setTemplate('project/projectitemdocument/wizard')
            ->setVariables(array(
               'form' => $form
            ));

        $htmlOutput = $this->getServiceLocator()
                         ->get('viewrenderer')
                         ->render($htmlViewPart);

        $jsonModel = new JsonModel();
        $jsonModel->setVariables(array(
            'form' => $htmlOutput,
            'err'  => false,
        ));

        return $jsonModel;
    }
    
    public function generateAction () {
        // check for documentId param
        $categoryId = $this->params()->fromQuery('documentId', false);
        
        if (empty($categoryId)) {
            throw new \Exception('Illegal request');
        }

        $inline = !empty($this->params()->fromQuery('documentInline', false));

        $data = $this->params()->fromQuery();
        
        $email = !empty($this->params()->fromQuery('email', false));
        if ($email) {
            try {
                $formEmail = new \Project\Form\DocumentEmailForm($this->getEntityManager());
                $formEmail->setInputFilter(new \Project\Filter\DocumentEmailFilter());
                $formEmail->setData($data);
                if (!$formEmail->isValid()) {
                    return new JsonModel(array('err'=>true, 'info'=>$formEmail->getMessages()));
                }
            } catch (\Exception $ex) {
                return new JsonModel(array('err'=>true, 'info'=>array('ex'=>$ex->getMessage())));
            }
        }
        
        $em = $this->getEntityManager();
        // grab document
        $bitwise = '(BIT_AND(d.compatibility, 1)=1 '.($this->getProject()->hasState(10)?'OR BIT_AND(d.compatibility, 4)=4':'').')';
        $query = $em->createQuery('SELECT d.documentCategoryId, d.location, d.name, d.description, d.config, d.partial FROM Project\Entity\DocumentCategory d WHERE d.active = true AND '.$bitwise.' AND d.documentCategoryId='.$categoryId);
        $category = $query->getSingleResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        if (empty($category)) {
            throw new \Exception('document does not exist or is incompatible');
        }
        
        $config = empty($category['config'])?array():json_decode($category['config'], true);
        $pdfVars = array(
            'resourcesUri' => getcwd().DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR,
            'project' => $this->getProject(),
            'footer' => array (
                'pages'=>true
            )
        );
        
        
        //{"con1":true,"usr1":true,"mdl1":false,"mdl2":true,"mdl3":false,"sur1":true,"model":true,"tac1":false,"autosave":true,"docsave":true,"quot":true,"adr1":true,"payterm":true}
        $form = new \Project\Form\DocumentWizardForm($em, $this->getProject(), $config);
        $form->setInputFilter(new \Project\Filter\DocumentWizardInputFilter($config));
        $form->setData($data);
        
        if (!$form->isValid()) {
            $this->debug()->dump($form->getMessages());
            throw new \Exception ('illegal configuration parameters');
        }
        
        $autoSave = false;
        foreach ($form->getData() as $name=>$value) {
            switch ($name) {
                case 'contact':
                    $pdfVars['contact'] = $em->find('Contact\Entity\Contact', $value);
                    break;
                case 'user':
                    $pdfVars['user'] = $em->find('Application\Entity\User', $value);
                    break;
                case 'dAddress':
                    $pdfVars['dAddress'] = $em->find('Contact\Entity\Address', $value);
                    break;
                case 'billstyle':
                    $pdfVars['billstyle'] = $value;
                    if ($pdfVars['billstyle']==4) { // important: in order to list the architectural elements individually as opposed to aggregated
                        if (($config['model'] & 2) != 2) {
                            $config['model']+=2;
                        }
                    }
                    break;
                case 'modelyears':
                    $pdfVars['modelyears'] = $value;
                    break;
                /*case 'autosave': 
                    $autoSave = (bool)$value;
                    break;/**/
                case 'AttachmentSections':
                    $pdfVars['attach'] = $value;
                    if (in_array('arch', $pdfVars['attach'])) {
                        if (($config['model'] & 2) != 2) {
                            $config['model']+=2;
                        }
                    }
                    break;
                default:
                    $pdfVars['form'][$name] = $value;
                    break;
            }
        }
        
        
        $pdfVars['form'] = $form->getData();
        

        if (empty($config['size'])) {
            $config['size'] = 'pdf';
        }
        
        if (empty($config['orientation'])) {
            $config['orientation'] = 'portrait';
        }
        
        $config['name'] = $category['name'].' '.date('Y-m-d H:i:s');

        //echo '<pre>', print_r($config, true), '</pre>'; die('<br />end');
        
        $pdf = new PdfModel();
        foreach ($config as $option=>$value) {
            switch ($option) {
                case 'checkpoint':
                    $save = $this->saveConfig();
                    $pdfVars['invoiceNo'] = $save->getSaveId();
                    break;
                case 'saveMode':
                    if (($value & 1) == 1) { // save on download
                        $autoSave = !$inline;
                    }
                    break;
                case 'name': // Triggers PDF download, automatically appends ".pdf" - this will not be inline
                    if ($inline) continue;
                    $pdf->setOption('filename', $value); 
                    break;
                case 'orientation':
                    if ($value=='landscape') {
                        $pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"
                    } 
                    break;
                case 'size':
                    $pdf->setOption('paperSize', $value); // Defaults to "8x11"
                    break;
                case 'model':
                    if (($value & 1)==1) {
                        $years = (!empty($pdfVars['modelyears'])?(int)$pdfVars['modelyears']:12);
                        $service = $this->getModelService()->payback($this->getProject(), $years);
                        $pdfVars['figures'] = $service['figures'];
                        $pdfVars['forecast'] = $service['forecast'];
                        //$this->debug()->dump($service['figures'], false);
                        //$this->debug()->dump($service['forecast']);
                    }
                    
                    if (($value & 2)==2) {
                        $service = $this->getModelService()->spaceBreakdown($this->getProject());
                        $pdfVars['breakdown'] = $service;
                    }
                    
                    if (($value & 4)==4) {
                        $billitems = $this->getModelService()->billitems($this->getProject());
                        $pdfVars['billitems'] = $billitems;
                    }

                    break;
                
                default:
                    $pdf->setOption($option, (string)$value); // Defaults to "8x11"
                    break;
            }
        }
        
        $pdf->setVariables($pdfVars);
        
        $pdf->setTemplate('project/projectitemdocument/'.$category['partial']);
        
        
        $this->AuditPlugin()->auditProject($inline?402:401, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId(), array(
            'documentCategory'=>$category['documentCategoryId']
        ));
        
        if ($autoSave || $email) {
            $pdfOutput = $this->getServiceLocator()
                             ->get('viewrenderer')
                             ->render($pdf);

            $dompdf = new \DOMPDF();
            $dompdf->load_html($pdfOutput);
            $dompdf->render();
            
            $route = array();
            if (!empty($category['location'])) {
                $route = explode('/', trim($category['location'], '/'));
            }
            
            $invoiceNo = empty($pdfVars['invoiceNo'])?false:$pdfVars['invoiceNo'];

            $this->documentService->setUser($this->getUser());
            $info = $this->documentService->saveDOMPdfDocument(
                $dompdf,
                array(
                    'filename' =>$config['name'],
                    'route' => $route,
                    'category' => $categoryId,
            ), $invoiceNo);/**/
            
            if ($email) {
                $googleService = $this->getGoogleService();
                $googleService->setProject($this->getProject());

                if (!$googleService->hasGoogle()) {
                    throw new \Exception ('account does not support emails');
                }
                
                $response = $googleService->sendGmail($formEmail->get('emailSubject')->getValue(), $formEmail->get('emailMessage')->getValue(), array ($formEmail->get('emailRecipient')->getValue()), array (
                    'attachment' => array ($info['file'])
                ));
                
                return new JsonModel(array('err'=>false, 'info'=>$response));
            } elseif ($inline) {
                $dompdf->stream('filename',array('Attachment'=>0));
            } else {
                $dompdf->stream($pdf->getOption('filename', 'download'));
            }
            
            exit;
        } else {
            return $pdf;
        }

    }
    
    
    public function viewerAction()
    {
        $this->setCaption('Document Viewer');
        // Note: we use bitwise comparison on the compatibility field: (1=project, 2=job, 4=post survey project, 8=images, 16=generated)
        $query = $this->getEntityManager()->createQuery('SELECT d.documentCategoryId, d.name, d.description, d.location FROM Project\Entity\DocumentCategory d '
                . 'WHERE d.active = true AND BIT_AND(d.compatibility, 1)=1 AND d.location!=\'\' '
                . 'ORDER BY d.location');
        $documentCategories = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        $query = $this->getEntityManager()->createQuery('SELECT d.documentCategoryId, d.name, d.description, d.location FROM Project\Entity\DocumentCategory d '
                . 'WHERE d.active = true AND BIT_AND(d.compatibility, 8)=8 AND d.location!=\'\' ');
        $imageCategories = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        
        $this->getView()
                ->setVariable('documentCategories', $documentCategories)
                ->setVariable('imageCategories', $imageCategories)
                ;
		return $this->getView();
    }
    
    
    public function listAction() {
         try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                //throw new \Exception('illegal request format');
            }
            
            $categoryId = $this->params()->fromPost('category', false);
            
            if (empty($categoryId)) {
                $categoryId = $this->params()->fromQuery('category', false);
                if (empty($categoryId)) {
                    throw new \Exception('no request category found');
                }
            }
            
            $category = $this->getEntityManager()->find('Project\Entity\DocumentCategory', $categoryId);
            if (!($category instanceof \Project\Entity\DocumentCategory)) {
                throw new \Exception('illegal category');
            }
            
            $config['route'] = array();
            if (!empty($category->getLocation())) {
                $config['route'] = explode('/', trim($category->getLocation(), '/'));
            }
            
            $subId = $this->params()->fromPost('subid', false);
            if (empty($subId)) {
                $subId = $this->params()->fromQuery('subid', false);
            }
            
            $dropzone = $this->params()->fromQuery('dropzone', false);
            if (empty($subId)) {
                $dropzone = $this->params()->fromPost('dropzone', false);
            }
            
            
            $dir = $this->documentService->getSaveLocation($config);
            
            $docData = array();
            $docs = $this->getEntityManager()->getRepository('Project\Entity\DocumentList')->findByProjectId($this->getProject()->getProjectId(), array('categoryId'=>$categoryId, 'subid'=>$subId), true);
            
            if ($dropzone) {
                foreach ($docs as $doc) {
                    $data[] = array(
                        'name'=>$doc['filename'],
                        'size'=>$doc['size'],
                        'dlid'=>$doc['documentListId']
                    );
                }
            } else {
                foreach ($docs as $doc) {
                    $docData[] = array(
                        $doc['documentListId'],
                        $doc['filename'],
                        ($doc['size']>(1024*1024))?(ceil($doc['size']/(1024*1024))).' MB':(($doc['size']>1024)?(ceil($doc['size']/1024)).' KB':$doc['size'].' B'),
                        $doc['extension'],
                        $doc['forename'].' '.$doc['surname'],
                        $doc['created']->format('jS F \a\t H:i'),
                        file_exists($dir.(!empty($doc['subid'])?$doc['subid'].DIRECTORY_SEPARATOR:'').$doc['filename']),
                    );
                }
                
                // create form
                $data = array('err'=>false, 'data'=>$docData);
            }

        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?($dropzone?array():array('err'=>true)):$data);/**/
    }
    
    public function downloadAction () {
         try {
            $documentListId = $this->params()->fromQuery('documentListId', false);
            if (empty($documentListId)) {
                throw new \Exception('no document id found');
            }
            
            $document = $this->getEntityManager()->find('Project\Entity\DocumentList', $documentListId);
            
            if (!($document instanceof \Project\Entity\DocumentList)) {
                throw new \Exception('document not found');
            }
            
            $config['route'] = array();
            if (!empty($document->getCategory()->getLocation())) {
                $config['route'] = explode('/', trim($document->getCategory()->getLocation(), '/'));
            }
            
            if (!empty($document->getSubId())) {
                $config['route'][] = $document->getSubId();
            }
            
            $filename = $this->documentService->getSaveLocation($config).$document->getFilename();
            if (!file_exists($filename)){
                throw new \Exception('file does not exist');
            }
            
            $response = new \Zend\Http\Response\Stream();
            $response->setStream(fopen($filename, 'r'));
            $response->setStatusCode(200);

            $headers = new \Zend\Http\Headers();
            $headers->addHeaderLine('Content-Type', $document->getExtension()->getHeader())
                    ->addHeaderLine('Content-Disposition', 'attachment; filename="' . $document->getFilename() . '"')
                    ->addHeaderLine('Content-Length', filesize($filename));

            $response->setHeaders($headers);
            return $response;
        } catch (\Exception $ex) {
            exit; // just exit as file does not exist
        }
    }
    
    function uploadAction () {
        try {
            $categoryId = $this->params()->fromQuery('category', false);
            
            if (empty($categoryId)) {
                throw new \Exception('no request category found');
            }
            
            $category = $this->getEntityManager()->find('Project\Entity\DocumentCategory', $categoryId);
            if (!($category instanceof \Project\Entity\DocumentCategory)) {
                throw new \Exception('illegal category');
            }
            
            $config['category'] = $category;
            $config['route'] = array();
            if (!empty($category->getLocation())) {
                $config['route'] = explode('/', trim($category->getLocation(), '/'));
            }

            // Note: we use bitwise comparison on the compatibility field: (1=project, 2=job, 4=spare, 8=images, 16=generated)
            if (($category->getCompatibility() & 8)==8) { // this is an image
                // we need to check config to determine which additional query params are required
                if (preg_match('/spaces$/', $category->getLocation())) {
                    $spaceId = $this->params()->fromQuery('space', false);
                    if (empty($spaceId)) {
                        throw new \Exception('no space identifier found');
                    }
                    
                    $space = $this->getEntityManager()->find('Space\Entity\Space', $spaceId);
                    if (!($space instanceof \Space\Entity\Space)) {
                        throw new \Exception('illegal space');
                    }
                    
                    if ($space->getProject()->getProjectId()!=$this->getProject()->getProjectId()) {
                        throw new \Exception('project mismatch');
                    }
                    
                    $config['subid'] = $space->getSpaceId();
                    $config['route'][] = $space->getSpaceId();
                }
            }
            
            $file = $this->params()->fromFiles('file', false);
            if (!empty($file)) {
                $this->documentService->setUser($this->getUser());
                $this->documentService->saveUploadedFile($file, $config);
                
            } else {
                throw new \Exception('No files found');
            }
            

        } catch (\Exception $ex) {
            die ($ex->getMessage());
        }

        die();
    }
    
    /**
     * export system model csv action
     * @return \Zend\Mvc\Controller\AbstractController
     */
    function exportSystemAction() {
        $data[] = array(
            '"Building ID"',	
            '"Building Name"',	
            '"Space ID"',	
            '"Space Name"',	
            '"Legacy Lighting"',	
            '"Legacy Quantity"',	
            '"Weekly Hours of Operation"',	
            '"Life Span"',	
            '"Legacy Rating"',	
            '"LED Replacement"',	
            '"LED Quantity"',	
            '"LED Rating"',	
            '"Power Saving"',	
            '"kW Saving"',	
            '"Electricity Savings Achievable Per Annum"',	
            '"CO2 Reductions Achievable Per Annum"',	
            '"Total Price"',
            '"Discounted Price"',
        );
        
        $years = $this->params()->fromQuery('modelyears',12);
        $service = $this->getModelService()->payback($this->getProject(), $years);
        $forecast = $service['forecast'];
        $figures = $service['figures'];
        $breakdown = $this->getModelService()->spaceBreakdown($this->getProject());
        $financing = !empty($figures['finance_amount']);
        
        
        foreach ($breakdown as $buildingId=>$building) {
            foreach ($building['spaces'] as $spaceId=>$space) {
                foreach ($space['products'] as $systemId=>$system) {
                    $led = ($system[2] == 1);
                    $row = array(
                        $buildingId,
                        '"'.$building['name'].'"',
                        $spaceId,
                        '"'.$space['name'].'"',
                        '"'.$system[8].'"', // legacy light name
                        $system[9], // hours of operation
                        $system[6], // legacy quantity
                        $led?number_format(50000/($system[9]*52), 2):0, // life span
                        $system[10], // legacy rating
                        $system[4], // LED model
                        $system[5], // Quantity
                        $system[7], // LED rating
                        $system[9], // power saving
                        $system[15], // kW saving
                        $system[13], // Elec saving
                        $system[14], // CO2 reductions
                        $system[0], // Total Price
                        $system[1], // Total Price (inc discount)
                    );
                    
                    $data[] = $row;
                }
            }
            
            
        }
        
        $cells = array(
            array('Year'),
            array('Cumulative Carbon Savings'),
            array('Carbon Allowance'),
            array('Current Spend'),
            array('LED Spend'),
            array('Electricity Savings'),
            array('Maintenance Savings'),
            array('Monthly Cost (No LED)'),
            array('Net Cash Saving'),
            array('Cumulative Savings'),
            array('Payback'),
            array('Payback with ECA')
        );
        
        for ($i=1; $i<=$years; $i++) {
            $cells[0][] = $i;
            $cells[1][] = $forecast[$i][5];
            $cells[2][] = $forecast[$i][10];
            $cells[3][] = $forecast[$i][0];
            $cells[4][] = $forecast[$i][1];
            $cells[5][] = $forecast[$i][2];
            $cells[6][] = $forecast[$i][3];
            $cells[7][] = $forecast[$i][6];
            $cells[8][] = $forecast[$i][4];
            $cells[9][] = $forecast[$i][5];
            $cells[10][] = $forecast[$i][8];
            $cells[11][] = $forecast[$i][9];
        }
        
        $data[] = array();
        $data[] = array();
        
        foreach ($cells as $cell) {
            $data[] = $cell;
        }

        $data[] = array();
        $data[] = array();
        
        $data[] = array('Projected ECA Eligibility',$figures['eca']);
        $data[] = array('Total '.$years.' Year Carbon Saving',$figures['carbon']);
        $data[] = array('Total '.$years.' Year Saving',$figures['saving']);
        if ($financing) {
            $data[] = array('Average Cash Benefit Over Funding Period',$figures['finance_avg_benefit']);
            $data[] = array('Average Repayments Over Funding Period',$figures['finance_avg_repay']);
            $data[] = array('Average Net Annual Benefit Over Funding Period',$figures['finance_avg_netbenefit']);
            $data[] = array('Net Cash Benefit Over Funding Period',$figures['finance_netbenefit']);
        }
        $data[] = array('LED Cost',$figures['eca']);
        $data[] = array('Installation Cost',$figures['eca']);
        
        if ($figures['cost_delivery']>0) {
            $data[] = array('Delivery Cost',$figures['cost_delivery']);
        }
        if ($figures['cost_ibp']>0) {
            $data[] = array('Insurance Backed Premium Cost',$figures['cost_ibp']);
        }
        if ($figures['cost_access']>0) {
            $data[] = array('Access Cost',$figures['cost_access']);
        }
        if ($figures['cost_prelim']>0) {
            $data[] = array('Prelim Fee',$figures['cost_prelim']);
        }
        if ($figures['cost_overheads']>0) {
            $data[] = array('Overheads Fee',$figures['cost_overheads']);
        }
        if ($figures['cost_management']>0) {
            $data[] = array('Management Fee',$figures['cost_management']);
        }

        $data[] = array('Total Cost',$figures['cost']);
        $data[] = array('Total Cost Less ECA',$figures['costeca']);
        $data[] = array('VAT at 20%',($figures['costvat']-$figures['cost']));
        $data[] = array('Total Cost (incl VAT)',$figures['costvat']);
        $data[] = array('Total Cost (incl VAT) Less ECA',$figures['costvateca']);
        $data[] = array('Total '.$years.' Year Profit',$figures['profit']);
        $data[] = array('Total '.$years.' Year Profit with ECA',$figures['profiteca']);
        
        /*$this->debug()->dump($data, false);
        $this->debug()->dump($breakdown, false);
        $this->debug()->dump($service);/**/

        $filename = 'Full System Model - '.str_pad($this->getProject()->getClient()->getClientId(), 5, "0", STR_PAD_LEFT).'-'.str_pad($this->getProject()->getProjectId(), 5, "0", STR_PAD_LEFT).'.csv';
        
        $response = $this->prepareCSVResponse($data, $filename);
        
        return $response;
    }
    
    
    /**
     * function to prepare and return csv response object
     * @param string|array $data
     * @param string $filename
     * @return \Zend\Mvc\Controller\AbstractController
     */
    private function prepareCSVResponse ($data, $filename) {
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        
        $crlf = chr(13).chr(10);
        
        if (is_array($data)) {
            foreach ($data as $row) {
                $content.=implode(',', $row).$crlf;
            }
        } else {
            $content = $data;
        }
        
        $headers->addHeaderLine('Content-Type', 'text/csv');
        $headers->addHeaderLine('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $headers->addHeaderLine('Accept-Ranges', 'bytes');
        $headers->addHeaderLine('Content-Length', strlen($content));

        $response->setContent($content);
        
        return $response;
    }
    
}