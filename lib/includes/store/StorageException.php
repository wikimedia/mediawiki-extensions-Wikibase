<?php
 /**
 *
 * Copyright © 06.06.13 by the authors listed below.
 *
 * @license GPL 2+
 *
 * @author Daniel Kinzler
 */


namespace Wikibase;
use Exception;
use Status;


/**
 * Class StorageException
 * @package Wikibase
 */

class StorageException extends \MWException {

	/**
	 * @var Status
	 */
	private $status;

	/**
	 * @param string|Status $status
	 * @param int $code
	 * @param Exception $previous
	 */
	public function __construct( $status = "", $code = 0, Exception $previous = null ) {

		if ( $status instanceof Status ) {
			$message = $status->getWikiText();
			$this->status = $status;
		} else {
			$message = $status;
		}

		parent::__construct( $message, $code, $previous );
	}

	/**
	 * @return \Status
	 */
	public function getStatus() {
		return $this->status;
	}

}