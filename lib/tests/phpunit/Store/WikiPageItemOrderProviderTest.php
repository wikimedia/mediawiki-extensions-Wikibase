<?php
declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\Lib\Store\WikiPageItemOrderProvider;
use WikitextContent;

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

	protected function setUp(): void {
		parent::setUp();
		$this->tablesUsed[] = 'page';
	}

	public function provideGetItemOrder(): iterable {
		return WikiPageItemOrderProviderTestHelper::provideGetItemOrder();
	}

	/**
	 * @dataProvider provideGetItemOrder
	 */
	public function testGetItemOrder( string $text, ?array $expected ): void {
		$this->makeWikiPage( 'MediaWiki:WikibaseLexeme-SortedGrammaticalFeaturesTest', $text );
		$instance = new WikiPageItemOrderProvider(
			$this->getServiceContainer()->getWikiPageFactory(),
			Title::newFromTextThrow( 'MediaWiki:WikibaseLexeme-SortedGrammaticalFeaturesTest' )
		);
		$itemOrder = $instance->getItemOrder();
		$this->assertSame( $expected, $itemOrder );
	}

	private function makeWikiPage( string $name, string $text ): void {
		$title = Title::newFromTextThrow( $name );
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
			Title::newFromTextThrow( 'MediaWiki:WikibaseLexeme-SortedGrammaticalFeatures-Test-DoesNotExist' )
		);
		$itemOrder = $instance->getItemOrder();
		$this->assertSame( null, $itemOrder );
	}

}
