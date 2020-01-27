<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\StringNormalizer;
use Wikimedia\Rdbms\IDatabase;

/**
 * Trait for code reuse between DatabaseItemTermStore and DatabasePropertyTermStore
 *
 * @author Addshore
 * @see @ref md_docs_storage_terms
 * @license GPL-2.0-or-later
 */
trait FingerprintableEntityTermStoreTrait {

	private function termsArrayFromFingerprint( Fingerprint $fingerprint, StringNormalizer $stringNormalizer ): array {
		$termsArray = [];
		foreach ( $fingerprint->getLabels()->toTextArray() as $language => $label ) {
			$label = $stringNormalizer->cleanupToNFC( $label );
			$termsArray['label'][$language] = $label;
		}
		foreach ( $fingerprint->getDescriptions()->toTextArray() as $language => $description ) {
			$description = $stringNormalizer->cleanupToNFC( $description );
			$termsArray['description'][$language] = $description;
		}
		foreach ( $fingerprint->getAliasGroups()->toTextArray() as $language => $aliases ) {
			$aliases = array_map( [ $stringNormalizer, 'cleanupToNFC' ], $aliases );
			$termsArray['alias'][$language] = $aliases;
		}
		return $termsArray;
	}

	/**
	 * @param array $result Result from TermIdsResolver::resolveTermIds
	 * @return Fingerprint
	 */
	private function resolveTermIdsResultToFingerprint( array $result ) {
		$labels = $result['label'] ?? [];
		$descriptions = $result['description'] ?? [];
		$aliases = $result['alias'] ?? [];

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

	/**
	 * Of the given term IDs, find those that aren’t used by any other items or properties.
	 *
	 * Currently, this does not account for term IDs that may be used anywhere else,
	 * e.g. by other entity types; anyone who uses term IDs elsewhere runs the risk
	 * of those terms being deleted at any time. This may be improved in the future.
	 *
	 * 1) Iterate through the IDs that we have been given and determine if they
	 * appear to be used or not in either the property or item term tables.
	 * 2) Select FOR UPDATE the rows in the wbt_property_terms and wbt_item_terms
	 * tables so they lock and nothing will happen to them.
	 *
	 * An alternative to this would be immediately lock all $termIds, but that would
	 * lead to deadlocks. see T234948
	 *
	 * @param int[] $termIds (wbtl_id)
	 * @return int[] wbtl_ids to be cleaned
	 */
	private function findActuallyUnusedTermIds( array $termIds, IDatabase $dbw ) {
		$termIdsUnused = [];
		foreach ( $termIds as  $termId ) {
			// Note: Not batching here is intentional to avoid deadlocks (see method comment)
			$usedInProperties = $dbw->selectField(
				'wbt_property_terms',
				'wbpt_term_in_lang_id',
				[ 'wbpt_term_in_lang_id' => $termId ]
			);
			$usedInItems = $dbw->selectField(
				'wbt_item_terms',
				'wbit_term_in_lang_id',
				[ 'wbit_term_in_lang_id' => $termId ]
			);

			if ( $usedInProperties === false && $usedInItems === false ) {
				$termIdsUnused[] = $termId;
			}
		}
		if ( $termIdsUnused === [] ) {
			return [];
		}

		$termIdsUsedInProperties = $dbw->selectFieldValues(
			'wbt_property_terms',
			'wbpt_term_in_lang_id',
			[ 'wbpt_term_in_lang_id' => $termIdsUnused ],
			__METHOD__,
			[
				'FOR UPDATE'
			]
		);
		$termIdsUsedInItems = $dbw->selectFieldValues(
			'wbt_item_terms',
			'wbit_term_in_lang_id',
			[ 'wbit_term_in_lang_id' => $termIdsUnused ],
			__METHOD__,
			[
				'FOR UPDATE'
			]
		);

		return array_diff(
			$termIds,
			$termIdsUsedInProperties,
			$termIdsUsedInItems
		);
	}

}
