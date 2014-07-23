<?php

namespace Wikibase\DataAccess\PropertyParserFunction;

use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Client\Usage\UsageAccumulator;

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
class VariantsAwareRenderer implements PropertyClaimsRenderer {

	/**
	 * @var PropertyClaimsRendererFactory
	 */
	private $rendererFactory;

	/**
	 * @param string[]
	 */
	private $variants;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @param PropertyClaimsRendererFactory $rendererFactory
	 * @param string[] $variants
	 * @param UsageAccumulator $usageAccumulator
	 */
	public function __construct( PropertyClaimsRendererFactory $rendererFactory, array $variants, UsageAccumulator $usageAccumulator ) {
		$this->rendererFactory = $rendererFactory;
		$this->variants = $variants;
		$this->usageAccumulator = $usageAccumulator;
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
	 * @return string[], key by variant codes
	 */
	private function buildRenderedVariantsArray( EntityId $entityId, $propertyLabelOrId ) {
		$renderedVariantsArray = array();

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
		$renderer = $this->rendererFactory->getLanguageAwareRendererFromCode( $variantCode, $this->usageAccumulator );

		return $renderer->render( $entityId, $propertyLabelOrId );
	}

}
