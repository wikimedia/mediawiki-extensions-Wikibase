<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\TermIndex;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityTermLookup extends EntityTermLookupBase {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	public function __construct( TermIndex $termIndex ) {
		$this->termIndex = $termIndex;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string[]|null $languageCodes The languages to get terms for; null means all languages.
	 *
	 * @return string[]
	 */
	protected function getTermsOfType( EntityId $entityId, $termType, array $languageCodes = null ) {
		$wikibaseTerms = $this->termIndex->getTermsOfEntity( $entityId, [ $termType ], $languageCodes );

		return $this->convertTermsToMap( $wikibaseTerms );
	}

}
