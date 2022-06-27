<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use MediaWiki\Logger\LoggerFactory;
use Throwable;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Services\Term\TermStoreException;
use Wikibase\DataModel\Term\AliasGroup;

/**
 * Adapter turning an ItemTermStoreWriter into an EntityTermStoreWriter.
 *
 * @license GPL-2.0-or-later
 */
class ItemTermStoreWriterAdapter implements EntityTermStoreWriter {

	/** @var ItemTermStoreWriter */
	private $store;

	public function __construct( ItemTermStoreWriter $store ) {
		$this->store = $store;
	}

	public function saveTermsOfEntity( EntityDocument $entity ): bool {
		if ( $entity instanceof Item ) {
			$entityId = $entity->getId();
			$terms = $entity->getFingerprint();
			LoggerFactory::getInstance( 'WikibaseTerms' )
				->debug( __METHOD__ . ': run saveTermsOfEntity for {id}', [
					'id' => $entityId->getSerialization(),
					'labels' => $terms->getLabels()->count(),
					'descriptions' => $terms->getDescriptions()->count(),
					'aliases' => array_reduce(
						$terms->getAliasGroups()->toArray(),
						static function ( int $count, AliasGroup $group ): int {
							return $count + $group->count();
						},
						0
					),
					'phab' => 'T311307',
				] );
			try {
				$this->store->storeTerms( $entityId, $terms );
				return true;
			} catch ( TermStoreException $ex ) {
				LoggerFactory::getInstance( 'WikibaseTerms' )
					->error( __METHOD__ . ': failed saveTermsOfEntity for {id}, returning false', [
						'id' => $entityId->getSerialization(),
						'exception' => $ex,
						'phab' => 'T311307',
					] );
				return false;
			} catch ( Throwable $ex ) {
				LoggerFactory::getInstance( 'WikibaseTerms' )
					->error( __METHOD__ . ': failed saveTermsOfEntity for {id}, rethrowing', [
						'id' => $entityId->getSerialization(),
						'exception' => $ex,
						'phab' => 'T311307',
					] );
				throw $ex;
			}
		}

		throw new InvalidArgumentException( 'Unsupported entity type' );
	}

	public function deleteTermsOfEntity( EntityId $entityId ): bool {
		if ( $entityId instanceof ItemId ) {
			try {
				$this->store->deleteTerms( $entityId );
				return true;
			} catch ( TermStoreException $ex ) {
				return false;
			}
		}

		throw new InvalidArgumentException( 'Unsupported entity type' );
	}

}
