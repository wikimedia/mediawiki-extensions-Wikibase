<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\MatchingTermsLookup;

/**
 * @license GPL-2.0-or-later
 */
class TermStoreDelegatingMatchingTermsLookup implements MatchingTermsLookup {

	/**
	 * @var MatchingTermsLookup
	 */
	private $oldLookup;

	/**
	 * @var MatchingTermsLookup
	 */
	private $newLookup;

	/**
	 * @var int
	 */
	private $itemMigrationStage;

	/**
	 * @var int
	 */
	private $propertyMigrationStage;

	public function __construct(
		MatchingTermsLookup $oldLookup,
		MatchingTermsLookup $newLookup,
		int $itemMigrationStage,
		int $propertyMigrationStage
	) {
		$this->oldLookup = $oldLookup;
		$this->newLookup = $newLookup;
		$this->itemMigrationStage = $itemMigrationStage;
		$this->propertyMigrationStage = $propertyMigrationStage;
	}

	public function getMatchingTerms(
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = []
	) {
		if ( $this->isRequestingOnlyTermsOfEntityType( Item::ENTITY_TYPE, $entityType ) ) {
			return $this->getStoreByMigrationStage( $this->itemMigrationStage )
				->getMatchingTerms( $criteria, $termType, $entityType, $options );
		} elseif ( $this->isRequestingOnlyTermsOfEntityType( Property::ENTITY_TYPE, $entityType )
			|| $this->bothUseSameStore() ) {
			return $this->getStoreByMigrationStage( $this->propertyMigrationStage )
				->getMatchingTerms( $criteria, $termType, $entityType, $options );
		}

		return $this->applyLimit( array_merge(
			$this->getStoreByMigrationStage( $this->itemMigrationStage )
				->getMatchingTerms( $criteria, $termType, Item::ENTITY_TYPE, $options ),
			$this->getStoreByMigrationStage( $this->propertyMigrationStage )
				->getMatchingTerms( $criteria, $termType, Property::ENTITY_TYPE, $options )
		), $options );
	}

	public function getTopMatchingTerms(
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = []
	) {
		if ( $this->isRequestingOnlyTermsOfEntityType( Item::ENTITY_TYPE, $entityType ) ) {
			return $this->getStoreByMigrationStage( $this->itemMigrationStage )
				->getTopMatchingTerms( $criteria, $termType, $entityType, $options );
		} elseif ( $this->isRequestingOnlyTermsOfEntityType( Property::ENTITY_TYPE, $entityType )
			|| $this->bothUseSameStore() ) {
			return $this->getStoreByMigrationStage( $this->propertyMigrationStage )
				->getTopMatchingTerms( $criteria, $termType, $entityType, $options );
		}

		return $this->applyLimit( array_merge(
			$this->getStoreByMigrationStage( $this->itemMigrationStage )
				->getTopMatchingTerms( $criteria, $termType, Item::ENTITY_TYPE, $options ),
			$this->getStoreByMigrationStage( $this->propertyMigrationStage )
				->getTopMatchingTerms( $criteria, $termType, Property::ENTITY_TYPE, $options )
		), $options );
	}

	private function isRequestingOnlyTermsOfEntityType( string $entityType, $requestedEntityTypes ) {
		return $requestedEntityTypes === $entityType || (
				is_array( $requestedEntityTypes )
				&& count( $requestedEntityTypes ) == 1
				&& $requestedEntityTypes[0] === $entityType
			);
	}

	private function getStoreByMigrationStage( int $stage ) {
		return $this->shouldUseNewStore( $stage ) ? $this->newLookup : $this->oldLookup;
	}

	private function shouldUseNewStore( int $stage ) {
		return $stage >= MIGRATION_WRITE_NEW;
	}

	private function bothUseSameStore() {
		return $this->shouldUseNewStore( $this->itemMigrationStage )
			=== $this->shouldUseNewStore( $this->propertyMigrationStage );
	}

	private function applyLimit( array $terms, $options ) {
		if ( isset( $options['LIMIT'] ) && $options['LIMIT'] > 0 ) {
			return array_slice( $terms, 0, $options['LIMIT'] );
		}

		return $terms;
	}

}
