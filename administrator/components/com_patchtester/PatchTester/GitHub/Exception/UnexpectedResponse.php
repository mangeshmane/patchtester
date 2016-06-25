<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\GitHub\Exception;

/**
 * Exception representing an unexpected response
 *
 * @since  __DEPLOY_VERSION__
 */
class UnexpectedResponse extends \DomainException
{
	/**
	 * The Response object.
	 *
	 * @var    \JHttpResponse
	 * @since  __DEPLOY_VERSION__
	 */
	private $response;

	/**
	 * Constructor
	 *
	 * @param   \JHttpResponse  $response  The Response object.
	 * @param   string          $message   The Exception message to throw.
	 * @param   integer         $code      The Exception code.
	 * @param   \Exception      $previous  The previous exception used for the exception chaining.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(\JHttpResponse $response, $message = '', $code = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);

		$this->response = $response;
	}

	/**
	 * Get the Response object.
	 *
	 * @return  \JHttpResponse
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getResponse()
	{
		return $this->response;
	}
}
