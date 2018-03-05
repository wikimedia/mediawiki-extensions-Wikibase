<?php

namespace Wikibase\Repo\Store;

use InvalidArgumentException;
use Title;
use Wikibase\DataModel\Entity\EntityId;

/**
 * An EntityTitleStoreLookup that guarantees to return the titles of pages that actually store the
 * entities, and does dispatching based on the entity type.
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityTitleStoreLookup implements EntityTitleStoreLookup {

	/**
	 * @var array indexed by entity type
	 */
	private $lookups;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $defaultLookup;

	/**
	 * @param callable[] $callbacks indexed by entity type
	 * @param EntityTitleStoreLookup $defaultLookup
	 */
	public function __construct( array $callbacks, EntityTitleStoreLookup $defaultLookup ) {
		$this->lookups = $callbacks;
		$this->defaultLookup = $defaultLookup;
	}

	/**
	 * @param EntityId $id
	 *
	 * @return Title|null
	 */
	public function getTitleForId( EntityId $id ) {
		return $this->getLookup( $id->getEntityType() )->getTitleForId( $id );
	}

	/**
	 * @param string $entityType
	 *
	 * @throws InvalidArgumentException
	 * @return EntityTitleStoreLookup
	 */
	private function getLookup( $entityType ) {
		if ( !array_key_exists( $entityType, $this->lookups ) ) {
			return $this->defaultLookup;
		}

		if ( is_callable( $this->lookups[$entityType] ) ) {
			$this->lookups[$entityType] = call_user_func(
				$this->lookups[$entityType],
				$this->defaultLookup
			);

			if ( !( $this->lookups[$entityType] instanceof EntityTitleStoreLookup ) ) {
				throw new InvalidArgumentException(
					"Callback provided for $entityType did not create an EntityTitleStoreLookup"
				);
			}
		}

		return $this->lookups[$entityType];
	}

}
