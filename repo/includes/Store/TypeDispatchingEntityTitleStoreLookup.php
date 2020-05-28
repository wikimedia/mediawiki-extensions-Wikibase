<?php

namespace Wikibase\Repo\Store;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceByTypeDispatcher;

/**
 * An EntityTitleStoreLookup that guarantees to return the titles of pages that actually store the
 * entities, and does dispatching based on the entity type.
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityTitleStoreLookup implements EntityTitleStoreLookup {

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $defaultLookup;

	/**
	 * @var ServiceByTypeDispatcher
	 */
	private $serviceDispatcher;

	/**
	 * @param callable[] $callbacks indexed by entity type
	 * @param EntityTitleStoreLookup $defaultLookup
	 */
	public function __construct( array $callbacks, EntityTitleStoreLookup $defaultLookup ) {
		$this->defaultLookup = $defaultLookup;
		$this->serviceDispatcher = new ServiceByTypeDispatcher( EntityTitleStoreLookup::class, $callbacks, $defaultLookup );
	}

	/**
	 * @param EntityId $id
	 *
	 * @return Title|null
	 */
	public function getTitleForId( EntityId $id ) {
		return $this->serviceDispatcher->getServiceForType( $id->getEntityType(), [ $this->defaultLookup ] )
			->getTitleForId( $id );
	}

	/**
	 * @inheritDoc
	 */
	public function getTitlesForIds( array $ids ) {
		$byType = $this->getIdsByType( $ids );

		$result = [];
		foreach ( $byType as $type => $idsForType ) {
			$result = array_merge(
				$result,
				$this->serviceDispatcher->getServiceForType( $type, [ $this->defaultLookup ] )->getTitlesForIds( $idsForType )
			);
		}

		return $result;
	}

	private function getIdsByType( array $ids ) {
		$byType = [];
		foreach ( $ids as $id ) {
			$byType[$id->getEntityType()][] = $id;
		}
		return $byType;
	}

}
