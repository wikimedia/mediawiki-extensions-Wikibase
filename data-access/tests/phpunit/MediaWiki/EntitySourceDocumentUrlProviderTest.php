<?php

namespace Wikibase\DataAccess\Tests\MediaWiki;

use MediaWiki\Interwiki\InterwikiLookup;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\MediaWiki\EntitySourceDocumentUrlProvider;

/**
 * @covers \Wikibase\DataAccess\MediaWiki\EntitySourceDocumentUrlProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDocumentUrlProviderTest extends \MediaWikiTestCase {

	public function testGivenLocalWikiSource_urlOfLocalWikiIsUsed() {
		$this->setService( 'InterwikiLookup', $this->getMock( InterwikiLookup::class ) );
		$this->setContentLang( 'de' );
		$this->setMwGlobals( 'wgArticlePath', 'http://foo.test/wiki/$1' );

		$sources = new EntitySourceDefinitions( [
			new EntitySource(
				'local',
				false,
				[],
				'http://concept',
				'',
				'',
				''
			)
		] );

		$urlProvider = new EntitySourceDocumentUrlProvider();

		$this->assertEquals(
			[ 'local' => 'http://foo.test/wiki/Spezial:EntityData/' ],
			$urlProvider->getCanonicalDocumentsUrls( $sources )
		);
	}

	public function testGivenNonLocalWikiSource_otherWikiUrlIsUsed() {
		$interwiki = new \Interwiki( 'nonlocal', 'http://other.test/wiki/$1' );
		$interwikiLookup = $this->getMock( InterwikiLookup::class );
		$interwikiLookup->method( 'fetch' )
			->with( 'nonlocal' )
			->willReturn( $interwiki );
		$this->setService( 'InterwikiLookup', $interwikiLookup );
		$this->setContentLang( 'de' );

		$sources = new EntitySourceDefinitions( [
			new EntitySource(
				'nonlocal',
				false,
				[],
				'http://concept',
				'',
				'',
				'nonlocal'
			)
		] );

		$urlProvider = new EntitySourceDocumentUrlProvider();

		$this->assertEquals(
			[ 'nonlocal' => 'http://other.test/wiki/Special:EntityData/' ],
			$urlProvider->getCanonicalDocumentsUrls( $sources )
		);
	}

}
