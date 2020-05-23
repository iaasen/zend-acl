<?php
namespace Acl\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\View\Model\ViewModel;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\ServiceLocatorAwareInterface;


class GroupSelectionWidget extends AbstractHelper
{
	/** @var  \Acl\Service\UserService */
	protected $userService;

	public function __construct($userService)
	{
		$this->userService = $userService;
	}

	public function __invoke() {
		$view = $this->getView();
		$currentUser = $this->userService->getCurrentUser();
		if($currentUser) {
			$currentGroup = $this->userService->getCurrentGroup();
		}
	else $currentGroup = false;


		$viewModel = new ViewModel();
		$viewModel->setTemplate('acl/widget/groupSelection');
		//$viewModel->setVariable('hasIdentity', $this->authService->hasIdentity());
		$viewModel->setVariable('user', $currentUser);
		$viewModel->setVariable('group', $currentGroup);
		$rendered = $view->render($viewModel);
		return $rendered;
		//return $this->getView()->render($viewModel);
	}
}
