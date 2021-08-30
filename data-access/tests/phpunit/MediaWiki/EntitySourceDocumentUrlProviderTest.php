<?php

namespace Wikibase\DataAccess\Tests\MediaWiki;

use MediaWiki\Interwiki\InterwikiLookup;
use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\MediaWiki\EntitySourceDocumentUrlProvider;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @covers \Wikibase\DataAccess\MediaWiki\EntitySourceDocumentUrlProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDocumentUrlProviderTest extends MediaWikiIntegrationTestCase {

	public function testGivenLocalWikiSource_urlOfLocalWikiIsUsed() {
		$this->setService( 'InterwikiLookup', $this->createMock( InterwikiLookup::class ) );
		$this->setMwGlobals( [
			'wgLanguageCode' => 'de',
			'wgArticlePath' => 'http://foo.test/wiki/$1',
		] );

		$sources = new EntitySourceDefinitions(
			[ new DatabaseEntitySource(
				'local',
				false,
				[],
				'http://concept',
				'',
				'',
				''
			) ],
			new SubEntityTypesMapper( [] )
		);

		$urlProvider = new EntitySourceDocumentUrlProvider( $this->getServiceContainer()->getTitleFactory() );

		$this->assertEquals(
			[ 'local' => 'http://foo.test/wiki/Spezial:EntityData/' ],
			$urlProvider->getCanonicalDocumentsUrls( $sources )
		);
	}

	public function testGivenNonLocalWikiSource_otherWikiUrlIsUsed() {
		$interwiki = new \Interwiki( 'nonlocal', 'http://other.test/wiki/$1' );
		$interwikiLookup = $this->createMock( InterwikiLookup::class );
		$interwikiLookup->method( 'fetch' )
			->with( 'nonlocal' )
			->willReturn( $interwiki );
		$this->setService( 'InterwikiLookup', $interwikiLookup );
		$this->setMwGlobals( 'wgLanguageCode', 'de' );

		$sources = new EntitySourceDefinitions(
			[ new DatabaseEntitySource(
				'nonlocal',
				false,
				[],
				'http://concept',
				'',
				'',
				'nonlocal'
			) ],
		new SubEntityTypesMapper( [] ) );

		$urlProvider = new EntitySourceDocumentUrlProvider( $this->getServiceContainer()->getTitleFactory() );

		$this->assertEquals(
			[ 'nonlocal' => 'http://other.test/wiki/Special:EntityData/' ],
			$urlProvider->getCanonicalDocumentsUrls( $sources )
		);
	}

}
