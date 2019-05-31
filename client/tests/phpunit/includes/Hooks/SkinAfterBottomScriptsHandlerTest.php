<?php

namespace Wikibase\Client\Tests\Hooks;

use File;
use PHPUnit4And6Compat;
use Title;

use Wikibase\Client\Hooks\SkinAfterBottomScriptsHandler;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySourceDefinitions;

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

	/**
	 * @dataProvider createSchemaProvider
	 */
	public function testCreateSchema( $revisionTimestamp, $image, $description, $expected ) {
		$client = WikibaseClient::getDefaultInstance();
		$repoLinker = new RepoLinker(
			new DataAccessSettings( 100, false, false, DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION ),
			new EntitySourceDefinitions( [] ),
			'https://www.wikidata.org',
			[ '' => 'https://www.wikidata.org/entity' ],
			'/wiki/$1',
			'/w'
		);
		$handler = new SkinAfterBottomScriptsHandler( $client, $repoLinker );

		$title = $this->mockTitle( 'https://de.wikipedia.org/wiki', 'Douglas Adams', '1022523983' );
		$actual = $handler->createSchema(
			$title, $revisionTimestamp, 'https://www.wikidata.org/entity/Q42', $image, $description
		);
		$this->assertArraySubset( $expected, $actual, 'schema' );
	}

	public function createSchemaProvider() {
		$nullExpected = [
			"@context" => "https://schema.org",
			"@type" => "Article",
			"name" => "Douglas Adams",
			"url" => "https://de.wikipedia.org/wiki/Douglas_Adams",
			"sameAs" => "https://www.wikidata.org/entity/Q42",
			"mainEntity" => "https://www.wikidata.org/entity/Q42",
			"author" => [ "@type" => "Organization" ],
			"publisher" => [
				"@type" => "Organization",
				"logo" => [
					"@type" => "ImageObject"
				]
			],
			"datePublished" => "2002-05-27T18:26:23Z"
		];

		$image = $this->mockFile(
			'https://upload.wikimedia.org/wikipedia/commons/c/c0/Douglas_adams_portrait_cropped.jpg'
		);
		$nonNullExpected = [
			"@context" => "https://schema.org",
			"@type" => "Article",
			"name" => "Douglas Adams",
			"url" => "https://de.wikipedia.org/wiki/Douglas_Adams",
			"sameAs" => "https://www.wikidata.org/entity/Q42",
			"mainEntity" => "https://www.wikidata.org/entity/Q42",
			"author" => [ "@type" => "Organization" ],
			"publisher" => [
				"@type" => "Organization",
				"logo" => [
					"@type" => "ImageObject"
				]
			],
			"datePublished" => "2002-05-27T18:26:23Z",
			"dateModified" => "2018-09-28T20:16:12Z",
			"image" => "https://upload.wikimedia.org/wikipedia/commons/c/c0/Douglas_adams_portrait_cropped.jpg",
			"headline" => "British author and humorist (1952–2001)"
		];

		return [
			[ null, null, null, $nullExpected ],
			[ '1538165772', $image, 'British author and humorist (1952–2001)', $nonNullExpected ]
		];
	}

	/**
	 * @param string|null $url
	 * @return File
	 */
	private function mockFile( $url = null ) {
		$mock = $this->getMockForAbstractClass(
			File::class, [ false, false ], '', true, true, true, [ 'getUrl' ]
		);
		$mock->expects( $this->any() )
			->method( 'getUrl' )
			->will( $this->returnValue( $url ) );
		return $mock;
	}

	/**
	 * @param string $baseURL
	 * @param string $text
	 * @param string|null $earliestRevTimestamp
	 * @return Title
	 */
	private function mockTitle( $baseURL, $titleText, $earliestRevTimestamp = null ) {
		$mock = $this->getMock( Title::class );
		$mock->expects( $this->any() )
			->method( 'getFullURL' )
			->will( $this->returnValue( $baseURL . '/' . str_replace( ' ', '_', $titleText ) ) );
		$mock->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $titleText ) );
		$mock->expects( $this->any() )
			->method( 'getEarliestRevTime' )
			->will( $this->returnValue( $earliestRevTimestamp ) );
		return $mock;
	}

}
