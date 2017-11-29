<?php

namespace Wikibase\Repo\Store;

use InvalidArgumentException;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * An EntityTitleLookup that does dispatching based on the entity type.
 *
 * Warning! This class is build on the assumption that it is only instantiated once.
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityTitleLookup implements EntityTitleStoreLookup {

	/**
	 * @var array indexed by entity type
	 */
	private $lookups;

	/**
	 * @var EntityTitleLookup
	 */
	private $defaultLookup;

	/**
	 * @param callable[] $callbacks indexed by entity type
	 * @param EntityTitleLookup $defaultLookup
	 */
	public function __construct( array $callbacks, EntityTitleLookup $defaultLookup ) {
		$this->lookups = $callbacks;
		$this->defaultLookup = $defaultLookup;
	}

	/**
	 * @param EntityId $id
	 *
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		return $this->getLookup( $id->getEntityType() )->getTitleForId( $id );
	}

	/**
	 * @param string $entityType
	 *
	 * @throws InvalidArgumentException
	 * @return EntityTitleLookup
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

			if ( !( $this->lookups[$entityType] instanceof EntityTitleLookup ) ) {
				throw new InvalidArgumentException(
					"Callback provided for $entityType did not created an EntityTitleLookup"
				);
			}
		}

		return $this->lookups[$entityType];
	}

}
