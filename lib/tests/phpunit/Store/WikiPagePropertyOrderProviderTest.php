<?php

namespace Wikibase\Lib\Tests\Store;

use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;
use WikiPage;
use WikitextContent;

/**
 * @covers \Wikibase\Lib\Store\WikiPagePropertyOrderProvider
 * @covers \Wikibase\Lib\Store\WikiTextPropertyOrderProvider
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Lucie-Aimée Kaffee
 * @author Marius Hoch
 */
class WikiPagePropertyOrderProviderTest extends MediaWikiIntegrationTestCase {

	public function provideGetPropertyOrder() {
		return WikiTextPropertyOrderProviderTestHelper::provideGetPropertyOrder();
	}

	/**
	 * @dataProvider provideGetPropertyOrder
	 */
	public function testGetPropertyOrder( $text, $expected ) {
		$this->makeWikiPage( 'MediaWiki:Wikibase-SortedProperties', $text );
		$instance = new WikiPagePropertyOrderProvider( Title::newFromText( 'MediaWiki:Wikibase-SortedProperties' ) );
		$propertyOrder = $instance->getPropertyOrder();
		$this->assertSame( $expected, $propertyOrder );
	}

	private function makeWikiPage( $name, $text ) {
		$title = Title::newFromText( $name );
		$wikiPage = WikiPage::factory( $title );
		$wikiPage->doEditContent( new WikitextContent( $text ), 'test' );
	}

	public function testGetPropertyOrder_pageDoesNotExist() {
		$instance = new WikiPagePropertyOrderProvider(
			Title::newFromText( 'MediaWiki:WikiPagePropertyOrderProviderTest-DoesNotExist' )
		);
		$propertyOrder = $instance->getPropertyOrder();
		$this->assertSame( null, $propertyOrder );
	}

}
