<?php

namespace Wikibase\Repo\Api;

use MediaWiki\Status\Status;
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
	 * @param string $message
	 */
	public function __construct( Status $status, string $message = "" ) {
		parent::__construct( $message );
		Assert::parameter( $status->getMessages( 'error' ) !== [], "status", "must have errors" );
		$this->status = $status;
	}

	/**
	 * Error status as returned by the backend driver
	 */
	public function getStatus(): Status {
		return $this->status;
	}

}
