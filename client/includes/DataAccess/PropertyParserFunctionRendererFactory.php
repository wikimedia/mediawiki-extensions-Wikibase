<?php

namespace Wikibase\DataAccess;

use Language;
use Parser;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\PropertyLabelResolver;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyParserFunctionRendererFactory {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var PropertyLabelResolver
	 */
	private $propertyLabelResolver;

	/**
	 * @param EntityLookup $entityLookup
	 * @param PropertyLabelResolver $propertyLabelResolver
	 */
	public function __construct(
		EntityLookup $entityLookup,
		PropertyLabelResolver $propertyLabelResolver
	) {
		$this->entityLookup = $entityLookup;
		$this->propertyLabelResolver = $propertyLabelResolver;
	}

	/**
	 * @param Parser $parser
	 *
	 * @return PropertyParserFunctionRenderer
	 */
	public function newFromParser( Parser $parser ) {
		if ( $this->useVariants( $parser ) ) {
			$language = $parser->getConverterLanguage();
			return $this->newVariantsRenderer( $language );
		} else {
			$language = $parser->getTargetLanguage();
			return $this->newLanguageRenderer( $language );
		}
	}

	/**
	 * Check whether variants are used in this parser run.
	 *
	 * @param Parser $parser
	 *
	 * @return boolean
	 */
	private function isParserUsingVariants( Parser $parser ) {
		$parserOptions = $parser->getOptions();

		return $parser->OutputType() === Parser::OT_HTML && !$parserOptions->getInterfaceMessage()
			&& !$parserOptions->getDisableContentConversion();
	}

	/**
	 * @param Parser $parser
	 *
	 * @return boolean
	 */
	private function useVariants( Parser $parser ) {
		return $this->isParserUsingVariants( $parser )
			&& $parser->getConverterLanguage()->hasVariants();
	}

	/**
	 * @param Language $language
	 *
	 * @return PropertyParserFunctionLanguageRenderer
	 */
	private function newLanguageRenderer( Language $language ) {
		return new PropertyParserFunctionLanguageRenderer(
			$this->entityLookup,
			$this->propertyLabelResolver,
			$language
		);
	}

	/**
	 * @param Language $language
	 *
	 * @return PropertyParserFunctionVariantsRenderer
	 */
	private function newVariantsRenderer( Language $language ) {
		return new PropertyParserFunctionVariantsRenderer(
			$this->newLanguageRenderer( $language ),
			$language->getVariants()
		);
	}

}
