<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\Lib\TermIndexEntry;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityTermLookupBase implements TermLookup {

	/**
	 * @see TermLookup::getLabel
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException
	 * @return string|null
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$labels = $this->getLabels( $entityId, [ $languageCode ] );
		return $labels[$languageCode] ?? null;
	}

	/**
	 * @see TermLookup::getLabels
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The languages to get terms for
	 *
	 * @throws TermLookupException
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId, array $languageCodes ) {
		return $this->getTermsOfType( $entityId, 'label', $languageCodes );
	}

	/**
	 * @see TermLookup::getDescription
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException
	 * @return string|null
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		$descriptions = $this->getDescriptions( $entityId, [ $languageCode ] );
		return $descriptions[$languageCode] ?? null;
	}

	/**
	 * @see TermLookup::getDescriptions
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The languages to get terms for
	 *
	 * @throws TermLookupException
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		return $this->getTermsOfType( $entityId, 'description', $languageCodes );
	}

	/**
	 * Gets the text string terms for a given type.
	 * If aliases are requested here you will only receive a single string.
	 *
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string[] $languageCodes The languages to get terms for
	 *
	 * @return string[] Normally indexed by language code
	 */
	abstract protected function getTermsOfType( EntityId $entityId, $termType, array $languageCodes );

	/**
	 * @param TermIndexEntry[] $wikibaseTerms
	 *
	 * @return string[] strings keyed by language code
	 */
	protected function convertTermsToMap( array $wikibaseTerms ) {
		$terms = [];

		foreach ( $wikibaseTerms as $wikibaseTerm ) {
			$languageCode = $wikibaseTerm->getLanguage();
			$terms[$languageCode] = $wikibaseTerm->getText();
		}

		return $terms;
	}

}
