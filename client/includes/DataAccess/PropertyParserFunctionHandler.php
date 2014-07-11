<?php

namespace Wikibase\DataAccess;

use Language;
use Parser;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\PropertyLabelResolver;
use Wikibase\PropertyParserFunction;

class PropertyParserFunctionHandler {

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var PropertyParserFunctionLanguageRenderer
	 */
	private $languageRenderer;

	/**
	 * @var PropertyParserFunctionVariantsRenderer
	 */
	private $variantsRenderer;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param PropertyParserFunctionLanguageRenderer $languageRenderer
	 * @param PropertyParserFunctionVariantsRenderer $variantsRenderer
	 * @param string $siteId
	 */
	public function __construct(
		SiteLinkLookup $siteLinkLookup,
		PropertyParserFunctionLanguageRenderer $languageRenderer,
		PropertyParserFunctionVariantsRenderer $variantsRenderer,
		$siteId
	) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->languageRenderer = $languageRenderer;
		$this->variantsRenderer = $variantsRenderer;
		$this->siteId = $siteId;
	}

	/**
	 * @param Parser $parser
	 * @param string $propertyLabel
	 *
	 * @return string
	 */
	public function handle( Parser $parser, $propertyLabel ) {
		wfProfileIn( __METHOD__ );

		$itemId = $this->getItemIdForConnectedPage( $parser );

		// @todo handle when site link is not there, such as site link / entity has been deleted...
		if ( $itemId === null ) {
			wfProfileOut( __METHOD__ );
			return '';
		}

		$result = $this->renderForItemId( $parser, $itemId, $propertyLabel );

		wfProfileOut( __METHOD__ );
		return $result;
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
	 * @param Parser $parser
	 * @param ItemId $itemId
	 * @param string $propertyLabel
	 *
	 * @return array
	 */
	private function renderForItemId( Parser $parser, ItemId $itemId, $propertyLabel ) {
		// @todo maybe use dispatching render or such
		if ( $this->useVariants( $parser ) ) {
			$language = $parser->getConverterLanguage();
			$rendered = $this->variantsRenderer->render( $itemId, $language, $propertyLabel );
		} else {
			$language = $parser->getTargetLanguage();
			$rendered = $this->languageRenderer->render( $itemId, $language, $propertyLabel );
		}

		return $this->buildResult( $rendered );
	}

	/**
	 * @param string $rendered
	 *
	 * @return array
	 */
	private function buildResult( $rendered ) {
		$result = array(
			$rendered,
			'noparse' => false, // parse wikitext
			'nowiki' => false,  // formatters take care of escaping as needed
		);

		return $result;
	}

	/**
	 * @param Parser $parser
	 *
	 * @return ItemId
	 */
	private function getItemIdForConnectedPage( Parser $parser ) {
		$title = $parser->getTitle();
		$siteLink = new SiteLink( $this->siteId, $title->getFullText() );
		$itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );

		return $itemId;
	}

}
