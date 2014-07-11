<?php

namespace Wikibase\DataAccess;

use Language;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Liangent
 */
class PropertyParserFunctionVariantsRenderer implements PropertyParserFunctionRenderer {

	/**
	 * @var PropertyParserFunctionLanguageRenderer
	 */
	private $languageRenderer;

	/**
	 * @var array
	 */
	private $variants;

	/**
	 * @param PropertyParserFunctionLanguageRenderer $languageRenderer
	 * @param array $variants
	 */
	public function __construct(
		PropertyParserFunctionLanguageRenderer $languageRenderer,
		array $variants
	) {
		$this->languageRenderer = $languageRenderer;
		$this->variants = $variants;
	}

	/**
	 * @param ItemId $itemId
	 * @param string $propertyLabel
	 */
	public function render( ItemId $itemId, $propertyLabel ) {
		$renderedVariantsArray = $this->renderInVariants(
			$itemId,
			$this->variants,
			$propertyLabel
		);

		return $this->processRenderedArray( $renderedVariantsArray );
	}

	/**
	 * @param ItemId $itemId
	 * @param string[] $variants Variant codes
	 * @param string $propertyLabel property label or ID (pXXX)
	 *
	 * @return string[], key by variant codes
	 */
	private function renderInVariants( $itemId, array $variants, $propertyLabel ) {
		$textArray = array();

		foreach ( $variants as $variantCode ) {
			$variantLanguage = Language::factory( $variantCode );
			$variantText = $this->languageRenderer->render( $itemId, $variantLanguage, $propertyLabel );
			// LanguageConverter doesn't handle empty strings correctly, and it's more difficult
			// to fix the issue there, as it's using empty string as a special value.
			// Also keeping the ability to check a missing property with {{#if: }} is another reason.
			if ( $variantText !== '' ) {
				$textArray[$variantCode] = $variantText;
			}
		}

		return $textArray;
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

}
