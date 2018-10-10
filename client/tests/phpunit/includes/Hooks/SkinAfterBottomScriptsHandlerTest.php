<?php

namespace Wikibase\Client\Tests\Hooks;

use PHPUnit4And6Compat;
use Title;

use Wikibase\Client\Hooks\SkinAfterBottomScriptsHandler;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\Client\Hooks\SkinAfterBottomScriptsHandler
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SkinAfterBottomScriptsHandlerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testCreateSchema() {
		$repoLinker = new RepoLinker( 'https://www.wikidata.org', '/wiki/$1', '/w' );
		$handler = new SkinAfterBottomScriptsHandler( $repoLinker );

		$title = $this->mockTitle( 'https://de.wikipedia.org/wiki', 'Douglas Adams' );
		$actual = $handler->createSchema( $title, new ItemId( 'Q42' ) );

		$expected = preg_replace(
			"/\t|\n/",
			'',
			'<script type="application/ld+json">
				{
					"@type":	"schema:Article",
					"name":	"Douglas Adams",
					"url":	"https:\/\/de.wikipedia.org\/wiki\/Douglas_Adams",
					"sameAs":	["https:\/\/www.wikidata.org\/wiki\/Q42"]
				}
			</script>'
		);
		$this->assertEquals( $expected, $actual, 'schema' );
	}

	/**
	 * @param string $baseURL
	 * @param string $text
	 * @return Title
	 */
	private function mockTitle( $baseURL, $titleText ) {
		$title = $this->getMock( Title::class );
		$title->expects( $this->any() )
			->method( 'getFullURL' )
			->will( $this->returnValue( $baseURL . '/' . str_replace( ' ', '_', $titleText ) ) );
		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $titleText ) );
		return $title;
	}

}
