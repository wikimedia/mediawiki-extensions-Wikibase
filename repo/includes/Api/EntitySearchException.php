<?php

namespace Wikibase\Repo\Api;

use Status;
use Wikimedia\Assert\Assert;

/**
 * Exception thrown by EntitySearchHelper implementation when an unrecoverable backend error occurs.
 *
 * @license GPL-2.0-or-later
 */
class EntitySearchException extends \Exception {
	/**
	 * @var Status
	 */
	private $status;

	/**
	 * @param Status $status status with errors
	 * @param String $message
	 */
	public function __construct( Status $status, string $message = "" ) {
		parent::__construct( $message );
		Assert::parameter( $status->getErrors() !== [], "status", "must have errors" );
		$this->status = $status;
	}

	/**
	 * Error status as returned by the backend driver
	 * @return Status
	 */
	public function getStatus(): Status {
		return $this->status;
	}

}
