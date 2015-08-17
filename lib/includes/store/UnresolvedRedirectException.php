<?php

namespace Wikibase\Lib\Store;

use Exception;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Exception indicating that an attempt was made to access a redirected EntityId
 * without resolving the redirect first.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UnresolvedRedirectException extends Exception {

	/**
	 * @var EntityId
	 */
	private $redirectTargetId;

	/**
	 * @var int
	 */
	private $revisionId;

	/**
	 * @var string
	 */
	private $revisionTimestamp;

	/**
	 * @param EntityId $redirectTargetId The ID of the target Entity of the redirect
	 * @param int $revisionId
	 * @param string $revisionTimestamp
	 */
	public function __construct( EntityId $redirectTargetId, $revisionId = 0, $revisionTimestamp = '' ) {
		parent::__construct( "Unresolved redirect to " . $redirectTargetId->getSerialization() );

		$this->redirectTargetId = $redirectTargetId;
		$this->revisionId = $revisionId;
		$this->revisionTimestamp = $revisionTimestamp;
	}

	/**
	 * Returns the ID of the entity referenced by the redirect.
	 *
	 * @return EntityId
	 */
	public function getRedirectTargetId() {
		return $this->redirectTargetId;
	}

	/**
	 * @return int
	 */
	public function getRevisionId() {
		return $this->revisionId;
	}

	/**
	 * @return string
	 */
	public function getRevisionTimestamp() {
		return $this->revisionTimestamp;
	}

}
