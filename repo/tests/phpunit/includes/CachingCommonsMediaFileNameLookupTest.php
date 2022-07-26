<?php

namespace Wikibase\Repo\Tests;

use HashBagOStuff;
use MediaWiki\Site\MediaWikiPageNameNormalizer;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;

/**
 * @covers \Wikibase\Repo\CachingCommonsMediaFileNameLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class CachingCommonsMediaFileNameLookupTest extends \PHPUnit\Framework\TestCase {

	public function testNormalize() {
		$lookup = new CachingCommonsMediaFileNameLookup(
			$this->getMediaWikiPageNameNormalizer( $this->once() ),
			new HashBagOStuff()
		);

		// Two lookups only cause one API call.
		$this->assertSame(
			'Foo.bar',
			$lookup->lookupFileName( 'Foo.bar' )
		);
		$this->assertSame(
			'Foo.bar',
			$lookup->lookupFileName( 'Foo.bar' )
		);
	}

	public function testNormalize_cachedValueIsUsed() {
		$cache = new HashBagOStuff();
		$cache->set( 'commons-media-Foo.bar', 'Bar.foo' );

		$lookup = new CachingCommonsMediaFileNameLookup(
			$this->getMediaWikiPageNameNormalizer( $this->never() ),
			$cache
		);

		$this->assertSame(
			'Bar.foo',
			$lookup->lookupFileName( 'Foo.bar' )
		);
	}

	public function testNormalize_cachedWithOriginalNameAndNormalized() {
		$cache = new HashBagOStuff();
		$cache->set( 'commons-media-Foo.bar', 'Bar.foo' );

		$lookup = new CachingCommonsMediaFileNameLookup(
			$this->getMediaWikiPageNameNormalizer( $this->once() ),
			$cache
		);

		$this->assertSame(
			'TARGET.png',
			$lookup->lookupFileName( 'REDIRECT.cat' )
		);
		$this->assertSame(
			'TARGET.png',
			$lookup->lookupFileName( 'TARGET.png' )
		);
		$this->assertSame(
			'TARGET.png',
			$lookup->lookupFileName( 'REDIRECT.cat' )
		);
	}

	/**
	 * @param Invocation $matcher
	 *
	 * @return MediaWikiPageNameNormalizer
	 */
	private function getMediaWikiPageNameNormalizer( $matcher ) {
		$fileNameLookup = $this->createMock( MediaWikiPageNameNormalizer::class );

		$fileNameLookup->expects( $matcher )
			->method( 'normalizePageName' )
			->willReturnCallback( function( $fileName, $apiUrl ) {
				$this->assertSame( 'https://commons.wikimedia.org/w/api.php', $apiUrl );

				if ( strpos( $fileName, 'NOT-FOUND' ) !== false ) {
					return false;
				}

				if ( strpos( $fileName, 'REDIRECT' ) !== false ) {
					return 'File:TARGET.png';
				}

				return 'File:' . $fileName;
			} );

		return $fileNameLookup;
	}

}
