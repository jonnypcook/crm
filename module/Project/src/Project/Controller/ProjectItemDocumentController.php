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
        
        $query = $this->getEntityManager()->createQuery('SELECT d.documentCategoryId, d.name, d.description, d.config, d.partial FROM Project\Entity\DocumentCategory d WHERE d.active = true AND BIT_AND(d.compatibility, 1)=1');
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
        $query = $this->getEntityManager()->createQuery('SELECT d.config FROM Project\Entity\DocumentCategory d WHERE d.active = true AND BIT_AND(d.compatibility, 1)=1 AND d.documentCategoryId='.$categoryId);
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
        $categoryId = !empty($this->params()->fromQuery('documentId', false));
        if (empty($categoryId)) {
            throw new \Exception('Illegal request');
        }
        
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
        $query = $em->createQuery('SELECT d.documentCategoryId, d.location, d.name, d.description, d.config, d.partial FROM Project\Entity\DocumentCategory d WHERE d.active = true AND BIT_AND(d.compatibility, 1)=1 AND d.documentCategoryId='.$categoryId);
        $category = $query->getSingleResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        if (empty($category)) {
            throw new \Exception('document does not exist or is incompatible');
        }
        
        $config = json_decode($category['config'], true);
        $pdfVars = array(
            'resourcesUri' => getcwd().DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR,
            'project' => $this->getProject(),
            'footer' => array (
                'pages'=>true
            )
        );
        
        
        
        //{"con1":true,"usr1":true,"mdl1":false,"mdl2":true,"mdl3":false,"sur1":true,"model":true,"tac1":false,"autosave":true,"docsave":true,"quot":true,"adr1":true,"payterm":true}
        $form = new \Project\Form\DocumentWizardForm($em, $this->getProject(), $config);
        $form->setInputFilter(new \Project\Filter\DocumentWizardInputFilter());
        $form->setData($data);
        
        if (!$form->isValid()) {
            throw new Exception ('illegal configuration parameters');
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
                case 'autosave': 
                    $autoSave = (bool)$value;
                    break;
                default:
                    $pdfVars['form'][$name] = $value;
                    break;
            }
        }

        $pdfVars['form'] = $form->getData();
        
        $inline = !empty($this->params()->fromQuery('documentInline', false));

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
                        $service = $this->getModelService()->payback($this->getProject());
                        $pdfVars['figures'] = $service['figures'];
                        $pdfVars['forecast'] = $service['forecast'];
                        
                        //echo '<pre>', print_r($service['figures'], true), '</pre>'; die('<br />end');
                    }
                    
                    if (($value & 2)==2) {
                        $service = $this->getModelService()->spaceBreakdown($this->getProject());
                        $pdfVars['breakdown'] = $service['breakdown'];
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
            
            $this->documentService->setUser($this->getUser());
            $info = $this->documentService->saveDOMPdfDocument(
                $dompdf,
                array(
                    'filename' =>$config['name'],
                    'route' => $route,
                    'category' => $categoryId,
            ));/**/
            
            if ($email) {
                $client = $this->getGoogle();

                $mail = new \PHPMailer();
                $mail->CharSet = "UTF-8";

                $mail->From = $this->identity()->getEmail();
                $mail->FromName = $this->identity()->getName();
                $mail->AddAddress($formEmail->get('emailRecipient')->getValue());
                $mail->AddReplyTo($this->identity()->getEmail(),$this->identity()->getName());
                $mail->Subject = $formEmail->get('emailSubject')->getValue();
                $mail->Body    = $formEmail->get('emailMessage')->getValue();

                $mail->addAttachment($info['file']);

                $mail->preSend();
                $mime = $mail->getSentMIMEMessage();
                
                $message = new \Google_Service_Gmail_Message();
                $message->setRaw(str_replace(array('+','/','='),array('-','_',''),base64_encode($mime)));

                $gmail = new \Google_Service_Gmail($client);
                $response = $gmail->users_messages->send('jonny.p.cook@8point3led.co.uk', $message);                
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
    
}