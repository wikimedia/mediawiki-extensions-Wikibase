<?php

namespace Wikibase\Lib\Store\SQL;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\TermsLookup;
use Wikibase\TermIndex;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermsSQLLookup implements TermsLookup {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @param TermIndex $termIndex
	 */
	public function __construct( TermIndex $termIndex ) {
		$this->termIndex = $termIndex;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 *
	 * @return array languageCode => text
	 */
	public function getTermsByTermType( EntityId $entityId, $termType ) {
		$wikibaseTerms = $this->termIndex->getTermsOfEntity( $entityId );

		return $this->filterWikibaseTerms( $wikibaseTerms, $termType );
	}

	/**
	 * @param Wikibase\Term[] $wikibaseTerms
	 * @param string $termType
	 *
	 * @return array languageCode => text
	 */
	private function filterWikibaseTerms( array $wikibaseTerms, $termType ) {
		$terms = array();

		foreach( $wikibaseTerms as $wikibaseTerm ) {
			if ( $wikibaseTerm->getType() === $termType ) {
				$languageCode = $wikibaseTerm->getLanguage();
				$terms[$languageCode] = $wikibaseTerm->getText();
			}
		}

		return $terms;
	}

}
