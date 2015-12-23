<?php

namespace Wikibase\Test\Repo\Validators;

use HashBagOStuff;
use PHPUnit_Framework_MockObject_Matcher_Invocation;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;

/**
 * @covers Wikibase\Repo\Validators\CachingCommonsMediaFileNameLookupTest
 *
 * @license GPL 2+
 *
 * @group WikibaseRepo
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Marius Hoch
 */
class CachingCommonsMediaFileNameLookupTest extends \PHPUnit_Framework_TestCase {

	public function testNormalize() {
		$lookup = new CachingCommonsMediaFileNameLookup(
			$this->getMediaWikiPageNameNormalizer( $this->once() ),
			new HashBagOStuff()
		);

		// Two lookups only cause one API call.
		$this->assertSame(
			'Foo.bar',
			$lookup->normalize( 'Foo.bar' )
		);
		$this->assertSame(
			'Foo.bar',
			$lookup->normalize( 'Foo.bar' )
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
			$lookup->normalize( 'Foo.bar' )
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
			$lookup->normalize( 'REDIRECT.cat' )
		);
		$this->assertSame(
			'TARGET.png',
			$lookup->normalize( 'TARGET.png' )
		);
		$this->assertSame(
			'TARGET.png',
			$lookup->normalize( 'REDIRECT.cat' )
		);
	}

	private function getMediaWikiPageNameNormalizer(
		PHPUnit_Framework_MockObject_Matcher_Invocation $matcher
	) {
		$fileNameLookup = $this->getMockBuilder( 'MediaWiki\Site\MediaWikiPageNameNormalizer' )
			->disableOriginalConstructor()
			->getMock();

		$fileNameLookup->expects( $matcher )
			->method( 'normalizePageName' )
			->will( $this->returnCallback( function( $fileName ) {
				if ( strpos( $fileName, 'NOT-FOUND' ) !== false ) {
					return false;
				}

				if ( strpos( $fileName, 'REDIRECT' ) !== false ) {
					return 'File:TARGET.png';
				}

				return 'File:' . $fileName;
			} ) );

		return $fileNameLookup;
	}

}
