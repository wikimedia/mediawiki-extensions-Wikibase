<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\StringNormalizer;

/**
 * Trait for code reuse between DatabaseItemTermStoreWriter and DatabasePropertyTermStoreWriter
 *
 * @author Addshore
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
trait FingerprintableEntityTermStoreTrait {

	use FindUnusedTermTrait;

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
}
