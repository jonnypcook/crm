<?php

namespace Project\Service;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

class DocumentService 
{
    protected $location;
    protected $entityManager;
    
    /**
     * project
     * @var \Project\Entity\Project
     */
    protected $project;
    
    /**
     * client
     * @var \Client\Entity\Client 
     */
    protected $client;


    /**
     * user
     * @var \Application\Entity\User 
     */
    protected $user;


    public function __construct($location, \Doctrine\ORM\EntityManager $em) {
        $this->setLocation($location);
        $this->setEntityManager($em);
    }

    
    public function getLocation() {
        return $this->location;
    }

    public function setLocation($location) {
        $this->location = $location;
        return $this;
    }
    
    /**
     * get client
     * @return \Client\Entity\Client
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * set client
     * @param \Client\Entity\Client $client
     * @return \Project\Service\DocumentService
     */
    public function setClient(\Client\Entity\Client $client) {
        $this->client = $client;
        return $this;
    }
    
    /**
     * check for client 
     * @return boolean
     */
    public function hasClient() {
        return ($this->client instanceof \Client\Entity\Client);
    }

        
    
    /**
     * get project
     * @return \Project\Entity\Project
     */
    public function getProject() {
        return $this->project;
    }
    

    /**
     * set project
     * @param \Project\Entity\Project $project
     * @return \Project\Service\DocumentService
     */
    public function setProject(\Project\Entity\Project $project) {
        $this->project = $project;
        $this->client = $project->getClient();
        return $this;
    }
    
    
    /**
     * check if project exists
     * @return boolean
     */
    public function hasProject() {
        return ($this->project instanceof \Project\Entity\Project);
    }

    /**
     * get user
     * @return \Application\Entity\User
     */
    public function getUser() {
        return $this->user;
    }
    
     /**
     * check if user exists
     * @return boolean
     */
    public function hasUser() {
        return ($this->user instanceof \Application\Entity\User);
    }

    /**
     * set user
     * @param \Application\Entity\User $user
     * @return \Project\Service\DocumentService
     */
    public function setUser(\Application\Entity\User $user) {
        $this->user = $user;
        return $this;
    }

        
    function getSaveLocation(array $config=array()) {
        $path = $this->getLocation();
        if (empty($path)) {
            throw new \Exception('Location not set');
        }
       
        if (!is_dir($path)) {
            throw new \Exception('Google Drive not found');
        }
        

        $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        
        // make unix safe
        if ($this->hasClient()) {
            $path.= trim(preg_replace('/[_]+/', '_',str_replace(array('"', "'", "&", "/", "\\", "?", "#"), '_', trim($this->getProject()->getClient()->getName()))),'_');
            if (!is_dir($path)) {
                if (!mkdir($path)) {
                    throw new \Exception('client path could not be created');
                }
            }
            
            
            if ($this->hasProject()) {
                $pid = str_pad($this->getProject()->getClient()->getClientId(), 5, '0', STR_PAD_LEFT).'-'.str_pad($this->getProject()->getProjectId(), 5, '0', STR_PAD_LEFT);
                $path.=DIRECTORY_SEPARATOR.trim(preg_replace('/[_]+/', '_',str_replace(array('"', "'", "&", "/", "\\", "?", "#"), '_', trim($this->getProject()->getName().' ['.$pid.']'))),'_');
                //$path.=DIRECTORY_SEPARATOR.str_pad($this->getProject()->getClient()->getClientId(), 5, '0', STR_PAD_LEFT).'-'.str_pad($this->getProject()->getProjectId(), 5, '0', STR_PAD_LEFT); 
            }
            
            if (!is_dir($path)) {
                if (!mkdir($path)) {
                    throw new \Exception('project path could not be created');
                }
            }
            
            
            if (isset ($config['route'])) {
                $route = $config['route'];
                if (!is_array($route)) {
                    $route = array($route);
                }
                
                foreach ($route as $dir) {
                    $path.=DIRECTORY_SEPARATOR.$dir;
                    if (!is_dir($path)) {
                        if (!mkdir($path)) {
                            throw new \Exception('project path could not be created');
                        }
                    }
                }
            }
            

        } else {
            throw new \Exception('no client found - cannot save to projects route');
        }
        
        return ($path.DIRECTORY_SEPARATOR);
    }

    public function saveDOMPdfDocument (\DOMPDF $dompdf, array $config=array()) {
        if (empty($config['filename'])) {
            throw new \Exception('no filename found');
        }
        
        if (empty($config['category'])) {
            throw new \Exception('no category found');
        }
        
        $filename = $config['filename'];
        
        if (!preg_match('/[.]pdf$/i', $filename)) {
            $filename.='.pdf';
        }
        
        
        $dir = $this->getSaveLocation($config);
        
        file_put_contents($dir.$filename, $dompdf->output());
        
        try {
            $fileMd5 = md5_file($dir.$filename);
            $fileSize = filesize($dir.$filename);
        } catch (\Exception $ex) {
            $fileMd5=null;
            $fileSize=0;
        }
        
        $this->logDocument($filename, $config['category'], $fileMd5, $fileSize);
        
        
        return array (
            'file'=>$dir.$filename,
        );
    }
    
    
    function logDocument($filename, $category, $hash, $size) {
        // example chksum = '3c167ffb798d9b313abd8a3f4cb30ecb';
        $em = $this->getEntityManager();
        $document = new \Project\Entity\DocumentList();
        
        
        $ext = 'moo';//preg_replace('/^[^.]+[.]([^.]+)$/','$1',$filename);
        
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder
            ->select('d')
            ->from('Project\Entity\DocumentExtension', 'd')
            ->where('d.extension=?1')
            ->setParameter(1, $ext);

        $query  = $queryBuilder->getQuery();
        try {
            $extObj = $query->getSingleResult();
        } catch (\Exception $e) {
            $extObj = new \Project\Entity\DocumentExtension();
            $extObj
                ->setExtension($ext)
                ->setHeader('application/octet-stream');
            $em->persist($extObj);
            $em->flush();
        }
        
        $document->setExtension($extObj);
        
        $data = array (
            'filename'=>$filename,
            'hash'=>$hash,
            'size'=>$size,
        );
        
        if ($category instanceof \Project\Entity\DocumentCategory) {
            $document->setCategory($category);
        } else {
            $data['category'] = $category;
        }
        
        if ($this->hasUser()) {
            $document->setUser($this->getUser());
        }
        
        if ($this->hasProject()) {
            $document->setProject($this->getProject());
        }
        
        $hydrator = new DoctrineHydrator($em,'Project\Entity\DocumentList');
        $hydrator->hydrate(
            $data,
            $document
        );
        
        $em->persist($document);
        $em->flush();
    }

    // factory involkable methods
    function setEntityManager(\Doctrine\ORM\EntityManager $em) {
        $this->em = $em;
    }
    
    public function getEntityManager() {
        return $this->em;
    }


    
}

