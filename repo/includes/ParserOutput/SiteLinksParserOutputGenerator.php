<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Creates parser output for a list of site links.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SiteLinksParserOutputGenerator {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	public function __construct( EntityTitleLookup $entityTitleLookup ) {
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * Assigns information about the given list of site links to the parser output.
	 *
	 * @since 0.5
	 *
	 * @param ParserOutput $pout
	 * @param SiteLinkList $siteLinks
	 */
	public function assignSiteLinksToParserOutput( ParserOutput $pout, SiteLinkList $siteLinks ) {
		$pout->setProperty( 'wb-sitelinks', $siteLinks->count() );

		//@todo: record sitelinks as iwlinks

		foreach ( $siteLinks as $siteLink ) {
			$this->assignBadgesToParserOutput( $pout, $siteLink );
		}
	}

	private function assignBadgesToParserOutput( ParserOutput $pout, SiteLink $siteLink ) {
		foreach ( $siteLink->getBadges() as $badge ) {
			$pout->addLink( $this->entityTitleLookup->getTitleForId( $badge ) );
		}
	}

}
