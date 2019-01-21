<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoBuilder;

/**
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityInfoBuilder implements EntityInfoBuilder {

	/**
	 * @var EntityInfoBuilder[]
	 */
	private $builders;

	public function __construct( array $builders ) {
		// TODO validate $builders
		$this->builders = $builders;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string[] $languageCodes
	 * @return EntityInfo
	 */
	public function collectEntityInfo( array $entityIds, array $languageCodes ) {
		$entityIdsByType = [];
		foreach ( $entityIds as $entityId ) {
			$entityIdsByType[$entityId->getEntityType()][] = $entityId;
		}

		$info = [];
		foreach ( $entityIdsByType as $type => $ids ) {
			if ( array_key_exists( $type, $this->builders ) ) {
				$info = array_merge(
					$info,
					$this->builders[$type]->collectEntityInfo( $ids, $languageCodes )->asArray()
				);
			}
			// TODO: what to do on unhandlded entity type?
		}

		return new EntityInfo( $info );
	}

}
