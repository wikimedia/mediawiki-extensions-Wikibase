<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\TermStore\ItemTermStore;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikibase\StringNormalizer;

/**
 * @license GPL-2.0-or-later
 */
class DatabaseItemTermStore implements ItemTermStore {

	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var TermIdsAcquirer */
	private $acquirer;

	/** @var TermIdsResolver */
	private $resolver;

	/** @var TermIdsCleaner */
	private $cleaner;

	/** @var StringNormalizer */
	private $stringNormalizer;

	/** @var LoggerInterface */
	private $logger;

	/** @var IDatabase|null */
	private $dbr = null;

	/** @var IDatabase|null */
	private $dbw = null;

	public function __construct(
		ILoadBalancer $loadBalancer,
		TermIdsAcquirer $acquirer,
		TermIdsResolver $resolver,
		TermIdsCleaner $cleaner,
		StringNormalizer $stringNormalizer,
		LoggerInterface $logger = null
	) {
		$this->loadBalancer = $loadBalancer;
		$this->acquirer = $acquirer;
		$this->resolver = $resolver;
		$this->cleaner = $cleaner;
		$this->stringNormalizer = $stringNormalizer;
		$this->logger = $logger ?: new NullLogger();
	}

	private function getDbr(): IDatabase {
		if ( $this->dbr === null ) {
			$this->dbr = $this->loadBalancer->getConnection( ILoadBalancer::DB_REPLICA );
		}
		return $this->dbr;
	}

	private function getDbw(): IDatabase {
		if ( $this->dbw === null ) {
			$this->dbw = $this->loadBalancer->getConnection( ILoadBalancer::DB_MASTER );
		}
		return $this->dbw;
	}

	private function disallowForeignItemIds( ItemId $itemId ) {
		if ( $itemId->isForeign() ) {
			throw new InvalidArgumentException(
				'This implementation cannot be used with foreign item IDs!'
			);
		}
	}

	public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
		$this->disallowForeignItemIds( $itemId );

		$termsArray = [];
		foreach ( $terms->getLabels()->toTextArray() as $language => $label ) {
			$termsArray['label'][$language] = $this->stringNormalizer->cleanupToNFC( $label );
		}
		foreach ( $terms->getDescriptions()->toTextArray() as $language => $description ) {
			$termsArray['description'][$language] = $this->stringNormalizer->cleanupToNFC( $description );
		}
		foreach ( $terms->getAliasGroups()->toTextArray() as $language => $aliases ) {
			foreach ( $aliases as $alias ) {
				$termsArray['alias'][$language][] = $this->stringNormalizer->cleanupToNFC( $alias );
			}
		}

		$oldTermIds = $this->getDbw()->selectFieldValues(
			'wbt_item_terms',
			'wbit_term_in_lang_id',
			[ 'wbit_item_id' => $itemId->getNumericId() ],
			__METHOD__
		);
		$newTermIds = $this->acquirer->acquireTermIds( $termsArray );

		$termIdsToInsert = array_diff( $newTermIds, $oldTermIds );
		$termIdsToClean = array_diff( $oldTermIds, $newTermIds );
		$rowsToInsert = [];
		foreach ( $termIdsToInsert as $termIdToInsert ) {
			$rowsToInsert[] = [
				'wbit_item_id' => $itemId->getNumericId(),
				'wbit_term_in_lang_id' => $termIdToInsert,
			];
		}

		$this->getDbw()->insert(
			'wbt_item_terms',
			$rowsToInsert,
			__METHOD__
		);
		if ( $termIdsToClean !== [] ) {
			$this->getDbw()->delete(
				'wbt_item_terms',
				[
					'wbit_item_id' => $itemId->getNumericId(),
					'wbit_term_in_lang_id' => $termIdsToClean,
				],
				__METHOD__
			);
			$this->cleanTermsIfUnused( $termIdsToClean );
		}
	}

	public function deleteTerms( ItemId $itemId ) {
		$this->disallowForeignItemIds( $itemId );

		$res = $this->getDbw()->select(
			'wbt_item_terms',
			[ 'wbit_id', 'wbit_term_in_lang_id' ],
			[ 'wbit_item_id' => $itemId->getNumericId() ],
			__METHOD__
		);

		$rowIdsToDelete = [];
		$termIdsToCleanUp = [];
		foreach ( $res as $row ) {
			$rowIdsToDelete[] = $row->wbit_id;
			$termIdsToCleanUp[] = $row->wbit_term_in_lang_id;
		}

		if ( $rowIdsToDelete !== [] ) {
			$this->getDbw()->delete(
				'wbt_item_terms',
				[ 'wbit_id' => $rowIdsToDelete ],
				__METHOD__
			);
			$this->cleanTermsIfUnused( array_values( array_unique( $termIdsToCleanUp ) ) );
		}
	}

	/**
	 * Of the given term IDs, delete those that aren’t used by any other items or properties.
	 *
	 * Currently, this does not account for term IDs that may be used anywhere else,
	 * e. g. by other entity types; anyone who uses term IDs elsewhere runs the risk
	 * of those terms being deleted at any time. This may be improved in the future.
	 *
	 * @param int[] $termIds
	 */
	private function cleanTermsIfUnused( array $termIds ) {
		$termIdsUsedInProperties = $this->getDbw()->selectFieldValues(
			'wbt_item_terms',
			'wbit_term_in_lang_id',
			[ 'wbit_term_in_lang_id' => $termIds ],
			__METHOD__,
			[
				'FOR UPDATE', // see comment in DatabaseTermIdsCleaner::cleanTermInLangIds()
				// 'DISTINCT', // not supported in combination with FOR UPDATE on some DB types
			]
		);
		$termIdsUsedInItems = $this->getDbw()->selectFieldValues(
			'wbt_item_terms',
			'wbit_term_in_lang_id',
			[ 'wbit_term_in_lang_id' => $termIds ],
			__METHOD__,
			[
				'FOR UPDATE', // see comment in DatabaseTermIdsCleaner::cleanTermInLangIds()
				// 'DISTINCT', // not supported in combination with FOR UPDATE on some DB types
			]
		);

		$this->cleaner->cleanTermIds(
			array_diff(
				$termIds,
				$termIdsUsedInProperties,
				$termIdsUsedInItems
			)
		);
	}

	public function getTerms( ItemId $itemId ): Fingerprint {
		$this->disallowForeignItemIds( $itemId );

		$termIds = $this->getDbr()->selectFieldValues(
			'wbt_item_terms',
			'wbit_term_in_lang_id',
			[ 'wbit_item_id' => $itemId->getNumericId() ],
			__METHOD__
		);

		$terms = $this->resolver->resolveTermIds( $termIds );
		$labels = $terms['label'] ?? [];
		$descriptions = $terms['description'] ?? [];
		$aliases = $terms['alias'] ?? [];

		return new Fingerprint(
			new TermList( array_map(
				function ( $language, $labels ) {
					return new Term( $language, $labels[0] );
				},
				array_keys( $labels ), $labels
			) ),
			new TermList( array_map(
				function ( $language, $descriptions ) {
					return new Term( $language, $descriptions[0] );
				},
				array_keys( $descriptions ), $descriptions
			) ),
			new AliasGroupList( array_map(
				function ( $language, $aliases ) {
					return new AliasGroup( $language, $aliases );
				},
				array_keys( $aliases ), $aliases
			) )
		);
	}

}
