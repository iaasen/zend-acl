<?php
namespace Acl\Controller;

use Zend\Http\PhpEnvironment\Request;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Zend\View\Model\ViewModel;
use Zend\Crypt\Password\Bcrypt;

class AuthController extends AbstractActionController {
	protected $form;
	protected $storage;

	/** @var  \Acl\Service\AuthService */
	protected $authService;
	/** @var  \Acl\Form\LoginForm */
	protected $loginForm;
	/** @var  \Acl\Service\UserService */
	protected $userService;
	/** @var  \Acl\Service\UserTable */
	protected $userTable;
	/** @var  \Acl\Model\AclStorage */
	protected $sessionStorage;
	/** @var  FlashMessenger */
	protected $flashMessenger;
	
	protected $tables;

	public function __construct($authService, $loginForm, $userService, $userTable, $sessionStorage)
	{
		$this->authService = $authService;
		$this->loginForm = $loginForm;
		$this->userService = $userService;
		$this->userTable = $userTable;
		$this->sessionStorage = $sessionStorage;
	}

//	public function onDispatch(\Zend\Mvc\MvcEvent $e) {
//		//$o = $this->getServiceLocator()->get('viewhelpermanager')->get('menu')->mainMenu();
//		return parent::onDispatch($e);
//	}

	/**
	 * @return mixed|\Zend\Authentication\Result|Response|ViewModel
	 * @throws \Exception
	 */
	public function loginAction() {
		if($this->authService->hasIdentity()) return $this->redirect()->toRoute('home');

		$redirect = $this->params()->fromPost('redirect', $this->params()->fromQuery('redirect'));
		//if(!$redirect) $redirect = $this->url()->fromRoute('auth/login');
		if(!$redirect) $redirect = $this->url()->fromRoute('home');

		$form = $this->loginForm;
		$form->get('redirect')->setValue($redirect);

		/** @var Request $request */
		$request = $this->getRequest();
		if($request->isPost()) {
			$form->setData($request->getPost());
			if($form->isValid()) {
				$data = $form->getData();
				$this->authService->setIdentity($data['username']);
				$this->authService->setCredential($data['password']);

				$result = $this->authService->authenticate();

				if($result->isValid()) {
					$this->getFlashMessenger()->addMessage('Logget inn som "' . $result->getIdentity() . '"');
					//$redirect = $this->url()->fromRoute('home');

					if ($request->getPost('rememberMe') == 1) {
						$this->sessionStorage->setRememberMe(1);
						$this->authService->setStorage($this->sessionStorage);
					}

					$user = $this->userService->getUserByUsername($this->authService->getIdentity());
//					if (!$user) { // Probably elfag2-user and it's the first login
////						if(filter_var($this->authService->getIdentity(), FILTER_VALIDATE_EMAIL)) {
////							$this->userService->createElfag2User($this->authService->getIdentity());
////						}
//						// Create user
//						//return $this->redirect()->toRoute('user/createElfagUser');
//					}

					$user->last_login = new \DateTime();
					$this->userService->saveUser($user);


					// Make sure current_group is set
					if (!$user->current_group) {
						if (count($user->access) > 1) {
							return $this->redirect()->toRoute('user/selectGroup', [], ['query' => ['redirect' => $redirect]]);
						} elseif (count($user->access) == 1) {
							$user->current_group = key($user->access);
							$this->userService->saveUser($user);
						} else {
							if($user->logintype == 'elfag2') {
								return $this->redirect()->toRoute('user/create-elfag2-group');
							}
							else $this->redirect()->toRoute('user/noAccess');
						}
					}

					// No access to the current current_group
					if (!isset($user->access[$user->current_group])) {
						return $this->redirect()->toRoute('user/selectGroup', [], ['query' => ['redirect' => $redirect]]);
					}

					// Dispatch the login_successful event. Redirect if receiving a Response
					$results = $this->getEventManager()->trigger('login_successful', $this, ['identity' => $result->getIdentity()]);
					foreach ($results as $result) {
						if ($result instanceof Response) {
							return $result;
						}
					}
					return $this->redirect()->toUrl($redirect);
				}
				else {
					$this->getFlashMessenger()->addMessage('Feil brukernavn eller passord');
				}
			}
		}

		$viewModel = new ViewModel([
			'form'		=> $form,
		]);
		return $viewModel;
	}


	
//	public function authenticateAction() {
//		$form = $this->loginForm;
//		$redirect = $this->params()->fromPost('redirect');
//		if(!$redirect) $redirect = $this->url()->fromRoute('auth/login');
//
//
//
//		/** @var Request $request */
//		$request = $this->getRequest();
//		if($request->isPost()) {
//			$form->setData($request->getPost());
//			if($form->isValid()) {
//				$data = $form->getData();
//				$this->authService->setIdentity($data['username']);
//				$this->authService->setCredential($data['password']);
//
//				$result = $this->authService->authenticate();
//
//				foreach($result->getMessages() as $message) {
//					$this->getFlashMessenger()->addMessage($message);
//				}
//
//				if($result->isValid()) {
//					$redirect = $this->url()->fromRoute('home');
//
//					if ($request->getPost('rememberMe') == 1) {
//						$this->sessionStorage->setRememberMe(1);
//						$this->authService->setStorage($this->sessionStorage);
//					}
//
//					$user = $this->userService->getUserByUsername($this->authService->getIdentity());
//
//
//					if (!$user) { // Probably elfag-user and it's the first login
//						return $this->redirect()->toRoute('user/createElfagUser');
//					}
//
//
//					$user->last_login = time();
//					$this->userService->saveUser($user);
//
//					// Make sure current_group is set
//					if (!$user->current_group) {
//						if (count($user->access) > 1) {
//							return $this->redirect()->toRoute('user/selectGroup', [], ['query' => ['redirect' => $redirect]]);
//						} elseif (count($user->access) == 1) {
//							$user->current_group = key($user->access);
//							$this->userService->saveUser($user);
//						} else {
//							$this->redirect()->toRoute('user/noAccess');
//						}
//					}
//					// No access to the current current_group
//					if (!isset($user->access[$user->current_group])) {
//						return $this->redirect()->toRoute('user/selectGroup', [], ['query' => ['redirect' => $redirect]]);
//					}
//
//					// Dispatch the login_successful event. Redirect if receiving a Response
//					$results = $this->getEventManager()->trigger('login_successful', $this, ['identity' => $result->getIdentity()]);
//					foreach ($results as $result) {
//						if ($result instanceof Response) {
//							return $result;
//						}
//					}
//
//					return $this->redirect()->toUrl($redirect);
//				}
//			}
//		}
//		return $this->redirect()->toUrl($redirect);
//	}
	
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
		
		$this->getFlashMessenger()->addMessage('Du er logget ut');
		return $this->redirect()->toRoute('auth/login');
	}
	
	public function createuserAction() {
		
	}

	/**
	 * @return FlashMessenger
	 */
	protected function getFlashMessenger() {
		if(!$this->flashMessenger) $this->flashMessenger = new FlashMessenger();
		return $this->flashMessenger;
	}
	
}