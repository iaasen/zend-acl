<?php
/**
 * Created by PhpStorm.
 * User: iaase
 * Date: 06.11.2017
 * Time: 22.48
 */

namespace Acl\Service;


use Iaasen\Transport\GuzzleHttpTransport;

class Elfag2Transport extends GuzzleHttpTransport
{

	/**
	 * Is run before each request
	 * @return void
	 */
	protected function checkSession()
	{
	}

	/**
	 * Is run if error code is given from server
	 * Request is attempted a second time if function returns true
	 * If function returns false the API error will be forwarded
	 * @return bool
	 */
	protected function renewSession()
	{
		return false;
	}
}