<?php

namespace Wikibase\Repo\Tests\LinkedData;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\LinkedData\EntityDataUriManager;

/**
 * @covers Wikibase\Repo\LinkedData\EntityDataUriManager
 *
 * @group Database
 * ^--- just because Title is a mess
 *
 * @group Wikibase
 * @group WikibaseEntityData
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityDataUriManagerTest extends \MediaWikiTestCase {

	protected function makeUriManager() {
		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::newFromText( $id->getEntityType() . ':' . $id->getSerialization() );
			} ) );

		$title = Title::newFromText( "Special:EntityDataUriManagerTest" );

		$extensions = [
			'text' => 'txt',
			'rdfxml' => 'rdf',
		];

		$uriManager = new EntityDataUriManager(
			$title,
			$extensions,
			$titleLookup
		);

		return $uriManager;
	}

	public function provideGetExtension() {
		return [
			[ 'text', 'txt' ],
			[ 'rdfxml', 'rdf' ],
			[ 'txt', null ],
			[ 'TEXT', null ],
		];
	}

	/**
	 * @dataProvider provideGetExtension
	 */
	public function testGetExtension( $format, $expected ) {
		$uriManager = $this->makeUriManager();

		$actual = $uriManager->getExtension( $format );
		$this->assertEquals( $expected, $actual );
	}

	public function provideGetFormatName() {
		return [
			[ 'txt', 'text' ],
			[ 'text', 'text' ],
			[ 'TEXT', 'text' ],
			[ 'TXT', 'text' ],
			[ 'xyz', null ],
		];
	}

	/**
	 * @dataProvider provideGetFormatName
	 */
	public function testGetFormatName( $extension, $expected ) {
		$uriManager = $this->makeUriManager();

		$actual = $uriManager->getFormatName( $extension );
		$this->assertEquals( $expected, $actual );
	}

	public function provideParseDocName() {
		return [
			[ '', [ '', '' ] ],
			[ 'foo', [ 'foo', '' ] ],
			[ 'foo.bar', [ 'foo', 'bar' ] ],
			[ "foo.bar\n", [ "foo\n", 'bar' ] ],
			[ ' foo.bar ', [ ' foo.bar ', '' ] ],
		];
	}

	/**
	 * @dataProvider provideParseDocName
	 */
	public function testParseDocName( $doc, $expected ) {
		$uriManager = $this->makeUriManager();

		$actual = $uriManager->parseDocName( $doc );
		$this->assertEquals( $expected, $actual );
	}

	public function provideGetDocName() {
		return [
			[ 'Q12', '', 'Q12' ],
			[ 'q12', null, 'Q12' ],
			[ 'Q12', 'text', 'Q12.txt' ],
		];
	}

	/**
	 * @dataProvider provideGetDocName
	 */
	public function testGetDocName( $id, $format, $expected ) {
		$id = new ItemId( $id );

		$uriManager = $this->makeUriManager();

		$actual = $uriManager->getDocName( $id, $format );
		$this->assertEquals( $expected, $actual );
	}

	public function provideGetDocTitle() {
		$title = Title::newFromText( "Special:EntityDataUriManagerTest" );
		$base = $title->getPrefixedText();

		return [
			[ 'Q12', '', "$base/Q12" ],
			[ 'q12', null, "$base/Q12" ],
			[ 'Q12', 'text', "$base/Q12.txt" ],
		];
	}

	/**
	 * @dataProvider provideGetDocTitle
	 */
	public function testGetDocTitle( $id, $format, $expected ) {
		$id = new ItemId( $id );

		$uriManager = $this->makeUriManager();

		$actual = $uriManager->getDocTitle( $id, $format );
		$this->assertEquals( $expected, $actual->getPrefixedText() );
	}

	public function provideGetDocUrl() {
		return [
			[ 'Q12', '', 0, '!Q12$!' ],
			[ 'q12', null, 0, '!Q12$!' ],
			[ 'q12', null, 2, '!Q12.*oldid=2$!' ],
			[ 'Q12', 'text', 0, '!Q12\.txt$!' ],
			[ 'Q12', 'text', 2, '!Q12\.txt.*oldid=2$!' ],
		];
	}

	/**
	 * @dataProvider provideGetDocUrl
	 */
	public function testGetDocUrl( $id, $format, $revision, $expectedExp ) {
		$id = new ItemId( $id );

		$uriManager = $this->makeUriManager();

		$actual = $uriManager->getDocUrl( $id, $format, $revision );
		$this->assertRegExp( $expectedExp, $actual );
	}

	public function provideGetCacheableUrls() {
		$title = Title::newFromText( "Special:EntityDataUriManagerTest" );
		$base = $title->getInternalURL();

		return [
			[ 'Q12', [
				"$base/Q12.txt",
				"$base/Q12.rdf",
			] ],
		];
	}

	/**
	 * @dataProvider provideGetCacheableUrls
	 */
	public function testGetCacheableUrls( $id, $expected ) {
		$id = new ItemId( $id );

		$uriManager = $this->makeUriManager();

		$actual = $uriManager->getCacheableUrls( $id );
		$this->assertEquals( $expected, $actual );
	}

}
