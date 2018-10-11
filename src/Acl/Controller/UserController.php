<?php

namespace Acl\Controller;

use Acl\Adapter\AuthLocalAdapter;
use Acl\Service\Elfag2Service;
use Iaasen\Exception\NotFoundException;
use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Acl\Form\CreateUserForm;
use Acl\Form\EditUserForm;
use Acl\Form\EditSoapUserForm;
use Acl\Model\User;
use Zend\Crypt\Password\Bcrypt;
use Oppned\Message;

/**
 * UserController
 *
 * @author
 *
 * @version
 *
 */
class UserController extends AbstractActionController {
	protected $tables;
	protected static $bcryptCost = 13;

	/** @var  \Acl\Model\User */
	protected $currentUser;
	/** @var  \Acl\Service\UserTable */
	protected $userTable;
	/** @var  \Acl\Service\GroupTable */
	protected $groupTable;
	/** @var  \Acl\Service\UserService */
	protected $userService;
	/** @var Elfag2Service */
	protected $elfag2Service;
	/** @var AuthLocalAdapter */
	protected $authLocalAdapter;

	public function __construct($currentUser, $userTable, $groupTable, $userService, $elfag2Service, $authLocalAdapter)
	{
		$this->currentUser = $currentUser;
		$this->userTable = $userTable;
		$this->groupTable = $groupTable;
		$this->userService = $userService;
		$this->elfag2Service = $elfag2Service;
		$this->authLocalAdapter = $authLocalAdapter;
	}

//	public function onDispatch(\Zend\Mvc\MvcEvent $e) {
//		//$this->getServiceLocator()->get('ViewHelperManager')->get('menu')->mainMenu($this->getServiceLocator()->get('Acl\AuthService')->hasIdentity(), 'settings');
//		//MenuWidget::mainMenu($this->getServiceLocator()->get('Acl\AuthService')->hasIdentity(), 'settings');
//		return parent::onDispatch($e);
//	}
	
	public function viewAction() {
	}
	
	public function listAction() {
		return new ViewModel(array(
			'currentUser' => $this->currentUser,
			'users' => $this->userService->getUsersByCurrentGroup(),
			'group' => $this->userService->getCurrentGroup(),
		));
	}
	
	public function editAction()
	{
		$id = $this->params()->fromRoute('id');
		if($id == null) $this->redirect()->toRoute('user', array('action' => 'list'));

		$currentUser = $this->currentUser;
		$user = $this->userService->getUserById($id);
		$group = $this->userService->getCurrentGroup();

		switch($user->logintype) {
			case 'soap':
				$form = new EditSoapUserForm($user);
				break;
			default:
				$form = new EditUserForm($user);
				break;
		}
		$form->bind($user);
		$form->get('access_level')->setValue($user->getAccessLevel($group->group));
		
		// Disable fields that user should not have access to
		if($user->username == $currentUser->username) {
			$form->get('username')->setAttribute('disabled', 'disabled');
			$form->get('access_level')->setAttribute('disabled', 'disabled');
			if($user->logintype != 'default') {
				$form->get('old_password')->setAttribute('disabled', 'disabled');
				$form->get('new_password')->setAttribute('disabled', 'disabled');
				$form->get('new_password_confirm')->setAttribute('disabled', 'disabled');
			}
				
		}
		else {
			$form->get('username')->setAttribute('disabled', 'disabled');
			if(in_array($user->logintype, ['default', 'elfag'])) {
				$form->get('email')->setAttribute('disabled', 'disabled');
				$form->get('old_password')->setAttribute('disabled', 'disabled');
				$form->get('new_password')->setAttribute('disabled', 'disabled');
				$form->get('new_password_confirm')->setAttribute('disabled', 'disabled');
				$form->get('name')->setAttribute('disabled', 'disabled');
			}
			else { // SOAP
				
			}

			if($currentUser->getAccessLevel() > 3 && $currentUser->getAccessLevel() > $user->getAccessLevel($group->group)) {
				$valueOptions = $form->get('access_level')->getValueOptions();
				
				if(in_array($user->logintype, array('default', 'elfag'))) {
					for($i = count($currentUser::$access_level); $i > $currentUser->getAccessLevel(); $i--) {
						$valueOptions[$i-1]['disabled'] = 'disabled';
					}
				}
				elseif($user->logintype == 'soap') {
					$valueOptions[2]['disabled'] = 'disabled';
					$valueOptions[4]['disabled'] = 'disabled';
					$valueOptions[5]['disabled'] = 'disabled';
				}
				else { // Unknown account type
					$form->get('access_level')->setAttribute('disabled', 'disabled');
				}
				$form->get('access_level')->setValueOptions($valueOptions);
				//~r($form);
			}
			else {
				$form->get('access_level')->setAttribute('disabled', 'disabled');
			}
		}

		/** @var Request $request */
		$request = $this->getRequest();
		
		if ($request->isPost()) {
			$form->setData($request->getPost());
			
			if ($form->isValid()) {
				$valid = true;
				//$formdata = $form->getData();

				// New password?
				if(strlen($form->get('new_password')->getValue())) {
					$typingConfirmed = false;
					if($user->logintype == 'soap') {
						$typingConfirmed = true;
					}
					else { // local
						if($form->get('new_password')->getValue() == $form->get('new_password_confirm')->getValue()) {
							$this->authLocalAdapter->setIdentity($user->username);
							$this->authLocalAdapter->setCredential($form->get('old_password')->getValue());
							if($this->authLocalAdapter->authenticate()->isValid()) {
								$typingConfirmed = true;
							}
							else {
								$messages = $form->get('old_password')->getMessages();
								$messages[] = 'Feil passord';
								$form->get('old_password')->setMessages($messages);
								$valid = false;
							}
						}
						else {
							$messages = $form->get('new_password_confirm')->getMessages();
							$messages[] = 'Passordene er ikke like';
							$form->get('new_password_confirm')->setMessages($messages);
							$valid = false;
						}
					}
					
					//Set the password
					if($typingConfirmed) {
						$bcrypt = new Bcrypt();
						$bcrypt->setCost(13);
						$user->password = $bcrypt->create($form->get('new_password')->getValue());
					}
				}
				
				// User access setting
				if(
					strlen($form->get('access_level')->getValue()) &&
					$form->get('access_level')->getValue() != $user->getAccessLevel($group->group)
				) {
					$user->setAccessLevel($group, $form->get('access_level')->getValue());
					$this->userService->saveUserAccess($user, $group);
				}
				
				$this->userService->saveUser($user);
				
				// Redirect to view
				if($valid) return $this->redirect()->toRoute('user', array('action' => 'list'));
			}
		}
		return ['form' => $form];
	}

	/**
	 * TODO editAccessAction not currently working
	 */
	public function editAccessAction() {
//		$groupName = $this->params()->fromRoute('id', null);
//		if(!$groupName) $groupName = $this->currentUser->current_group;
//		if($groupName == null) $this->redirect()->toRoute('user', array('action' => 'list'));
		$groupName = $this->currentUser->current_group;
		$group = $this->userService->getCurrentGroup();


		/** @var Request $request */
		$request = $this->getRequest();
		if ($request->isPost()) {
			$post = $request->getpost()->toArray();
			~r($post);
			foreach($post['users'] AS $key => $value) {

				$user = $this->userService->getUserById((int) $key);
				if($user !== false) {
					$user->setAccess($groupName, $value);
					$this->userTable->saveUserAccess($user, $groupName);
				}
			}
			$this->redirect()->toRoute('user', array('action' => 'list'));
		}

		$users = $this->userService->getUsersByCurrentGroup();

		// List only users with access to this group
		$editUsers = [];
		foreach($users AS $user) {
			if($user->getAccessLevel($group->group)) {
				$editUsers[] = $user;
			}
		}
		
		$access = [];
		foreach($editUsers AS $user) {
			$access[$user->id] = $this->userService->accessToSaveAccess($user, $group->group);
		}
		
		return new ViewModel(array(
			'users' => $editUsers,
			'currentUser' => $this->currentUser,
			'group' => $group,
			'access' => $access,
		));
	}


	/**
	 * TODO createUserAction not currently working
	 */
	public function createUserAction() {
        $id = $this->params()->fromRoute('id');
        if($id == null) $this->redirect()->toRoute('user', array('action' => 'list'));

        $group = $this->userService->getGroupByName($id);

		if(!$this->userTable->accessToCreateUser($group))
			$this->redirect()->toRoute('user', array('action' => 'list'));
		
		$form = new CreateUserForm();
		$form->setData(array('group' => $group->group));
		
		// Disable unavailable access_levels
		$newoptions = array();
		foreach($form->get('access_level')->getValueOptions() AS $key => $value) {
			if($key < $this->currentUser->access[$group->group]['access_level'])
				$newoptions[] = array('value' => $key, 'label' => $value);
			else
				$newoptions[] = array('value' => $key, 'label' => $value, 'disabled' => 'disabled');
		}
		$form->get('access_level')->setValueOptions($newoptions);
		
		/** @var Request $request */
		$request = $this->getRequest();
		if ($request->isPost()) {
			$post = $request->getpost();
			$form->setData($post);
			
			if($form->isValid()) {
				$data = $form->getData();
				$user = $this->userTable->getUserByEmail($data['email']);
				if($user !== false) {
					// User already member of group
					if(isset($user->access[$group->group])) {
						$this->flashMessenger()->addMessage($user->name . ' er allerede medlem av gruppen');
						return $this->redirect()->toRoute('user');
					}
				}
				else {
				// Create user
				$user = new User();
				$user->name = $data['name'];
				$user->email = $data['email'];
				$user = $this->userTable->create($user);
				}
				// Add user to gruop
				$this->userService->addUserToGroup($user, $group);
				// Add access
				$user->setAccess($group->group, array('access_level' => $data['access_level'], 'onnshop' => $data['onnshop']));
				$this->userService->saveUserAccess($user, $group);
				// Redirect to user list
				return $this->redirect()->toRoute('user');
			}
		}
		
		return new ViewModel(array(
			'currentUser' => $this->currentUser,
			'group' => $group,
			'form' => $form,
		));
	}


	/**
	 * TODO createSoapUser is not currently working
	 */
	public function createSoapUserAction() {
		$id = $this->params()->fromRoute('id', null);
		if(!$id) $id = $this->currentUser->current_group;
		if($id == null) $this->redirect()->toRoute('user');
		$group = $this->userService->getGroupByName($id);

		$user = new User();
		$user->username = preg_replace('/^elfag-/', '', $group->group, 1) . '-visma';
		$user->password = substr(md5(rand()), 0, 16);
		$user->name = 'Visma';
		$user->current_group = $this->currentUser->current_group;
		
		if(!$this->userTable->accessToCreateUser($group)) {
			Message::create(3, 'You have no access to create user accounts for this group');
			$this->redirect()->toRoute('user', array('action' => 'list'));
		}
		
		$form = new EditSoapUserForm($user);
		$form->bind($user);
		$form->setData(array('new_password' => $user->password, 'access_level' => 1));

		/** @var Request $request */
		$request = $this->getRequest();
		if ($request->isPost()) {
			$form->setData($request->getPost());
			//$form->setData($post);
			
			if($form->isValid()) {
				$valid = true;
				
				$user->logintype = 'soap';
				$user->current_group = $group->group;
				
				$uniqueUsername = $this->userTable->getUniqueUsername($user->username);
				if($uniqueUsername != $user->username) {
					$messages = $form->get('username')->getMessages();
					$messages[] = sprintf(_('The username \'%s\' is not available. Suggesting a different username'), $user->username);
					$form->get('username')->setMessages($messages);
					$form->get('username')->setValue($uniqueUsername);
					$valid = false;
				}
				if($valid) {
					$bcrypt = new Bcrypt();
					$bcrypt->setCost(13);
					$user->password = $bcrypt->create($form->get('new_password')->getValue());
					$user->setAccess($group, $form->get('access_level')->getValue());
					$userId = $this->userTable->save($user);
					$user->id = $userId;
					$this->userTable->addUserToGroup($user, $group);
					$this->userTable->saveUserAccess($user, $group);
					// Redirect to user list
					return $this->redirect()->toRoute('user');
				}
			}
		}
		
		return new ViewModel(array(
			'currentUser' => $this->currentUser,
			'group' => $group,
			'form' => $form,
		));
	}

	/**
	 * @return array|\Zend\Http\Response
	 * @throws \Exception
	 */
	public function createElfag2GroupAction() {
		$user = $this->currentUser;

		$groups = [];
		if(is_numeric($user->ludens_company->org_number)) {
			try {
				$group = $this->userService->getGroupByOrgNumber((int) $user->ludens_company->org_number);
				$groups = [$group];
			}
			catch(NotFoundException $e) {}
		}

		/** @var Request $request */
		$request = $this->getRequest();
		if($request->isPost()) {
			$answer = $request->getPost()->get('company');

			if($answer == 'new') {
				$this->elfag2Service->createGroupFromUser($user);
			}
			elseif($answer == 'missing') {
				$this->elfag2Service->sendEmailAboutMissingGroup($user);
				return $this->redirect()->toRoute('user/missing-elfag2-group');
			}
			elseif(is_numeric($answer)) {
				$answer = (int) $answer;
				foreach($groups AS $group) {
					if($group->id == $answer) $this->elfag2Service->connectUserToGroup($user, $group);
				}
			}

			return $this->redirect()->toRoute('home');
		}

		return [
			'groups' => $groups,
			'user' => $user,
		];
	}

	public function missingElfag2GroupAction() {

	}
	
	public function selectGroupAction() {
		$redirect = $this->params()->fromPost('redirect', $this->params()->fromQuery('redirect', ''));

		$currentUser = $this->currentUser;
		$groups = $this->userService->getGroupsByCurrentUser();

		// Only one group available
		if(count($groups) == 1) {
			$currentUser->current_group = $groups[0]->group;
			$this->userService->saveUser($currentUser);
			if(strlen($redirect)) return $this->redirect()->toUrl($redirect);
			else return $this->redirect()->toRoute('home');
		}

		/** @var Request $request */
		$request = $this->getRequest();
		if($request->isPost()) {
			$post = $request->getPost();

			if(isset($post['group']) && $currentUser->getAccessLevel($post['group'])) {
				$currentUser->current_group = $post['group'];
				$this->userService->saveUser($currentUser);
			}
			
			if(strlen($redirect)) return $this->redirect()->toUrl($redirect);
			else return $this->redirect()->toRoute('home');
		}
		
		return [
			'redirect' => $redirect,
			'user' => $currentUser,
			'groups' => $groups,
		];
	}

	public function noAccessAction() {
		$currentUser = $this->userService->getCurrentUser();
		return [
			'currentUser' => $currentUser,
		];
	}
}