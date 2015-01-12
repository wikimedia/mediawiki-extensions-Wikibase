<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Term;

/**
 * Get a list of legacy Wikibase Term objects from a Fingerprint.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LegacyWikibaseTermsConverter {

	/**
	 * @param Fingerprint $fingerprint
	 * @param mixed[] $fields Optional, supply extra fields for setting entity id and entity type.
	 *
	 * @return Term[]
	 */
	public function getTermsOfFingerprint( Fingerprint $fingerprint, array $fields = array() ) {
		$terms = array_merge(
			$this->getTermsOfTermList(
				$fingerprint->getLabels(),
				Term::TYPE_LABEL,
				$fields
			),
			$this->getTermsOfTermList(
				$fingerprint->getDescriptions(),
				Term::TYPE_DESCRIPTION,
				$fields
			)
		);

		return array_merge(
			$terms,
			$this->getTermsOfAliasGroups( $fingerprint->getAliasGroups(), $fields )
		);
	}

	private function getTermsOfAliasGroups( AliasGroupList $aliasGroups, array $fields = array() ) {
		$terms = array();

		foreach ( $aliasGroups as $aliasGroup ) {
			foreach ( $aliasGroup->getAliases() as $alias ) {
				$term = new Term( $fields );

				$term->setLanguage( $aliasGroup->getLanguageCode() );
				$term->setType( Term::TYPE_ALIAS );
				$term->setText( $alias );

				$terms[] = $term;
			}
		}

		return $terms;
	}

	private function getTermsOfTermList( TermList $termList, $termType, array $fields = array() ) {
		$terms = array();

		foreach ( $termList as $term ) {
			$wikibaseTerm = new Term( $fields );

			$wikibaseTerm->setLanguage( $term->getLanguageCode() );
			$wikibaseTerm->setType( $termType );
			$wikibaseTerm->setText( $term->getText() );

			$terms[] = $wikibaseTerm;
		}

		return $terms;
	}
}
