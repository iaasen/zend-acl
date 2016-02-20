<?php
namespace Acl\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\View\Model\ViewModel;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;


class GroupselectionWidget extends AbstractHelper implements ServiceLocatorAwareInterface
{
	protected $serviceLocator;
	
	public function __invoke() {
		$view = $this->getView();
		$currentUser = $this->getServiceLocator()->get('UserTable')->getCurrentUser();
		if($currentUser) {
			$currentGroup = $this->getServiceLocator()->get('GroupTable')->getGroup($currentUser->current_group);
		}
		else $currentGroup = false;
		
		
		$viewModel = new ViewModel();
		$viewModel->setTemplate('acl/widget/groupselection');
		//$viewModel->setVariable('hasIdentity', $this->authService->hasIdentity());
		$viewModel->setVariable('user', $currentUser);
		$viewModel->setVariable('group', $currentGroup);
		$rendered = $view->render($viewModel);
		
		return $rendered;
		//return $this->getView()->render($viewModel);
	}
	
	public function getCurrentUser() {
		return $this->getServiceLocator()->get('UserTable')->getCurrentUser();
	}
	
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
		$this->serviceLocator = $serviceLocator->getServiceLocator();
	}
	
	public function getServiceLocator() {
		return $this->serviceLocator;
	}
}
