<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 09.06.2018
 * Time: 17:07
 */

namespace Acl\Initializer;


use Acl\Model\User;

interface CurrentUserAwareInterface
{
	public function setCurrentUser(User $currentUser);
}