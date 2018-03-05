<?php

namespace Wikibase\Client\DataAccess\ParserFunctions;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Handler of the {{#property}} parser function.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
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
	 * @throws OutOfBoundsException
	 * @return string Wikitext
	 */
	public function render( EntityId $entityId, $propertyLabelOrId ) {
		$renderedVariantsArray = $this->buildRenderedVariantsArray( $entityId, $propertyLabelOrId );

		return $this->processRenderedArray( $renderedVariantsArray );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId
	 *
	 * @throws OutOfBoundsException
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
	 * @return string Wikitext
	 */
	private function processRenderedArray( array $textArray ) {
		if ( $textArray === [] ) {
			return '';
		}

		if ( count( array_unique( $textArray ) ) === 1 ) {
			return reset( $textArray );
		}

		$text = '';

		foreach ( $textArray as $variantCode => $variantText ) {
			$text .= "$variantCode:$variantText;";
		}

		return '-{' . $text . '}-';
	}

	/**
	 * @param string $variantCode
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId
	 *
	 * @throws OutOfBoundsException
	 * @return string Wikitext
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
