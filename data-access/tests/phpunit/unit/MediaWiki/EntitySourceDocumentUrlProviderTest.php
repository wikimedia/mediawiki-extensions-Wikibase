<?php

namespace Wikibase\DataAccess\Tests\MediaWiki;

use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWikiUnitTestCase;
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
class EntitySourceDocumentUrlProviderTest extends MediaWikiUnitTestCase {

	public function testGivenLocalWikiSource_urlOfLocalWikiIsUsed() {
		$interwiki = '';
		$sources = $this->makeEntitySourceDefinitions( $interwiki );

		$titleFactory = $this->makeTitleFactory( NS_SPECIAL, 'EntityData', $interwiki );
		$urlProvider = new EntitySourceDocumentUrlProvider( $titleFactory );

		$this->assertEquals(
			[ 'source-name' => 'http://successful/' ],
			$urlProvider->getCanonicalDocumentsUrls( $sources )
		);
	}

	public function testGivenNonLocalWikiSource_otherWikiUrlIsUsed() {
		$interwiki = 'nonlocal';
		$sources = $this->makeEntitySourceDefinitions( $interwiki );

		$titleFactory = $this->makeTitleFactory( NS_MAIN, 'Special:EntityData', $interwiki );
		$urlProvider = new EntitySourceDocumentUrlProvider( $titleFactory );

		$this->assertEquals(
			[ 'source-name' => 'http://successful/' ],
			$urlProvider->getCanonicalDocumentsUrls( $sources )
		);
	}

	private function makeEntitySourceDefinitions( string $interwiki ): EntitySourceDefinitions {
		return new EntitySourceDefinitions(
			[ new DatabaseEntitySource(
				'source-name',
				false,
				[],
				'http://concept',
				'',
				'',
				$interwiki
			) ],
			new SubEntityTypesMapper( [] )
		);
	}

	private function makeTitleFactory( int $ns, string $text, string $interwiki ): TitleFactory {
		$title = $this->createNoOpMock( Title::class, [ 'getCanonicalURL' ] );
		$title->method( 'getCanonicalURL' )->willReturn( 'http://successful' );

		$titleFactory = $this->createNoOpMock( TitleFactory::class, [ 'makeTitle' ] );
		$titleFactory->method( 'makeTitle' )
			->with( $ns, $text, '', $interwiki )
			->willReturn( $title );
		return $titleFactory;
	}

}
