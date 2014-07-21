<?php

namespace Wikibase\DataAccess\PropertyParserFunction;

use Language;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Handler of the {{#property}} parser function.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 */
class VariantsAwareRenderer implements Renderer {

	/**
	 * @var RendererFactory
	 */
	private $rendererFactory;

	/**
	 * @param string[]
	 */
	private $variants;

	/**
	 * @param RendererFactory $rendererFactory
	 * @param string[] $variants
	 */
	public function __construct( RendererFactory $rendererFactory, array $variants ) {
		$this->rendererFactory = $rendererFactory;
		$this->variants = $variants;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabel property label or ID (pXXX)
	 *
	 * @return string
	 */
	public function render( EntityId $entityId, $propertyLabel ) {
		$renderedVariantsArray = $this->buildRenderedVariantsArray( $entityId, $propertyLabel );

		return $this->processRenderedArray( $renderedVariantsArray );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabel
	 *
	 * @return string[], key by variant codes
	 */
	private function buildRenderedVariantsArray( EntityId $entityId, $propertyLabel ) {
		$renderedVariantsArray = array();

		foreach ( $this->variants as $variantCode ) {
			$variantText = $this->getVariantText( $variantCode, $entityId, $propertyLabel );

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
	 * @param string $propertyLabel
	 *
	 * @return string
	 */
	private function getVariantText( $variantCode, EntityId $entityId, $propertyLabel ) {
		$variantLanguage = Language::factory( $variantCode );
		$renderer = $this->rendererFactory->newLanguageAwareRenderer( $variantLanguage );

		return $renderer->render( $entityId, $propertyLabel );
	}

}
