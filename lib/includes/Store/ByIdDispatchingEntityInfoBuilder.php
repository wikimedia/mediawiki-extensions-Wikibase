<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\Int32EntityId;
use Wikimedia\Assert\Assert;

/**
 * An {@link EntityInfoBuilder} wrapping several other {@link EntityInfoBuilder}s,
 * dispatching between them based on the numeric ID.
 *
 * @license GPL-2.0-or-later
 */
class ByIdDispatchingEntityInfoBuilder implements EntityInfoBuilder {

	/** @var EntityInfoBuilder[] */
	private $entityInfoBuilders;

	/**
	 * @param EntityInfoBuilder[] $entityInfoBuilders
	 * Map from maximum ID number to EntityInfoBuilder for those IDs.
	 * The dispatcher iterates over this array,
	 * dispatching to the first builder whose key is greater than
	 * or equal to the numeric ID for which the builder is called.
	 * It orders the key entries so the lowest one wins first.
	 * At least one of the keys must cover all possible IDs.
	 * Example:
	 *
	 * [ 1000000 => $newBuilder, 2000000 => $mixedBuilder, Int32EntityId::MAX => $oldBuilder ]
	 */
	public function __construct( array $entityInfoBuilders ) {
		Assert::parameter( $entityInfoBuilders !== [], '$entityInfoBuilders', 'must not be empty' );
		Assert::parameterElementType(
			EntityInfoBuilder::class,
			$entityInfoBuilders,
			'$entityInfoBuilders'
		);
		Assert::parameterKeyType(
			'integer',
			$entityInfoBuilders,
			'$entityInfoBuilders'
		);
		$this->entityInfoBuilders = $entityInfoBuilders;
		ksort( $this->entityInfoBuilders );
	}

	/**
	 * @param array $entityIds
	 * @param array $languageCodes
	 * @return EntityInfo
	 */
	public function collectEntityInfo( array $entityIds, array $languageCodes ) {
		$mapping = [];

		foreach ( $this->entityInfoBuilders as $maxId => $entityInfoBuilder ) {
			$mapping[$maxId] = [];
		}

		foreach ( $entityIds as $entityId ) {
			if ( !$entityId instanceof Int32EntityId ) {
				continue;
			}
			$mapping = $this->populateMapping( $entityId, $mapping );
		}

		// God forgive me for what I have done here
		$result = [];

		foreach ( $this->entityInfoBuilders as $maxId => $entityInfoBuilder ) {
			if ( $mapping[$maxId] === [] ) {
				continue;
			}
			$entityInfo = $entityInfoBuilder->collectEntityInfo( $mapping[$maxId], $languageCodes );
			$result = array_merge( $result, $entityInfo->asArray() );
		}

		return new EntityInfo( $result );
	}

	private function populateMapping( Int32EntityId $entityId, array $mapping ): array {
		foreach ( $this->entityInfoBuilders as $maxId => $entityInfoBuilder ) {
			if ( $entityId->getNumericId() < $maxId ) {
				$mapping[$maxId][] = $entityId;
				return $mapping;
			}
		}

		return $mapping;
	}

}
