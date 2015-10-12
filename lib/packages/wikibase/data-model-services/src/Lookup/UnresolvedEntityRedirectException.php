<?php

namespace Wikibase\DataModel\Services\Lookup;

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
class UnresolvedEntityRedirectException extends EntityLookupException {

	/**
	 * @var EntityId
	 */
	private $redirectTargetId;

	/**
	 * @param EntityId $entityId
	 * @param EntityId $redirectTargetId The ID of the target Entity of the redirect
	 */
	public function __construct( EntityId $entityId, EntityId $redirectTargetId ) {
		parent::__construct( $entityId, 'Unresolved redirect to ' . $redirectTargetId->getSerialization() );

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
