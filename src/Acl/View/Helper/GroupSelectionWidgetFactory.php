<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 11.06.2017
 * Time: 20.52
 */

namespace Acl\View\Helper;


class GroupSelectionWidgetFactory
{
	/**
	 * @param \Zend\View\HelperPluginManager $helperManager
	 * @return GroupSelectionWidget
	 */
	public function __invoke($helperManager)
	{
		$sm = $helperManager->getServiceLocator();
		$userService = $sm->get(\Acl\Service\UserService::class);

		return new GroupSelectionWidget($userService);
	}

}