<?php

namespace Wikibase\DataModel\Services\Lookup;

use Exception;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Exception indicating that an attempt was made to access a redirected EntityId
 * without resolving the redirect first.
 *
 * @since 1.1
 *
 * @license GPL-2.0+
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
	 * @param string|null $message Added in 3.1
	 * @param Exception|null $previous Added in 3.1
	 */
	public function __construct(
		EntityId $entityId,
		EntityId $redirectTargetId,
		$message = null,
		Exception $previous = null
	) {
		parent::__construct(
			$entityId,
			$message !== null ? $message : 'Unresolved redirect to ' . $redirectTargetId->getSerialization(),
			$previous
		);

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
