<?php
namespace Login\Controller;

// Authentication with Remember Me
// http://samsonasik.wordpress.com/2012/10/23/zend-framework-2-create-login-authentication-using-authenticationservice-with-rememberme/

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Entity\User; // only for the filters

use Login\Form\LoginForm;       // <-- Add this import
use Login\Form\LoginFilter;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

class DoctrineController extends AbstractActionController
{
    public function indexAction()
    {
        $em = $this->getEntityManager();
		$auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');

		if ($auth->hasIdentity()) {
			// Identity exists; get it
            return $this->redirect()->toRoute('home');
        }
        
        $form = new LoginForm();
		$messages = null;

		$request = $this->getRequest();
        if ($request->isPost()) {
            //- $authFormFilters = new User(); // we use the Entity for the filters
			// TODO fix the filters
            //- $form->setInputFilter($authFormFilters->getInputFilter());

			// Filters have been fixed
			$form->setInputFilter(new LoginFilter($this->getServiceLocator()));
            $form->setData($request->getPost());
			// echo "<h1>I am here1</h1>";
            if ($form->isValid()) {
				$data = $form->getData();			
				// $data = $this->getRequest()->getPost();
				// If you used another name for the authentication service, change it here
				// it simply returns the Doctrine Auth. This is all it does. lets first create the connection to the DB and the Entity
				$authService = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');		
				// Do the same you did for the ordinar Zend AuthService	
				$adapter = $authService->getAdapter();
				$adapter->setIdentityValue($data['username']); //$data['usr_name']
				$adapter->setCredentialValue($data['password']); // $data['usr_password']
				$authResult = $authService->authenticate();

                if ($authResult->isValid()) {
					$user = $authResult->getIdentity();
                    $this->logon($authService, $user, $data['rememberme']);
					
					return $this->redirect()->toRoute('home');
				}
				foreach ($authResult->getMessages() as $message) {
					$messages .= "$message\n"; 
				}	

			} else {
                //print_r($form->getMessages());
                //die('nv');
            }
		}
        
		return new ViewModel(array(
			'error' => 'Your authentication credentials are not valid',
			'form'	=> $form,
			'messages' => $messages,
		));
    }

    public function logoutAction()
	{
		// in the controller
		// $auth = new AuthenticationService();
		$auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');

		// @todo Set up the auth adapter, $authAdapter
		if ($auth->hasIdentity()) {
			$identity = $auth->getIdentity();
            $this->AuditPlugin()->audit(2, $identity->getUserId());
		}
		$auth->clearIdentity();
		$sessionManager = new \Zend\Session\SessionManager();
		$sessionManager->forgetMe();

        return $this->redirect()->toRoute('login');

	}

    /**             
	 * @var Doctrine\ORM\EntityManager
	 */                
	protected $em;

	public function getEntityManager()
	{
		if (null === $this->em) {
			$this->em = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
		}
		return $this->em;
	}
    
    
    /**
     * create user session
     * @param \Zend\Authentication\AuthenticationService $authService
     * @param Application\Entity\User $user
     * @param type $persist
     * @return boolean
     */
    public function logon(\Zend\Authentication\AuthenticationService $authService, $user, $persist=true) {
        try {
            $authService->getStorage()->write($user);

            if ($persist) {
                $time = 1410259678; // 44 years //1209600; // 14 days 1209600/3600 = 336 hours => 336/24 = 14 days
                $sessionManager = new \Zend\Session\SessionManager();
                $sessionManager->rememberMe($time);
            }

            $this->AuditPlugin()->audit(1, $user->getUserId());
            
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }
    
    
    public function oauth2googleAction() {
        try {
            // grab local config
            $config = $this->getServiceLocator()->get('Config');
            
            $client = new \Google_Client();
            $client->setClientId($config['openAuth2']['google']['clientId']);
            $client->setClientSecret($config['openAuth2']['google']['clientSecret']);
            $client->setAccessType($config['openAuth2']['google']['accessType']);
            $client->setRedirectUri($config['openAuth2']['google']['redirectUri']);
            $client->setScopes($config['openAuth2']['google']['scope']);

            $code = $this->params()->fromQuery('code', false);
            if (empty($code)) {
                $this->redirect()->toUrl($client->createAuthUrl());
                return;
            } 
            
            $client->authenticate($code);
            
            // We got an access token, let's now get the user's details
            $plus = new \Google_Service_Oauth2($client);
            $me = $plus->userinfo_v2_me->get();
            
            // find user
            $user = $this->getEntityManager()->getRepository('Application\Entity\User')->findByEmail($me->getEmail());

            // check user id
            if (empty($user->getGoogle_id()) || ($user->getGoogle_id() != $me->getId())) {
                die ('This user has not been admin passed yet - add to task list for administrator');
            }

            // user is fine to proceed add the 
            $user->setToken_access($client->getAccessToken());
            $token = json_decode($client->getAccessToken());
            if (!empty($token->refresh_token)) {
                $user->setToken_refresh($token->refresh_token);
            }

            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();

            $this->logon($this->getServiceLocator()->get('Zend\Authentication\AuthenticationService'), $user);
            return $this->redirect()->toRoute('home');
            die('<br /><a href="http://loc.8point3.co.uk/oauth2google">restart</a>');
        } catch (\Exception $e) {
            echo $e->getMessage();
            die('<br /><a href="http://loc.8point3.co.uk/oauth2google">restart</a>');
            return $this->redirect()->toRoute('login');
        }
    }
    
    public function _oauth2googleAction() {
        try {
            
            session_start();
            $client_id = '83789145724-lrqatuio5e3vr01cqt14faa9r3sdhf2c.apps.googleusercontent.com';
            $client_secret = 'ogKEZ5Np4a5vf95YtObDOHl-';
            $redirect_uri = 'http://loc.8point3.co.uk/oauth2google';
            
            $client = new \Google_Client();
            $client->setClientId($client_id);
            $client->setClientSecret($client_secret);
            $client->setAccessType('offline');
            $client->setRedirectUri($redirect_uri);
            $client->setScopes(array('https://www.googleapis.com/auth/userinfo.profile',
                'https://www.googleapis.com/auth/userinfo.email',            
                'https://www.googleapis.com/auth/calendar',
                'https://www.googleapis.com/auth/calendar.readonly'));
            
            /************************************************
            If we're logging out we just need to clear our
            local access token in this case
            ************************************************/
            if (isset($_REQUEST['logout'])) {
                unset($_SESSION['access_token']);
                unset($_SESSION['refresh_token']);
            }
            
            /************************************************
            If we have a code back from the OAuth 2.0 flow,
            we need to exchange that with the authenticate()
            function. We store the resultant access token
            bundle in the session, and redirect to ourself.
            ************************************************/
            if (isset($_GET['code'])) {
                $client->authenticate($_GET['code']);
                $_SESSION['access_token'] = $client->getAccessToken();
                $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
                echo '<pre>',print_r(json_decode($_SESSION['access_token'], true), true), '</pre>';
                die('Location: ' . $redirect_uri);
            }

            /************************************************
            If we have an access token, we can make
            requests, else we generate an authentication URL.
            ************************************************/
            if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
                $client->setAccessToken($_SESSION['access_token']);
            } else {
                $authUrl = $client->createAuthUrl();
            }
            
            
            /************************************************
            If we're signed in we can go ahead and retrieve
            the ID token, which is part of the bundle of
            data that is exchange in the authenticate step
            - we only need to do a network call if we have
            to retrieve the Google certificate to verify it,
            and that can be cached.
            ************************************************/
            if ($client->getAccessToken()) {
                $obj = json_decode($client->getAccessToken(), true);
                //$client->refreshToken($client->getAccessToken());
                
                $_SESSION['access_token'] = $client->getAccessToken();
                $obj = json_decode($client->getAccessToken(), true);
                echo 'date='.date('Y-m-d H:i:s', $obj['created']+$obj['expires_in']).'<br />';
                echo '<pre>',print_r(json_decode($client->getAccessToken(), true), true), '</pre>';
                $token_data = $client->verifyIdToken()->getAttributes();
            }
            
            $plus = new \Google_Service_Oauth2($client);
            $person = $plus->userinfo_v2_me->get();

            echo '<hr>';
            echo '<pre>',print_r($person, true), '</pre>';
            echo '<hr>';
            
            //$client->revokeToken($_SESSION['access_token']);
            echo '<div class="box">
  <div class="request">
    '.(isset($authUrl)?'<a class="login" href="'.$authUrl.'">Connect Me!</a>':'<a class="logout" href="?logout">Logout</a>').'
  </div>';
echo '<pre>',print_r($_SESSION['access_token'], true), '</pre>';
echo $_SESSION['access_token'];
  if (isset($token_data)) {
      echo '<pre>';
      var_dump($token_data);
      echo '</pre>';
      
  }
echo '</div>';
            
            die();
            $client = new \Google_Client();
            $client->setApplicationName("Client_Projis_Examples");
            $client->setClientId('83789145724-lrqatuio5e3vr01cqt14faa9r3sdhf2c.apps.googleusercontent.com');
            $client->setClientSecret('ogKEZ5Np4a5vf95YtObDOHl');
            $client->setRedirectUri('http://loc.8point3.co.uk/oauth2google');
            $client->setScopes(array('https://www.googleapis.com/auth/userinfo.profile',
                'https://www.googleapis.com/auth/userinfo.email',            
                'https://www.googleapis.com/auth/calendar',
                'https://www.googleapis.com/auth/calendar.readonly'));

            //For loging out.
            // Step 2: The user accepted your access now you need to exchange it.
            if (isset($_GET['code'])) {
                $client->authenticate($_GET['code']);  
                die('STOP');
                $_SESSION['token'] = $client->getAccessToken();
                $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
                header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
            }

            // Step 1:  The user has not authenticated we give them a link to login    
            if (!$client->getAccessToken() && !isset($_SESSION['token'])) {
                die('Location: '.$client->createAuthUrl());
                exit;
                echo $authUrl;
                print "<br /><a class='login' href='$authUrl'>Connect Me!</a>";
            }   
            
            die();
            $client = new \Google_Client();
            $client->setClientId('83789145724-lrqatuio5e3vr01cqt14faa9r3sdhf2c.apps.googleusercontent.com');
            $client->setClientSecret('ogKEZ5Np4a5vf95YtObDOHl');
            $client->setRedirectUri('http://loc.8point3.co.uk/oauth2google');
            $client->setScopes (array(
                'https://www.googleapis.com/auth/userinfo.profile',
                'https://www.googleapis.com/auth/userinfo.email',            
                'https://www.googleapis.com/auth/calendar',
                'https://www.googleapis.com/auth/calendar.readonly',
            ));
            //$client->setAccessType('online');
            //$client->setDeveloperKey('AIzaSyCfXumYi3zTmnpT06D13zaewG86H_sr-ok');

            if (isset($_GET['code'])) { // we received the positive auth callback, get the token and store it in session
                echo 'here';
                $client->authenticate($_GET['code']);
                echo '<pre>',print_r($client->getAccessToken(), true),'</pre>';;
                die('STOP');
            }
            
            if (!$client->getAccessToken()) { // auth call to google
                $authUrl = $client->createAuthUrl();
                header("Location: ".$authUrl);
                exit;
            }
        } catch (\Exception $e) {
            echo $e->getMessage(),'<br />';
            echo $e->getCode(),'<br />';
        }
        
        
        die('<br /><a href="http://loc.8point3.co.uk/oauth2google?logout">restart</a>');/**/
        // grab global configuration
        $config = $this->getServiceLocator()->get('Config');

        // check configuration
        if (empty($config['oagoogle']['provider'])) {
            throw new Exception('google oauth2.0 parameters not found');
        }
        //calendar
        
        $provider = new \League\OAuth2\Client\Provider\Google($config['oagoogle']['provider']+array('scopes'=>array(
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/userinfo.email',            
            'https://www.googleapis.com/auth/calendar',
            'https://www.googleapis.com/auth/calendar.readonly',
        )));
        $code = $this->params()->fromQuery('code', false);
        if (empty($code)) {
            header('Location: '.$provider->getAuthorizationUrl());
            exit;/**/
        } else {
            try {
                $token = $provider->getAccessToken('authorization_code', array('code' => $code));
                
                // We got an access token, let's now get the user's details
                $userDetails = $provider->getUserDetails($token);
                
                // find user
                $user = $this->getEntityManager()->getRepository('Application\Entity\User')->findByEmail($userDetails->email);
                
                // check user id
                if (empty($user->getaccess_id()) || ($user->getaccess_id() != $userDetails->uid)) {
                    die ('This user has not been admin passed yet - add to task list for administrator');
                }

                // user is fine to proceed add the 
                $dt = new \DateTime();
                $dt->setTimestamp(time()+$token->expires);
                $user
                    ->setaccess_token($token->accessToken)
                    ->setrefresh_token($token->refreshToken)
                    ->setsession_expiry($dt);

                $this->getEntityManager()->persist($user);
                $this->getEntityManager()->flush();

                $this->logon($this->getServiceLocator()->get('Zend\Authentication\AuthenticationService'), $user);
                return $this->redirect()->toRoute('home');
            } catch (\Exception $e) {
                echo $e->getMessage();
                die();
                return $this->redirect()->toRoute('login');
            }
        }

    }
   
}

/*
 * League Request = https://accounts.google.com/o/oauth2/token
 * 
 * 
 * 
 */