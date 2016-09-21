<?php
namespace Acl\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Form\Form;
use Zend\Form\Fieldset;
use Zend\Form\Element;
use General\Message;
use Acl\Model\User;
use Zend\Crypt\Password\Bcrypt;

class AuthController extends AbstractActionController {
	protected $form;
	protected $storage;
	protected $authService;
	protected $tables;
	
	public function onDispatch(\Zend\Mvc\MvcEvent $e) {
		//$o = $this->getServiceLocator()->get('viewhelpermanager')->get('menu')->mainMenu();
		return parent::onDispatch($e);
	}
	
	
	public function loginAction() {
		if(isset($_GET['redirect'])) $redirect = $_GET['redirect'];
		if($this->getAuthService()->hasIdentity()) {
			return $this->redirect()->toRoute('home');
		}
		
		$_form = $this->getForm();
		$_form->setAttribute('action', $this->url()->fromRoute('auth/authenticate'));
		if(isset($redirect)) $_form->get('redirect')->setValue($redirect);

		$viewModel = new ViewModel([
			'form'		=> $_form,
			'messages'	=> $this->flashmessenger()->getMessages(),
		]);
		$viewModel->setTemplate('auth/login');
		return $viewModel;
//		return array(
//			'form'		=> $_form,
//			'messages'	=> $this->flashmessenger()->getMessages(),
//		);
	}
	
	public function authenticateAction() {
		$_form = $this->getForm();
		$_redirect = 'auth/login';
		
		$_request = $this->getRequest();
		if($_request->isPost()) {
			$_form->setData($_request->getPost());
			if($_form->isValid()) {
				$authService = $this->getAuthService();
				$authService->setIdentity($_request->getPost('username'));
				$authService->setCredential($_request->getPost('password'));

				$_result = $authService->authenticate();

				foreach($_result->getMessages() as $message) {
					$this->flashmessenger()->addMessage($message);
				}
				
				if($_result->isValid()) {
					$_redirect = 'home';
					
					if($_request->getPost('rememberMe') == 1) {
						$this->getSessionStorage()->setRememberMe(1);
						$this->getAuthService()->setStorage($this->getSessionStorage());
					}
					
					$user = $this->getTable('user')->getUser($this->getAuthService()->getIdentity());
					
					if(!$user) { // Probably elfag-user and it's the first login
						return $this->redirect()->toRoute('createUser');
					}
					
					
					$user->last_login = time();
					$this->getTable('user')->save($user);
					
					// Make sure current_group is set
					if($user->current_group == null) {
						if(count($user->access) > 1) return $this->redirect()->toRoute('user', array('action' => 'selectgroup'), array('query' => array('redirect' => $_POST['redirect'])));
						elseif(count($user->access) == 1) {
							$user->current_group = key($user->access);
							$this->getTable('user')->save($user);
						}
						else {
							$this->redirect()->toRoute('user', array('action' => 'noaccess'));
						}
					}
					if(!isset($user->access[$user->current_group])) return $this->redirect()->toRoute('user', array('action' => 'selectgroup'), array('query' => array('redirect' => $_POST['redirect'])));
					
					if(isset($_POST['redirect']) && strlen($_POST['redirect'])) {
						return $this->redirect()->toUrl($_POST['redirect']);
					}
					else {
						return $this->redirect()->toRoute($_redirect);
					}
				}
			}
		}
		
		return $this->redirect()->toRoute($_redirect);
	}
	
	public function generatebcryptAction() {
		$cost = $this->params()->fromPost('cost', 13);
		$password = $this->params()->fromPost('password', null);
		
		$request = $this->getRequest();
		if($request->isPost()) {
			$post = $request->getPost();
			$bcrypt = new Bcrypt();
			$bcrypt->setCost($post['cost']);
			$time = microtime(true);
			$hash = $bcrypt->create($post['password']);
			$time = microtime(true) - $time;
		}
		
		return array('hash' => $hash, 'time' => $time, 'cost' => $cost);
	}
	
	public function logoutAction() {
		$this->getSessionStorage()->forgetMe();
		$this->getAuthService()->clearIdentity();
		
		$this->flashmessenger()->addMessage('Du er logget ut');
		return $this->redirect()->toRoute('auth/login');
	}
	
	public function createuserAction() {
		
	}
	
	public function getForm() {
		if(!$this->form) {
			$this->form = $this->getServiceLocator()->get('Acl\Form\LoginForm');
		}
	
		return $this->form;
	}
	
	public function getAuthService() {
		if(!$this->authService) {
			$this->authService = $this->getServiceLocator()->get('Acl\AuthService');
		}
		
		return $this->authService;
	}
	
	public function getSessionStorage() {
		if(!$this->storage) {
			$this->storage = $this->getServiceLocator()->get('Acl\Model\AclStorage');
		}
		
		return $this->storage;
	}
	
	public function getTable($table) {
		if (!isset($this->tables[$table])) {
			$sm = $this->getServiceLocator();
			$table = ucfirst ($table);
			$this->tables[$table] = $sm->get ($table . 'Table');
		}
		return $this->tables [$table];
	}
	
}