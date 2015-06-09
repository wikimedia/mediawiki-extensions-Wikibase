<?php

namespace Wikibase\Lib\Store;

use Exception;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Exception indicating that an attempt was made to access a redirected EntityId
 * without resolving the redirect first.
 * Includes revision information for the redirect data.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UnresolvedRedirectRevisionException extends UnresolvedRedirectException {

	/**
	 * @var int
	 */
	private $revisionId;

	/**
	 * @var string
	 */
	private $mwTimestamp;

	/**
	 * @param EntityId $redirectTargetId The ID of the target Entity of the redirect
	 * @param int $revisionId Revision ID or 0 for none
	 * @param string $mwTimestamp in MediaWiki format or an empty string for none
	 * @param string|null $message
	 * @param int $code
	 * @param Exception|null $previous
	 */
	public function __construct( EntityId $redirectTargetId, $revisionId = 0, $mwTimestamp = '',
			$message = null, $code = 0, Exception $previous = null ) {
		parent::__construct( $redirectTargetId, $message, $code, $previous );

		$this->revisionId = $revisionId;
		$this->mwTimestamp = $mwTimestamp;
	}

	/**
	 * @see Revision::getId
	 *
	 * @return int
	 */
	public function getRevisionId() {
		return $this->revisionId;
	}

	/**
	 * @see Revision::getTimestamp
	 *
	 * @return string in MediaWiki format or an empty string
	 */
	public function getTimestamp() {
		return $this->mwTimestamp;
	}
}
