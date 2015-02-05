<?php

namespace Wikibase\Repo;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 */
class FingerprintSearchTextGenerator {

	/**
	 * @param Fingerprint $fingerprint
	 *
	 * @return string
	 */
	public function generate( Fingerprint $fingerprint ) {
		$text = $this->getArrayAsText( $fingerprint->getLabels()->toTextArray() );

		$text .= "\n" . $this->getArrayAsText( $fingerprint->getDescriptions()->toTextArray() );

		$text .= $this->getAllAliasesText( $fingerprint->getAliasGroups() );

		return $text;
	}

	/**
	 * @param string[] $elements
	 *
	 * @return string
	 */
	private function getArrayAsText( array $elements ) {
		return implode( "\n", $elements );
	}

	/**
	 * @param AliasGroupList $aliasGroups
	 *
	 * @return string
	 */
	private function getAllAliasesText( AliasGroupList $aliasGroups ) {
		$text = '';

		/** @var AliasGroup $aliasGroup */
		foreach ( $aliasGroups as $aliasGroup ) {
			$text .= "\n" . implode( "\n", $aliasGroup->getAliases() );
		}

		return $text;
	}

}
