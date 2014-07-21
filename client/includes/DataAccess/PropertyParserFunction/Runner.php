<?php

namespace Wikibase\DataAccess\PropertyParserFunction;

use InvalidArgumentException;
use Language;
use Parser;
use Status;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\SiteLink;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\PropertyLabelResolver;

/**
 * Handler of the {{#property}} parser function.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 */
class Runner {

	/**
	 * @var RendererFactory
	 */
	private $rendererFactory;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @param RendererFactory $rendererFactory
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param string $siteId
	 */
	public function __construct(
		RendererFactory $rendererFactory,
		SiteLinkLookup $siteLinkLookup,
		$siteId
	) {
		$this->rendererFactory = $rendererFactory;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteId = $siteId;
	}

	/**
	 * @param Parser $parser
	 * @param string $propertyLabelOrId property label or ID (pXXX)
	 *
	 * @return string Wikitext
	 */
	public function runPropertyParserFunction( Parser $parser, $propertyLabelOrId ) {
		wfProfileIn( __METHOD__ );

		// @todo use id provided as argument, if arbitrary access allowed
		$itemId = $this->getItemIdForConnectedPage( $parser );

		// @todo handle when site link is not there, such as site link / entity has been deleted...
		if ( $itemId === null ) {
			wfProfileOut( __METHOD__ );
			return '';
		}

		$rendered = $this->renderForEntityId( $parser, $itemId, $propertyLabelOrId );
		$result = $this->buildResult( $rendered );

		wfProfileOut( __METHOD__ );
		return $result;
	}

	/**
	 * @param Parser $parser
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId
	 *
	 * @return string
	 */
	private function renderForEntityId( Parser $parser, EntityId $entityId, $propertyLabelOrId ) {
		if ( $this->useVariants( $parser ) ) {
			return $this->renderInVariants( $parser, $entityId, $propertyLabelOrId );
		} else {
			$targetLanguage = $parser->getTargetLanguage();
			return $this->renderInLanguage( $entityId, $propertyLabelOrId, $targetLanguage );
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
		$converterLanguageHasVariants = $parser->getConverterLanguage()->hasVariants();
		return $this->isParserUsingVariants( $parser ) && $converterLanguageHasVariants;
	}

	/**
	 * @param Parser $parser
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId
	 *
	 * @return string
	 */
	private function renderInVariants( Parser $parser, EntityId $entityId, $propertyLabelOrId ) {
		$variants = $parser->getConverterLanguage()->getVariants();
		$variantsRenderer = $this->rendererFactory->newVariantsRenderer( $variants );

		return $variantsRenderer->render( $entityId, $propertyLabelOrId );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId property label or ID (pXXX)
	 * @param Language $language
	 *
	 * @return string
	 */
	private function renderInLanguage( EntityId $entityId, $propertyLabelOrId, Language $language ) {
		$renderer = $this->rendererFactory->newFromLanguage( $language );
		return $renderer->render( $entityId, $propertyLabelOrId );
	}

	/**
	 * @param Parser $parser
	 *
	 * @return ItemId|null
	 */
	private function getItemIdForConnectedPage( Parser $parser ) {
		$title = $parser->getTitle();
		$siteLink = new SiteLink( $this->siteId, $title->getFullText() );
		$itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );

		return $itemId;
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
	 * @since 0.4
	 *
	 * @param Parser $parser
	 * @param string $propertyLabelOrId property label or ID (pXXX)
	 *
	 * @return array
	 */
	public static function render( Parser $parser, $propertyLabelOrId ) {
		wfProfileIn( __METHOD__ );

		$runner = WikibaseClient::getDefaultInstance()->getPropertyParserFunctionRunner();
		$result = $runner->runPropertyParserFunction( $parser, $propertyLabelOrId );

		wfProfileOut( __METHOD__ );
		return $result;
	}

}
