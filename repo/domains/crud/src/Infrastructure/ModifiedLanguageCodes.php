<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure;

use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
trait ModifiedLanguageCodes {

	/**
	 * @param TermList|AliasGroupList $original
	 * @param TermList|AliasGroupList $modified
	 */
	private function getModifiedLanguageCodes( $original, $modified ): array {
		$original = iterator_to_array( $original );
		$modified = iterator_to_array( $modified );
		$modifiedLanguages = [];

		// handle additions and text changes
		foreach ( $modified as $language => $termOrAliases ) {
			if ( !array_key_exists( $language, $original ) || !$original[ $language ]->equals( $termOrAliases ) ) {
				$modifiedLanguages[] = $language;
			}
		}

		// handle deletions
		foreach ( $original as $language => $termOrAliases ) {
			if ( !array_key_exists( $language, $modified ) ) {
				$modifiedLanguages[] = $language;
			}
		}

		sort( $modifiedLanguages );

		return $modifiedLanguages;
	}

}
