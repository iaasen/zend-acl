<?php
namespace Acl\Controller;

use Zend\Http\PhpEnvironment\Request;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Crypt\Password\Bcrypt;

class AuthController extends AbstractActionController {
	protected $form;
	protected $storage;

	/** @var  \Acl\Service\AuthService */
	protected $authService;
	/** @var  \Acl\Form\LoginForm */
	protected $loginForm;
	/** @var  \Acl\Service\UserTable */
	protected $userTable;
	/** @var  \Acl\Model\AclStorage */
	protected $sessionStorage;
	
	protected $tables;

	public function __construct($authService, $loginForm, $userTable, $sessionStorage)
	{
		$this->authService = $authService;
		$this->loginForm = $loginForm;
		$this->userTable = $userTable;
		$this->sessionStorage = $sessionStorage;
	}

//	public function onDispatch(\Zend\Mvc\MvcEvent $e) {
//		//$o = $this->getServiceLocator()->get('viewhelpermanager')->get('menu')->mainMenu();
//		return parent::onDispatch($e);
//	}
	
	
	public function loginAction() {
		if(isset($_GET['redirect'])) $redirect = $_GET['redirect'];
		if($this->authService->hasIdentity()) {
			return $this->redirect()->toRoute('home');
		}

		$_form = $this->loginForm;
		$_form->setAttribute('action', $this->url()->fromRoute('auth/authenticate'));
		if(isset($redirect)) $_form->get('redirect')->setValue($redirect);

		$viewModel = new ViewModel([
			'form'		=> $_form,
			'messages'	=> $this->flashmessenger()->getMessages(),
		]);
		//$viewModel->setTemplate('auth/login');
		return $viewModel;
	}
	
	public function authenticateAction() {
		$form = $this->loginForm;
		$redirect = $this->params()->fromPost('redirect');
		if(!$redirect) $redirect = $this->url()->fromRoute('auth/login');

		/** @var Request $request */
		$request = $this->getRequest();
		if($request->isPost()) {
			$form->setData($request->getPost());
			if($form->isValid()) {
				$this->authService->setIdentity($request->getPost('username'));
				$this->authService->setCredential($request->getPost('password'));

				$result = $this->authService->authenticate();

				foreach($result->getMessages() as $message) {
					$this->flashmessenger()->addMessage($message);
				}
				
				if($result->isValid()) {
					$redirect = $this->url()->fromRoute('home');
					
					if($request->getPost('rememberMe') == 1) {
						$this->sessionStorage->setRememberMe(1);
						$this->authService->setStorage($this->sessionStorage);
					}

					$user = $this->userTable->getUser($this->authService->getIdentity());

					if(!$user) { // Probably elfag-user and it's the first login
						return $this->redirect()->toRoute('user/createElfagUser');
					}
					
					
					$user->last_login = time();
					$this->userTable->save($user);

					// Make sure current_group is set
					if(!$user->current_group) {
						if(count($user->access) > 1) {
							return $this->redirect()->toRoute('user/selectGroup', [], ['query' => ['redirect' => $redirect]]);
						}
						elseif(count($user->access) == 1) {
							$user->current_group = key($user->access);
							$this->userTable->save($user);
						}
						else {
							$this->redirect()->toRoute('user', array('action' => 'noaccess'));
						}
					}

					// No access to the current current_group
					if(!isset($user->access[$user->current_group])) {
						return $this->redirect()->toRoute('user/selectGroup', [], ['query' => ['redirect' => $redirect]]);
					}

					// Dispatch the login_successful event. Redirect if receiving a Response
					$results = $this->getEventManager()->trigger('login_successful', $this, ['identity' => $result->getIdentity()]);
					foreach($results as $result) {
						if($result instanceof Response) {
							return $result;
						}
					}

					return $this->redirect()->toUrl($redirect);
				}
			}
		}
		
		return $this->redirect()->toRoute($redirect);
	}
	
	public function generateBcryptAction() {
		$cost = $this->params()->fromPost('cost', 13);
		//$password = $this->params()->fromPost('password', null);

		/** @var Request $request */
		$request = $this->getRequest();
		if($request->isPost()) {
			$post = $request->getPost();
			$bcrypt = new Bcrypt();
			$bcrypt->setCost($post['cost']);
			$time = microtime(true);
			$hash = $bcrypt->create($post['password']);
			$time = microtime(true) - $time;
		}
		
		return [
			'hash' => (isset($hash)) ? $hash : null,
			'time' => (isset($time)) ? $time : null,
			'cost' => $cost
		];
	}
	
	public function logoutAction() {
		$this->sessionStorage->forgetMe();
		$this->authService->clearIdentity();
		
		$this->flashmessenger()->addMessage('Du er logget ut');
		return $this->redirect()->toRoute('auth/login');
	}
	
	public function createuserAction() {
		
	}
	
}