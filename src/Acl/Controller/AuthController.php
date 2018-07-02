<?php
namespace Acl\Controller;

use Acl\Form\LoginForm;
use Acl\Model\AclStorage;
use Acl\Service\AuthService;
use Acl\Service\Elfag2Service;
use Acl\Service\UserService;
use Acl\Service\UserTable;
use Iaasen\Controller\AbstractController;
use Iaasen\Messenger\SessionMessenger;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;
use Zend\Crypt\Password\Bcrypt;

class AuthController extends AbstractController {

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
	/** @var Elfag2Service */
	protected $elfag2Service;

	public function __construct(
		AuthService $authService,
		LoginForm $loginForm,
		UserService $userService,
		UserTable $userTable,
		AclStorage $sessionStorage,
		Elfag2Service $elfag2Service)
	{
		$this->authService = $authService;
		$this->loginForm = $loginForm;
		$this->userService = $userService;
		$this->userTable = $userTable;
		$this->sessionStorage = $sessionStorage;
		$this->elfag2Service = $elfag2Service;
	}


	/**
	 * @return mixed|\Zend\Authentication\Result|Response|ViewModel
	 * @throws \Exception
	 */
	public function loginAction() {
		$redirect = $this->getRedirect($this->url()->fromRoute('home'));
		if(strpos($redirect, '/auth/login') !== false) $redirect = $this->url()->fromRoute('home');

		if($this->authService->hasIdentity()) return $this->redirect()->toUrl($redirect);

		$form = $this->loginForm;

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
					$this->getFlashMessenger()->addSuccessMessage('Logget inn som "' . $result->getIdentity() . '"');

					if ($request->getPost('rememberMe') == 1) {
						$this->sessionStorage->setRememberMe(1);
						$this->authService->setStorage($this->sessionStorage);
					}

					$user = $this->userService->getUserByUsername($this->authService->getIdentity());
					$user->last_login = new \DateTime();
					$this->userService->saveUser($user);

					// Update access to elfag2 user
					if($user->logintype == 'elfag2') {
						$this->elfag2Service->connectUserToGroup($user);
						if(!$user->countGroupAccesses()) return $this->redirect()->toRoute('user/create-elfag2-group');
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
					$this->getFlashMessenger()->addErrorMessage('Feil brukernavn eller passord');
				}
			}
		}


		$viewModel = new ViewModel([
			'form'		=> $form,
		]);
		return $viewModel;
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
			'hash' => $hash ?? null,
			'time' => $time ?? null,
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
	 * @return SessionMessenger
	 */
	protected function getFlashMessenger() : SessionMessenger {
		return $this->flashMessenger();
	}

}