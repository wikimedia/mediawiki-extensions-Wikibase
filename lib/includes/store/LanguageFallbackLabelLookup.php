<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LanguageFallbackLabelLookup implements LabelLookup {

	/**
	 * @var EntityTermLookup
	 */
	private $entityTermLookup;

	/**
	 * @var LanguageFallbackChain
	 */
	private $languageFallbackChain;

	/**
	 * @param EntityTermLookup $entityTermLookup
	 * @param LanguageFallbackChain $languageFallbackChain
	 */
	public function __construct(
		EntityTermLookup $entityTermLookup,
		LanguageFallbackChain $languageFallbackChain
	) {
		$this->entityTermLookup = $entityTermLookup;
		$this->languageFallbackChain = $languageFallbackChain;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException
	 * @return string
	 */
	public function getLabel( EntityId $entityId ) {
		$labels = $this->entityTermLookup->getTermsOfType( $entityId, 'label' );
		$extractedData = $this->languageFallbackChain->extractPreferredValue( $labels );

		if ( $extractedData && isset( $extractedData['value'] ) ) {
			return $extractedData['value'];
		}

		throw new OutOfBoundsException( 'Label not found for fallback chain.' );
	}

}
