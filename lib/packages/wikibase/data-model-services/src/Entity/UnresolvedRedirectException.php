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
	 * @param EntityId $redirectTargetId The ID of the target Entity of the redirect
	 */
	public function __construct( EntityId $redirectTargetId ) {
		parent::__construct( "Unresolved redirect to " . $redirectTargetId->getSerialization() );

		$this->redirectTargetId = $redirectTargetId;
	}

	/**
	 * Returns the ID of the entity referenced by the redirect.
	 *
	 * @return EntityId
	 */
	public function getRedirectTargetId() {
		return $this->redirectTargetId;
	}

}
