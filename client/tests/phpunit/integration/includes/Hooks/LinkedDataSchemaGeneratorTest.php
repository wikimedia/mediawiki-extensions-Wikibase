<?php

namespace Wikibase\Client\Tests\Unit\Hooks;

use MediaWiki\FileRepo\File\File;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use Wikibase\Client\Hooks\LinkedDataSchemaGenerator;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\ItemId;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Client\Hooks\LinkedDataSchemaGenerator
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LinkedDataSchemaGeneratorTest extends \PHPUnit\Framework\TestCase {
	private const CREATE_SCHEMA_ELEMENT_EXPECTED_OUTPUT = '{
        "@context": "https://schema.org",
        "@type": "Article",
        "name": "Douglas Adams",
        "url": "https://de.wikipedia.org/wiki/Douglas_Adams",
        "sameAs": "",
        "mainEntity": "",
        "author": {
            "@type": "Organization",
            "name": ""
        },
        "publisher": {
            "@type": "Organization",
            "name": "",
            "logo": {
                "@type": "ImageObject",
                "url": ""
            }
        },
        "datePublished": "2018-09-28T20:16:12Z",
        "dateModified": "2018-09-28T20:20:00Z",
        "headline": "descr"
    }';

	public function testCreateSchemaElement_firstRevisionTimestampPresent() {
		// Test case: when firstRevisionTimestamp is present as input we do not need to look up in revisionLookup
		$firstRevisionTimestamp = "1538165772";
		$revisionTimestamp = "1538166000";
		$expectedJsonOutput = self::CREATE_SCHEMA_ELEMENT_EXPECTED_OUTPUT;

		$repoLinker = $this->stubRepoLinker();
		$mockRevisionLookup = $this->createMockRevisionLookup();
		$generator = new LinkedDataSchemaGenerator(
			$mockRevisionLookup,
			$repoLinker
		);
		$generatorWrapper = TestingAccessWrapper::newFromObject( $generator );
		$title = $this->stubTitle( 'https://de.wikipedia.org/wiki', 'Douglas Adams' );

		// Expect to not call the revisionLookup
		$mockRevisionLookup->expects( $this->never() )
			->method( 'getFirstRevision' );

		$actualOutputString = $generatorWrapper->createSchemaElement(
			$title, $revisionTimestamp, $firstRevisionTimestamp, new ItemId( 'Q42' ), "descr"
		);
		$actualJsonOutput = $this->extractJsonFromScriptTag( $actualOutputString );

		$this->assertJsonStringEqualsJsonString( $expectedJsonOutput, $actualJsonOutput );
	}

	public function testCreateSchemaElement_firstRevisionTimestampNull() {
		// Test case: if firstRevisionTimestamp is not passed in, the fallback will be used to look it up from the revisionLookup
		$firstRevisionTimestamp = null;
		$revisionTimestamp = "1538166000";
		$expectedJsonOutput = self::CREATE_SCHEMA_ELEMENT_EXPECTED_OUTPUT;

		$repoLinker = $this->stubRepoLinker();
		$mockRevisionLookup = $this->createMockRevisionLookup();
		$generator = new LinkedDataSchemaGenerator(
			$mockRevisionLookup,
			$repoLinker
		);
		$generatorWrapper = TestingAccessWrapper::newFromObject( $generator );
		$title = $this->stubTitle( 'https://de.wikipedia.org/wiki', 'Douglas Adams' );

		// Assert that it uses the revisionLookup
		$mockRevisionLookup->expects( $this->once() )
		->method( 'getFirstRevision' );

		$actualOutputString = $generatorWrapper->createSchemaElement(
			$title, $revisionTimestamp, $firstRevisionTimestamp, new ItemId( 'Q42' ), "descr"
		);
		$actualJsonOutput = $this->extractJsonFromScriptTag( $actualOutputString );

		$this->assertJsonStringEqualsJsonString( $expectedJsonOutput, $actualJsonOutput );
	}

	/**
	 * @dataProvider createSchemaProvider
	 */
	public function testCreateSchema( $firstRevisionTimestamp, $revisionTimestamp, callable $imageFactory, $description, $expected ) {
		$image = $imageFactory( $this );
		$repoLinker = $this->stubRepoLinker();
		$generator = new LinkedDataSchemaGenerator(
			$this->createMockRevisionLookup(),
			$repoLinker
		);
		$generatorWrapper = TestingAccessWrapper::newFromObject( $generator );

		$title = $this->stubTitle( 'https://de.wikipedia.org/wiki', 'Douglas Adams' );

		$actual = $generatorWrapper->createSchema(
			$title, $revisionTimestamp, $firstRevisionTimestamp, 'https://www.wikidata.org/entity/Q42', $image, $description
		);
		$this->assertSchemaSubset( $expected, $actual );
	}

	private function assertSchemaSubset( array $expected, array $actual ) {
		foreach ( $expected as $key => $val ) {
			$this->assertArrayHasKey( $key, $actual );
			if ( is_array( $val ) ) {
				$this->assertSchemaSubset( $expected[$key], $actual[$key] );
			} else {
				$this->assertSame( $val, $actual[$key] );
			}
		}
	}

	public static function createSchemaProvider() {
		$nullModifiedDateExpected = [
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
					"@type" => "ImageObject",
				],
			],
			"datePublished" => "2018-09-28T20:16:12Z",
		];

		$nullFirstRevisionTimestampExpected = [
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
					"@type" => "ImageObject",
				],
			],
			"dateModified" => "2018-09-28T20:20:00Z",
			"image" => "https://upload.wikimedia.org/wikipedia/commons/c/c0/Douglas_adams_portrait_cropped.jpg",
			"headline" => "British author and humorist (1952–2001)",
		];

		$nonNullValuesExpected = [
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
					"@type" => "ImageObject",
				],
			],
			"datePublished" => "2018-09-28T20:16:12Z",
			"dateModified" => "2018-09-28T20:20:00Z",
			"image" => "https://upload.wikimedia.org/wikipedia/commons/c/c0/Douglas_adams_portrait_cropped.jpg",
			"headline" => "British author and humorist (1952–2001)",
		];

		return [
			// arguments are:
			// $firstRevisionTimestamp, $revisionTimestamp, callable $imageFactory, $description, $expected

			// Test case: null modified date, no picture or description
			[ '1538165772', null, fn () => null, null, $nullModifiedDateExpected ],

			// Test case: null firstRevisionTimestamp. This means no datePublished will be in the schema
			[
				null,
				'1538166000',
				fn ( self $self ) => $self->mockFile(
					'https://upload.wikimedia.org/wikipedia/commons/c/c0/Douglas_adams_portrait_cropped.jpg'
				),
				'British author and humorist (1952–2001)',
				$nullFirstRevisionTimestampExpected,
			],

			// Test case: nonNull firstRevisionTimestamp and modified date
			[
				'1538165772',
				'1538166000',
				fn ( self $self ) => $self->mockFile(
					'https://upload.wikimedia.org/wikipedia/commons/c/c0/Douglas_adams_portrait_cropped.jpg'
				),
				'British author and humorist (1952–2001)',
				$nonNullValuesExpected,
			],
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
		$mock->method( 'getUrl' )
			->willReturn( $url );
		return $mock;
	}

	/**
	 * @param string $baseURL
	 * @param string $titleText
	 * @return Title
	 */
	private function stubTitle( $baseURL, $titleText ) {
		$stub = $this->createStub( Title::class );
		$stub->method( 'getFullURL' )
			->willReturn( $baseURL . '/' . str_replace( ' ', '_', $titleText ) );
		$stub->method( 'getText' )
			->willReturn( $titleText );
		return $stub;
	}

	/**
	 * @return RepoLinker
	 */
	private function stubRepoLinker() {
		$stub = $this->createStub( RepoLinker::class );
		$stub
			->method( 'getEntityUrl' )
			->willReturn( 'foo' );
		return $stub;
	}

	/**
	 * @return RevisionLookup
	 */
	private function createMockRevisionLookup(): RevisionLookup {
		$revisionRecord = $this->createMock( RevisionRecord::class );
		$revisionRecord->method( 'getTimestamp' )
			->willReturn( '1538165772' );
		$mockRevLookup = $this->createMock( RevisionLookup::class );
		$mockRevLookup->method( 'getFirstRevision' )
			->willReturn( $revisionRecord );
		return $mockRevLookup;
	}

	private function extractJsonFromScriptTag( string $actualOutputString ): string {
		preg_match( '/<script type="application\/ld\+json">(.*?)<\/script>/s', $actualOutputString, $matches );

		if ( !isset( $matches[1] ) ) {
			$this->fail( "No JSON found in the script tag" );
		}

		return $matches[1];
	}
}
