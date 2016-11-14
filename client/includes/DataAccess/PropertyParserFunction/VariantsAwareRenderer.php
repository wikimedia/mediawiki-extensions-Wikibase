<?php

namespace Wikibase\Client\DataAccess\PropertyParserFunction;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Handler of the {{#property}} parser function.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 */
class VariantsAwareRenderer implements StatementGroupRenderer {

	/**
	 * @var string[]
	 */
	private $variants;

	/**
	 * @var LanguageAwareRenderer[]
	 */
	private $languageAwareRenderers;

	/**
	 * @param LanguageAwareRenderer[] $languageAwareRenderers
	 * @param string[] $variants
	 */
	public function __construct( array $languageAwareRenderers, array $variants ) {
		$this->languageAwareRenderers = $languageAwareRenderers;
		$this->variants = $variants;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId
	 *
	 * @return string
	 */
	public function render( EntityId $entityId, $propertyLabelOrId ) {
		$renderedVariantsArray = $this->buildRenderedVariantsArray( $entityId, $propertyLabelOrId );

		return $this->processRenderedArray( $renderedVariantsArray );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId
	 *
	 * @return string[] key by variant codes
	 */
	private function buildRenderedVariantsArray( EntityId $entityId, $propertyLabelOrId ) {
		$renderedVariantsArray = [];

		foreach ( $this->variants as $variantCode ) {
			$variantText = $this->getVariantText( $variantCode, $entityId, $propertyLabelOrId );

			// LanguageConverter doesn't handle empty strings correctly, and it's more difficult
			// to fix the issue there, as it's using empty string as a special value.
			// Also keeping the ability to check a missing property with {{#if: }} is another reason.
			if ( $variantText !== '' ) {
				$renderedVariantsArray[$variantCode] = $variantText;
			}
		}

		return $renderedVariantsArray;
	}

	/**
	 * Post-process rendered array (variant text) into wikitext to be used in pages.
	 *
	 * @param string[] $textArray
	 *
	 * @return string
	 */
	private function processRenderedArray( array $textArray ) {
		// We got arrays, so they must have already checked that variants are being used.
		$text = '';
		foreach ( $textArray as $variantCode => $variantText ) {
			$text .= "$variantCode:$variantText;";
		}
		if ( $text !== '' ) {
			$text = '-{' . $text . '}-';
		}

		return $text;
	}

	/**
	 * @param string $variantCode
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId
	 *
	 * @return string
	 */
	private function getVariantText( $variantCode, EntityId $entityId, $propertyLabelOrId ) {
		$renderer = $this->getLanguageAwareRendererFromCode( $variantCode );

		return $renderer->render( $entityId, $propertyLabelOrId );
	}

	/**
	 * @param string $variantCode
	 *
	 * @throws OutOfBoundsException
	 * @return LanguageAwareRenderer
	 */
	private function getLanguageAwareRendererFromCode( $variantCode ) {
		if ( !isset( $this->languageAwareRenderers[$variantCode] ) ) {
			throw new OutOfBoundsException( 'No LanguageAwareRenderer set for ' . $variantCode );
		}

		return $this->languageAwareRenderers[$variantCode];
	}

}
