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

		$title = $this->mockTitle( 'https://de.wikipedia.org/wiki', 'Douglas Adams', '1022523983' );
		$revisionTimestamp = '1538165772';
		$actual = $handler->createSchema( $title, $revisionTimestamp, null, null, new ItemId( 'Q42' ) );
		$expected = preg_replace(
			"/\t|\n/",
			'',
			'<script type="application/ld+json">
				{
					"@context":	"https:\/\/schema.org",
					"@type":	"Article",
					"name":	"Douglas Adams",
					"url":	"https:\/\/de.wikipedia.org\/wiki\/Douglas_Adams",
					"sameAs":	"https:\/\/www.wikidata.org\/wiki\/Q42",
					"mainEntity":	"https:\/\/www.wikidata.org\/wiki\/Q42",
					"author":	{	"@type":	"Organization",	"name":	"Wikipedia"	},
					"publisher":	{
						"@type":	"Organization",
						"name":	"Wikimedia Foundation, Inc.",
						"logo":	{
							"@type":	"ImageObject",
							"url":	"https:\/\/www.wikidata.org\/extensions\/Wikibase\/client\/assets\/wikimedia.png"
						}
					},
					"datePublished":	"2002-05-27T18:26:23Z",
					"dateModified":	"2018-09-28T20:16:12Z"
				}
			</script>'
		);
		$this->assertEquals( $expected, $actual, 'schema' );
	}

	/**
	 * @param string $baseURL
	 * @param string $text
	 * @param string|null $earliestRevTimestamp
	 * @return Title
	 */
	private function mockTitle( $baseURL, $titleText, $earliestRevTimestamp ) {
		$title = $this->getMock( Title::class );
		$title->expects( $this->any() )
			->method( 'getFullURL' )
			->will( $this->returnValue( $baseURL . '/' . str_replace( ' ', '_', $titleText ) ) );
		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $titleText ) );
		$title->expects( $this->any() )
			->method( 'getEarliestRevTime' )
			->will( $this->returnValue( $earliestRevTimestamp ) );
		return $title;
	}

}
