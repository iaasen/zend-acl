Events fired by this module
===========================

### Acl\Controller\AuthController::login_successful
* Fired after successful login attempt.
* If a Response object is returned AuthController will redirect to that URL
* When using this event, be aware that Factories may have already instantiated its object before the User instance was set by AuthController.

### Acl\Adapter\AuthElfag2Adapter::user_elfag2_logged_in
* Fired end an elfag2 (ludens) user is authenticated
* Used by Acl\Service\Elfag2Service to create the user if needed

Setup
=====
* Suggested menu options are listed in config/acl.global.php.dist