<?php

namespace Wikibase\DataModel\Services\Entity;

use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Exception indicating that an attempt was made to access a redirected EntityId
 * without resolving the redirect first.
 *
 * @since 1.1
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UnresolvedRedirectException extends RuntimeException {

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
