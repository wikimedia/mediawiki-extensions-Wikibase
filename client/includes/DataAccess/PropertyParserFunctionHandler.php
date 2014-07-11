<?php

namespace Wikibase\DataAccess;

use Parser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyParserFunctionHandler {

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var PropertyParserFunctionRendererFactory
	 */
	private $renderFactory;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param PropertyParserFunctionRendererFactory $renderFactory
	 * @param string $siteId
	 */
	public function __construct(
		SiteLinkLookup $siteLinkLookup,
		PropertyParserFunctionRendererFactory $rendererFactory,
		$siteId
	) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->rendererFactory = $rendererFactory;
		$this->siteId = $siteId;
	}

	/**
	 * @fixme name this method something nicer!
	 *
	 * @param Parser $parser
	 * @param string $propertyLabel
	 *
	 * @return string
	 */
	public function handle( Parser $parser, $propertyLabel ) {
		wfProfileIn( __METHOD__ );

		// @todo use id provided as argument, if arbitrary access allowed
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
	 * @param Parser $parser
	 * @param ItemId $itemId
	 * @param string $propertyLabel
	 *
	 * @return array
	 */
	private function renderForItemId( Parser $parser, ItemId $itemId, $propertyLabel ) {
		$renderer = $this->rendererFactory->newFromParser( $parser );
		$rendered = $renderer->render( $itemId, $propertyLabel );

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
