<?php

namespace Acl\Controller;

use Acl\Model\Group;
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

	public function __construct($currentUser, $userTable, $groupTable)
	{
		$this->currentUser = $currentUser;
		$this->userTable = $userTable;
		$this->groupTable = $groupTable;
	}

//	public function onDispatch(\Zend\Mvc\MvcEvent $e) {
//		//$this->getServiceLocator()->get('ViewHelperManager')->get('menu')->mainMenu($this->getServiceLocator()->get('Acl\AuthService')->hasIdentity(), 'settings');
//		//MenuWidget::mainMenu($this->getServiceLocator()->get('Acl\AuthService')->hasIdentity(), 'settings');
//		return parent::onDispatch($e);
//	}
	
	public function viewAction() {
		//$user = $this->currentUser;
	}
	
	public function listAction() {
		//$this->getServiceLocator()->get('ViewHelperManager')->get('menu')->settingsMenu('users');
		//MenuWidget::settingsMenu('users');
		$user = $this->currentUser;
		return new ViewModel(array(
			'currentUser' => $user,
			'users' => $this->userTable->getUsers(),
			'group' => $this->groupTable->getGroup($user->current_group),
			
		));
	}
	
	public function editAction()
	{
		$id = (int) $this->params()->fromRoute('id', null);
		if($id == null) $this->redirect()->toRoute('user', array('action' => 'list'));
		
		$user = $this->userTable->getUser($id);
		$currentUser = $this->currentUser;
		$group = $this->groupTable->getGroup($currentUser->current_group);
		
		switch($user->logintype) {
			case 'soap':
				$form = new EditSoapUserForm($user);
				break;
			default:
				$form = new EditUserForm($user, $group);
				break;
		}
		$form->bind($user);
		$form->get('access_level')->setValue($user->access[$currentUser->current_group]['access_level']);
		
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
			if(in_array($user->logintype, array('default', 'elfag'))) {
				$form->get('email')->setAttribute('disabled', 'disabled');
				$form->get('old_password')->setAttribute('disabled', 'disabled');
				$form->get('new_password')->setAttribute('disabled', 'disabled');
				$form->get('new_password_confirm')->setAttribute('disabled', 'disabled');
				$form->get('name')->setAttribute('disabled', 'disabled');
			}
			else { // SOAP
				
			}
				
			if($currentUser->getAccessLevel() > 3 && $currentUser->getAccessLevel() > $user->getAccessLevel($currentUser->current_group)) {
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
							$authAdapter = $this->getServiceLocator()->get('Acl\AuthLocalAdapter');
							$authAdapter->setIdentity($user->username);
							$authAdapter->setCredential($form->get('old_password')->getValue());
							if($authAdapter->authenticate()->isValid()) {
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
					$user->updateAccess($group, $form->get('access_level')->getValue());
					$this->userTable->saveUserAccess($user, $group);
				}
				
				$this->userTable->save($user);
				
				// Redirect to view
				if($valid) return $this->redirect()->toRoute('user', array('action' => 'list'));
			}
		}
		$viewmodel = new ViewModel(array('form' => $form));
		//$this->getServiceLocator()->get('ViewHelperManager')->get('menu')->settingsMenu('users');
		//MenuWidget::settingsMenu('user');

		return $viewmodel;
	}
		
	public function editaccessAction() {
		$groupName = (int) $this->params()->fromRoute('id', null);
		if(!$groupName) $groupName = $this->currentUser->current_group;
		if($groupName == null) $this->redirect()->toRoute('user', array('action' => 'list'));

		/** @var Request $request */
		$request = $this->getRequest();
		if ($request->isPost()) {
			$post = $request->getpost()->toArray();
			foreach($post['users'] AS $key => $value) {
				$user = $this->userTable->getUser((int) $key);
				if($user !== false) {
					$user->updateAccess($groupName, $value);
					$this->userTable->saveUserAccess($user, $groupName);
				}
			}
			$this->redirect()->toRoute('user', array('action' => 'list'));
		}
		
		$group = $this->groupTable->getGroup($groupName);
		
		$users = $this->userTable->getUsers();
		// List only users with access to this group
		$editusers = array();
		foreach($users AS $user) {
			if(isset($user->access[$group->group])) {
				$editusers[] = $user;
			}
		}
		
		$access = array();
		foreach($editusers AS $user) {
			$access[$user->id] = $this->userTable->accessToSaveAccess($user, $group->group);
		}
		
		//MenuWidget::settingsMenu('users');
		return new ViewModel(array(
			'users' => $editusers,
			'currentUser' => $this->currentUser,
			'group' => $group,
			'access' => $access,
		));
	}

	public function createElfagUserAction() {
	    if($this->currentUser->logintype != 'elfag') {
	        Message::create(3, 'Ikke elfagbruker, kan ikke opprette');
	        $this->redirect()->toRoute('home');
        }

        $user = $this->currentUser;
        // Create user
        $user->name = 'Systembruker';
        $userId = $this->userTable->save($user);
        $user->id = $userId;

        $groupName = $this->currentUser->username;

        $group = $this->groupTable->getGroup($groupName);
        if(!$group) {
            $group = new Group();
            $group->group = $groupName;
            $groupId = $this->groupTable->save($group);
            $group->id = $groupId;
        }

        // Add user to group
        $this->userTable->addUserToGroup($user, $group);
        // Add access
        $user->updateAccess($group->group, array('access_level' => 5, 'onnshop' => 0));
        $this->userTable->saveUserAccess($user, $group);



        $this->redirect()->toRoute('createUser');

    }

	public function createUserAction() {
        $id = $this->params()->fromRoute('id');
        if($id == null) $this->redirect()->toRoute('user', array('action' => 'list'));

        $group = $this->groupTable->getGroup($id);

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
				$this->userTable->addUserToGroup($user, $group);
				// Add access
				$user->updateAccess($group->group, array('access_level' => $data['access_level'], 'onnshop' => $data['onnshop']));
				$this->userTable->saveUserAccess($user, $group);
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


	
	public function createSoapUserAction() {
		$id = $this->params()->fromRoute('id', null);
		if(!$id) $id = $this->currentUser->current_group;
		if($id == null) $this->redirect()->toRoute('user');
		$group = $this->groupTable->getGroup($id);

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
					$user->updateAccess($group, $form->get('access_level')->getValue());
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
	
	public function selectGroupAction() {
		$redirect = '';
		if(isset($_POST['redirect'])) $redirect = $_POST['redirect'];
		elseif(isset($_GET['redirect'])) $redirect = $_GET['redirect'];
		
		$user = $this->currentUser;
		$groups = $this->groupTable->getGroups();
		
		// Only one group available
		if(count($groups) == 1) {
			$user->current_group = $groups[0]->group;
			$this->userTable->save($user);
			if(strlen($redirect)) return $this->redirect()->toUrl($redirect);
			else return $this->redirect()->toRoute('home');
		}

		/** @var Request $request */
		$request = $this->getRequest();
		if($request->isPost()) {
			$post = $request->getPost();
			
			if(isset($post['group']) && isset($user->access[$post['group']])) {
				$user->current_group = $post['group'];
				$this->userTable->save($user);
			}
			
			if(strlen($redirect)) return $this->redirect()->toUrl($redirect);
			else return $this->redirect()->toRoute('home');
		}
		
		return array(
			'redirect' => $redirect,
			'user' => $user,
			'groups' => $groups,
		);
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