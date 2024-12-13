<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 2.1
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityAccessLimitException extends EntityLookupException {
	private int $entityAccessCount;
	private int $entityAccessLimit;

	public function __construct( EntityId $entityId, int $entityAccessCount, int $entityAccessLimit ) {
		parent::__construct(
			$entityId,
			'Too many entities loaded, must not load more than ' . $entityAccessLimit . ' entities.'
		);
		$this->entityAccessCount = $entityAccessCount;
		$this->entityAccessLimit = $entityAccessLimit;
	}

	public function getEntityAccessCount(): int {
		return $this->entityAccessCount;
	}

	public function getEntityAccessLimit(): int {
		return $this->entityAccessLimit;
	}
}
