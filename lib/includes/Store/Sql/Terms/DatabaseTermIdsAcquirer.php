<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use Exception;
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
	 * @param ILoadBalancer $loadBalancer
	 * @param TypeIdsAcquirer $typeIdsAcquirer
	 */
	public function __construct(
		ILoadBalancer $loadBalancer,
		TypeIdsAcquirer $typeIdsAcquirer
	) {
		$this->loadBalancer = $loadBalancer;
		$this->typeIdsAcquirer = $typeIdsAcquirer;
	}

	public function acquireTermIds( array $termsArray, $callback = null ): array {
		if ( $termsArray === [] ) {
			if ( $callback !== null ) {
				( $callback )( [] );
			}
			return [];
		}

		$termIds = $this->mapTermsArrayToTermIds( $termsArray );

		if ( $callback !== null ) {
			( $callback )( $termIds );
		}

		$this->restoreCleanedUpIds( $termsArray, $termIds );

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
		$textRecords = $this->filterUniqueRecords( $textRecords );

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
		$textInLangRecords = $this->filterUniqueRecords( $textInLangRecords );

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

	private function acquireTermInLangIds( array $typeTextInLangIds, array $idsToRestore = [] ) {
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
		$termInLangRecords = $this->filterUniqueRecords( $termInLangRecords );

		$acquiredIds = $termInLangIdsAcquirer->acquireIds(
			$termInLangRecords,
			function ( $recordsToInsert ) use ( $idsToRestore ) {
				if ( count( $idsToRestore ) <= 0 ) {
					return $recordsToInsert;
				}

				if ( count( $idsToRestore ) !== count( $recordsToInsert ) ) {
					$exception = new Exception(
						'Fail-safe exception. Number of ids to be restored is not equal to'
						. ' the number of records that are about to be inserted into master.'
						. ' This should never happen, except for an edge-case that was not'
						. ' detected during development or due to a race-condition that is'
						. ' not covered by this implementation.'
					);

					$this->logger->warning(
						'{method}: Restoring record term in lang ids failed: {exception}',
						[
							'method' => __METHOD__,
							'exception' => $exception,
							'idsToRestore' => $idsToRestore,
							'recordsToInsert' => $recordsToInsert,
						]
					);

					throw $exception;
				}

				return array_map(
					function ( $record, $idToRestore ) {
						$record['wbtl_id'] = $idToRestore;
						return $record;
					},
					$recordsToInsert,
					$idsToRestore
				);
			} );

		$termInLangIds = [];
		foreach ( $acquiredIds as $acquiredId ) {
			$termInLangIds[$acquiredId['wbtl_type_id']][$acquiredId['wbtl_text_in_lang_id']]
				= $acquiredId['wbtl_id'];
		}

		return $termInLangIds;
	}

	private function restoreCleanedUpIds( array $termsArray, array $termIds = [] ) {
		$uniqueTermIds = array_values( array_unique( $termIds ) );

		$dbMaster = $this->loadBalancer->getConnection( ILoadBalancer::DB_MASTER );
		$persistedTermIds = $dbMaster->selectFieldValues(
			'wbt_term_in_lang',
			'wbtl_id',
			[ 'wbtl_id' => $termIds ],
			__METHOD__
		);

		sort( $uniqueTermIds );
		sort( $persistedTermIds );
		$idsToRestore = array_diff( $uniqueTermIds, $persistedTermIds );

		if ( !empty( $idsToRestore ) ) {
			$this->mapTermsArrayToTermIds( $termsArray, $idsToRestore );
		}
	}

	private function mapTermsArrayToTermIds(
		array $termsArray,
		array $termIdsToRestore = []
	): array {
		$termsArray = $this->mapToTextIds( $termsArray );
		$termsArray = $this->mapToTextInLangIds( $termsArray );
		$termsArray = $this->mapToTypeIds( $termsArray );
		return $this->mapToTermInLangIds( $termsArray, $termIdsToRestore );
	}

	private function calcRecordHash( array $record ) {
		ksort( $record );
		return md5( serialize( $record ) );
	}

	private function filterUniqueRecords( array $records ) {
		$uniqueRecords = [];
		foreach ( $records as $record ) {
			$recordHash = $this->calcRecordHash( $record );
			$uniqueRecords[$recordHash] = $record;
		}

		return array_values( $uniqueRecords );
	}

}
