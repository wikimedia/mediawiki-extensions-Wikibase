<?php

namespace Wikibase\Lib\Store;

use Exception;
use MWException;
use Status;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class StorageException extends MWException {

	/**
	 * @var Status|null
	 */
	private $status = null;

	/**
	 * @param string|Status $status
	 * @param int $code
	 * @param Exception|null $previous
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
	 * @return Status|null
	 */
	public function getStatus() {
		return $this->status;
	}

}
