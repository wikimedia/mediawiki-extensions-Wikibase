<?php

namespace Wikibase\DataAccess\PropertyParserFunction;

use Parser;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;

/**
 * Runner for the {{#property}} parser function.
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

		// @todo use id provided as argument, if arbitrary access allowed,
		// which means property ids might also be allowed here.
		$entityId = $this->getItemIdForConnectedPage( $parser );

		// @todo handle when site link is not there, such as site link / entity has been deleted...
		if ( $entityId === null ) {
			wfProfileOut( __METHOD__ );
			return '';
		}

		$renderer = $this->rendererFactory->newRendererFromParser( $parser );
		$rendered = $renderer->render( $entityId, $propertyLabelOrId );
		$result = $this->buildResult( $rendered );

		// Track usage of "all" (that is, arbitrary) data from the item.
		$usageAcc = new ParserOutputUsageAccumulator( $parser->getOutput() );
		$usageAcc->addOtherUsage( $entityId );

		wfProfileOut( __METHOD__ );
		return $result;
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
