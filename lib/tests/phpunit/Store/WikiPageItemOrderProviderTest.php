<?php
declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

use MediaWiki\Content\WikitextContent;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Store\WikiPageItemOrderProvider;

/**
 * @covers \Wikibase\Lib\Store\WikiPageItemOrderProvider
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Noa Rave
 */
class WikiPageItemOrderProviderTest extends MediaWikiIntegrationTestCase {

	public static function provideGetItemOrder(): iterable {
		return WikiPageItemOrderProviderTestHelper::provideGetItemOrder();
	}

	/**
	 * @dataProvider provideGetItemOrder
	 */
	public function testGetItemOrder( string $text, ?array $expected ): void {
		$title = Title::makeTitle( NS_MEDIAWIKI, 'WikibaseLexeme-SortedGrammaticalFeaturesTest' );
		$this->makeWikiPage( $title, $text );
		$instance = new WikiPageItemOrderProvider(
			$this->getServiceContainer()->getWikiPageFactory(),
			$title
		);
		$itemOrder = $instance->getItemOrder();
		$this->assertSame( $expected, $itemOrder );
	}

	private function makeWikiPage( Title $title, string $text ): void {
		$wikiPage = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$wikiPage->doUserEditContent(
			new WikitextContent( $text ),
			$this->getTestUser()->getUser(),
			'test'
		);
	}

	public function testGetItemOrder_pageDoesNotExist(): void {
		$instance = new WikiPageItemOrderProvider(
			$this->getServiceContainer()->getWikiPageFactory(),
			Title::makeTitle( NS_MEDIAWIKI, 'WikibaseLexeme-SortedGrammaticalFeatures-Test-DoesNotExist' )
		);
		$itemOrder = $instance->getItemOrder();
		$this->assertSame( null, $itemOrder );
	}

}
