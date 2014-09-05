<?php

namespace Wikibase\Test;

use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\ParserOutput\SiteLinksParserOutputGenerator;

/**
 * @covers Wikibase\Repo\ParserOutput\SiteLinksParserOutputGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @group Database
 *		^---- needed because we rely on Title objects internally
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SiteLinksParserOutputGeneratorTest extends \PHPUnit_Framework_TestCase {

	public function testAssignToParserOutput() {
		$siteLinksParserOutputGenerator = $this->getSiteLinksParserOutputGenerator();
		$pout = new ParserOutput();

		$siteLinks = new SiteLinkList();
		$siteLinks->addNewSiteLink( 'enwiki', 'Foo' );
		$siteLinks->addNewSiteLink( 'frwiki', 'Bar', array( new ItemId( 'Q42' ) ) );
		$siteLinks->addNewSiteLink( 'dewiki', 'Baz', array( new ItemId( 'Q42' ), new ItemId( 'Q51' ) ) );


		$siteLinksParserOutputGenerator->assignToParserOutput( $pout, $siteLinks );

		$links = $pout->getLinks();
		$expectedLinks = array( 'Q42', 'Q51' );
		$this->assertEquals( $expectedLinks, array_keys( $links[NS_MAIN] ) );
	}

	private function getSiteLinksParserOutputGenerator() {
		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				return Title::makeTitle( NS_MAIN, $entityId->getPrefixedId() );
			} ) );

		return new SiteLinksParserOutputGenerator( $entityTitleLookup );
	}

}
