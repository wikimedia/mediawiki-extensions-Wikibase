<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Term;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
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
	 * @throws OutOfBoundsException if no label in that language is known
	 * @return string
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$labels = $this->getLabels( $entityId, array( $languageCode ) );

		if ( !isset( $labels[$languageCode] ) ) {
			throw new OutOfBoundsException( 'No label found for language ' . $languageCode );
		}

		return $labels[$languageCode];
	}

	/**
	 * @see TermLookup::getLabels
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The languages to get terms for
	 *
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
	 * @throws OutOfBoundsException if no description in that language is known
	 * @return string
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		$descriptions = $this->getDescriptions( $entityId, array( $languageCode ) );

		if ( !isset( $descriptions[$languageCode] ) ) {
			throw new OutOfBoundsException( 'No description found for language ' . $languageCode );
		}

		return $descriptions[$languageCode];
	}

	/**
	 * @see TermLookup::getDescriptions
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The languages to get terms for
	 *
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		return $this->getTermsOfType( $entityId, 'description', $languageCodes );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string[] $languageCodes The languages to get terms for
	 *
	 * @return string[]
	 */
	abstract protected function getTermsOfType( EntityId $entityId, $termType, array $languageCodes );

	/**
	 * @param Term[] $wikibaseTerms
	 *
	 * @return string[] strings keyed by language code
	 */
	protected function convertTermsToMap( array $wikibaseTerms ) {
		$terms = array();

		foreach( $wikibaseTerms as $wikibaseTerm ) {
			$languageCode = $wikibaseTerm->getLanguage();
			$terms[$languageCode] = $wikibaseTerm->getText();
		}

		return $terms;
	}

}
