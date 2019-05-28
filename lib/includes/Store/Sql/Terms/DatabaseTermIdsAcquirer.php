<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\Lib\Store\Sql\Terms\Util\ReplicaMasterAwareRecordIdsAcquirer;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @license GPL-2.0-or-later
 */
class DatabaseTermIdsAcquirer implements TermIdsAcquirer {

	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var TypeIdsAcquirer
	 */
	private $typeIdsAcquirer;

	/**
	 * @var callable
	 */
	private $acquiredIdsConsumerCallback = null;

	/**
	 * This implementation guarantees that in-parallel {@link DatabaseTermIdsCleaner}
	 * will not result in deleting terms that have been acquired by this acquirer, should
	 * these two in-parallel processes happen to overlap on some existing term ids.
	 * The mechanism of achieving this guarantee is complete under the following two conditions:
	 * - external linking to acquired ids (e.g. using them as foreign keys in other tables)
	 *	 must happen inside a callback passed through $acquiredIdsConsumerCallback
	 *	 constructor param.
	 * - the in-parallel cleaner is called with set of ids based on the absence of any
	 *	 links to those ids, in the same external places where the callback links to them.
	 *
	 * @param ILoadBalancer $loadBalancer
	 * @param TypeIdsAcquirer $typeIdsAcquirer
	 * @param callable|null $acquiredIdsConsumerCallback
	 *	If callable is not null, this implementation guarantees,
	 *	it will be called with the array of acquired ids (same one that will be returned
	 *	by {@link acquireTermIds()}).
	 */
	public function __construct(
		ILoadBalancer $loadBalancer,
		TypeIdsAcquirer $typeIdsAcquirer,
		$acquiredIdsConsumerCallback = null
	) {
		$this->loadBalancer = $loadBalancer;
		$this->typeIdsAcquirer = $typeIdsAcquirer;
		$this->acquiredIdsConsumerCallback = $acquiredIdsConsumerCallback
										   ?? function ( $ids ) { /* no-op */ };
	}

	public function acquireTermIds( array $termsArray ): array {
		if ( $termsArray === [] ) {
			return [];
		}

		$originalTermsArray = $termsArray;

		$termsArray = $this->mapToTextIds( $termsArray );
		$termsArray = $this->mapToTextInLangIds( $termsArray );
		$termsArray = $this->mapToTypeIds( $termsArray );
		$termIds = $this->mapToTermInLangIds( $termsArray );

		( $this->acquiredIdsConsumerCallback )( $termIds );

		$this->restoreCleanedUpIds( $originalTermsArray, $termIds );

		return $termIds;
	}

	/**
	 * replace root keys containing type names in termsArray
	 * with their respective ids in wbt_type table
	 *
	 * @param array $termsArray terms per type per language:
	 *	[
	 *		'type1' => [ ... ],
	 *		'type2' => [ ... ],
	 *		...
	 *	]
	 *
	 * @return array
	 *	[
	 *		<typeId1> => [ ... ],
	 *		<typeId2> => [ ... ],
	 *		...
	 *	]
	 */
	private function mapToTypeIds( array $termsArray ) {
		$typeIds = $this->typeIdsAcquirer->acquireTypeIds( array_keys( $termsArray ) );

		$termsArrayByTypeId = [];
		foreach ( $typeIds as $type => $typeId ) {
			$termsArrayByTypeId[$typeId] = $termsArray[$type];
		}

		return $termsArrayByTypeId;
	}

	/**
	 * replace text at termsArray leaves with their ids in wbt_text table
	 * and return resulting array
	 *
	 * @param array $termsArray terms per type per language:
	 *	[
	 *		'type' => [
	 *			[ 'language' => 'term' | [ 'term1', 'term2', ... ] ], ...
	 *		], ...
	 *	]
	 *
	 * @return array
	 *	[
	 *		'type' => [
	 *			[ 'language' => [ <textId1>, <textId2>, ... ] ], ...
	 *		], ...
	 *	]
	 */
	private function mapToTextIds( array $termsArray ) {
		$texts = [];

		array_walk_recursive( $termsArray, function ( $text ) use ( &$texts ) {
			$texts[] = $text;
		} );

		$textIds = $this->acquireTextIds( $texts );

		array_walk_recursive( $termsArray, function ( &$text ) use ( $textIds ) {
			$text = $textIds[$text];
		} );

		return $termsArray;
	}

	private function acquireTextIds( array $texts ) {
		$textIdsAcquirer = new ReplicaMasterAwareRecordIdsAcquirer(
			$this->loadBalancer, 'wbt_text', 'wbx_id' );

		$textRecords = [];
		foreach ( $texts as $text ) {
			$textRecords[] = [ 'wbx_text' => $text ];
		}

		$acquiredIds = $textIdsAcquirer->acquireIds( $textRecords );

		$textIds = [];
		foreach ( $acquiredIds as $acquiredId ) {
			$textIds[$acquiredId['wbx_text']] = $acquiredId['wbx_id'];
		}

		return $textIds;
	}

	/**
	 * replace ( lang => [ textId, ... ] ) entries with their respective ids
	 * in wbt_text_in_lang table and return resulting array
	 *
	 * @param array $termsArray text ids per type per langauge
	 *	[
	 *		'type' => [
	 *			[ 'language' => [ <textId1>, <textId2>, ... ] ], ...
	 *		], ...
	 *	]
	 *
	 * @return array
	 *	[
	 *		'type' => [ <textInLangId1>, <textInLangId2>, ... ],
	 *		...
	 *	]
	 */
	private function mapToTextInLangIds( array $termsArray ) {
		$flattenedLangTextIds = [];
		foreach ( $termsArray as $langTextIds ) {
			foreach ( $langTextIds as $lang => $textIds ) {
				if ( !isset( $flattenedLangTextIds[$lang] ) ) {
					$flattenedLangTextIds[$lang] = [];
				}

				$flattenedLangTextIds[$lang] = array_unique(
					array_merge(
						(array)$textIds,
						(array)$flattenedLangTextIds[$lang]
					)
				);

			}
		}

		$textInLangIds = $this->acquireTextInLangIds( $flattenedLangTextIds );

		$newTermsArray = [];
		foreach ( $termsArray as $type => $langTextIds ) {
			$newTermsArray[$type] = [];
			foreach ( $langTextIds as $lang => $textIds ) {
				foreach ( (array)$textIds as $textId ) {
					$newTermsArray[$type][] = $textInLangIds[$lang][$textId];
				}
			}
		}

		return $newTermsArray;
	}

	private function acquireTextInLangIds( array $langTextIds ) {
		$textInLangIdsAcquirer = new ReplicaMasterAwareRecordIdsAcquirer(
			$this->loadBalancer, 'wbt_text_in_lang', 'wbxl_id' );

		$textInLangRecords = [];
		foreach ( $langTextIds as $lang => $textIds ) {
			foreach ( $textIds as $textId ) {
				$textInLangRecords[] = [ 'wbxl_text_id' => $textId, 'wbxl_language' => $lang ];
			}
		}

		$acquiredIds = $textInLangIdsAcquirer->acquireIds( $textInLangRecords );

		$textInLangIds = [];
		foreach ( $acquiredIds as $acquiredId ) {
			$textInLangIds[$acquiredId['wbxl_language']][$acquiredId['wbxl_text_id']]
				= $acquiredId['wbxl_id'];
		}

		return $textInLangIds;
	}

	/**
	 * replace root ( type => [ textInLangId, ... ] ) entries with their respective ids
	 * in wbt_term_in_lang table and return resulting array
	 *
	 * @param array $termsArray text in lang ids per type
	 *	[
	 *		'type' => [ <textInLangId1>, <textInLangId2>, ... ],
	 *		...
	 *	]
	 *
	 * @return array
	 *	[
	 *		<termInLang1>,
	 *		<termInLang2>,
	 *		...
	 *	]
	 */
	private function mapToTermInLangIds(
		array $termsArray,
		array $idsToRestore = []
	) {
		$flattenedTypeTextInLangIds = [];
		foreach ( $termsArray as $typeId => $textInLangIds ) {
			if ( !isset( $flattenedTypeTextInLangIds[$typeId] ) ) {
				$flattenedTypeTextInLangIds[$typeId] = [];
			}

			$flattenedTypeTextInLangIds[$typeId] = array_unique(
				array_merge(
					(array)$textInLangIds,
					(array)$flattenedTypeTextInLangIds[$typeId]
				)
			);
		}

		$termInLangIds = $this->acquireTermInLangIds( $flattenedTypeTextInLangIds, $idsToRestore );

		$newTermsArray = [];
		foreach ( $termsArray as $typeId => $textInLangIds ) {
			foreach ( $textInLangIds as $textInLangId ) {
				$newTermsArray[] = $termInLangIds[$typeId][$textInLangId];
			}
		}

		return $newTermsArray;
	}

	private function acquireTermInLangIds( array $typeTextInLangIds, array $idsToRestore ) {
		$termInLangIdsAcquirer = new ReplicaMasterAwareRecordIdsAcquirer(
			$this->loadBalancer, 'wbt_term_in_lang', 'wbtl_id' );

		$termInLangRecords = [];
		foreach ( $typeTextInLangIds as $typeId => $textInLangIds ) {
			foreach ( $textInLangIds as $textInLangId ) {
				$termInLangRecords[] = [
					'wbtl_text_in_lang_id' => $textInLangId,
					'wbtl_type_id' => (string)$typeId
				];
			}
		}

		$acquiredIds = $termInLangIdsAcquirer->acquireIds( $termInLangRecords, $idsToRestore );

		$termInLangIds = [];
		foreach ( $acquiredIds as $acquiredId ) {
			$termInLangIds[$acquiredId['wbtl_type_id']][$acquiredId['wbtl_text_in_lang_id']]
				= $acquiredId['wbtl_id'];
		}

		return $termInLangIds;
	}

	private function restoreCleanedUpIds( array $termsrAray, array $termIds = [] ) {
		$uniqueTermIds = array_values( array_unique( $termIds ) );
		sort( $uniqueTermIds );

		$dbMaster = $this->loadBalancer->getConnection( ILoadBalancer::DB_MASTER );
		$persistedTermIds = $dbMaster->selectFieldValues(
			'wbt_term_in_lang',
			'wbtl_id',
			[ 'wbtl_id' => $termIds ],
			__METHOD__
		);
		sort( $persistedTermIds );

		$idsToRestore = array_diff( $uniqueTermIds, $persistedTermIds );

		if ( empty( $idsToRestore ) ) {
			return;
		}

		$termsArray = $this->mapToTextIds( $termsArray );
		$termsArray = $this->mapToTextInLangIds( $termsArray );
		$termsArray = $this->mapToTypeIds( $termsArray );
		$this->mapToTermInLangIds( $termsArray, $idsToRestore );
	}

}
