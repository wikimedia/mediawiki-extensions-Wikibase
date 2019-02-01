<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityInfoBuilder implements EntityInfoBuilder {

	/**
	 * @var EntityInfoBuilder[]
	 */
	private $builders;

	/**
	 * @param EntityInfoBuilder[] $builders
	 */
	public function __construct( array $builders ) {
		Assert::parameterElementType( EntityInfoBuilder::class, $builders, '$builders' );
		Assert::parameterElementType( 'string', array_keys( $builders ), 'keys of $builders' );

		$this->builders = $builders;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string[] $languageCodes
	 * @return EntityInfo
	 */
	public function collectEntityInfo( array $entityIds, array $languageCodes ) {
		$info = [];

		foreach ( $this->groupByEntityType( $entityIds ) as $type => $ids ) {
			if ( array_key_exists( $type, $this->builders ) ) {
				$info = array_merge(
					$info,
					$this->builders[$type]->collectEntityInfo( $ids, $languageCodes )->asArray()
				);
			}
		}

		return new EntityInfo( $info );
	}

	private function groupByEntityType( array $entityIds ) {
		$idsByType = [];

		foreach ( $entityIds as $entityId ) {
			$idsByType[$entityId->getEntityType()][] = $entityId;
		}

		return $idsByType;
	}

}
