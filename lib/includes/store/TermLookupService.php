<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermLookupService implements TermLookup {

	/**
	 * @var TermsLookup
	 */
	private $termsLookup;

	/**
	 * @param TermsLookup $termsLookup
	 */
	public function __construct( TermsLookup $termsLookup ) {
		$this->termsLookup = $termsLookup;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return array $languageCode => $text
	 */
	public function getLabels( EntityId $entityId ) {
		return $this->termsLookup->getTermsByTermType( $entityId, 'label' );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @return string|null
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$labels = $this->getLabels( $entityId );

		return $this->filterTermsByLanguage( $labels, $languageCode );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return array $languageCode => $text
	 */
	public function getDescriptions( EntityId $entityId ) {
		return $this->termsLookup->getTermsByTermType( $entityId, 'description' );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @return string|null
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		$descriptions = $this->getDescriptions( $entityId );

		return $this->filterTermsByLanguage( $descriptions, $languageCode );
	}

	/**
	 * @param string[] $terms
	 * @param string $languageCode
	 *
	 * @return string|null
	 */
	private function filterTermsByLanguage( $terms, $languageCode ) {
		if ( array_key_exists( $languageCode, $terms ) ) {
			return $terms[$languageCode];
		}

		return null;
	}

}
