<?php

namespace Wikibase\Repo;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;

/**
 * FIXME: OCP violation. Extensions that add new types of entities with
 * new types of terms cannot register proper support.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntitySearchTextGenerator {

	/**
	 * @param EntityDocument $entity
	 *
	 * @return string
	 */
	public function generate( EntityDocument $entity ) {
		if ( $entity instanceof FingerprintProvider ) {
			return $this->getTextForFingerprint( $entity->getFingerprint() );
		}

		return '';
	}

	private function getTextForFingerprint( Fingerprint $fingerprint ) {
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
	protected function getArrayAsText( array $elements ) {
		return implode( "\n", $elements );
	}

	/**
	 * @param AliasGroupList $aliasGroups
	 *
	 * @return string
	 */
	protected function getAllAliasesText( AliasGroupList $aliasGroups ) {
		$text = '';

		foreach ( $aliasGroups as $aliasGroup ) {
			$text .= "\n" . implode( "\n", $aliasGroup->getAliases() );
		}

		return $text;
	}

}
