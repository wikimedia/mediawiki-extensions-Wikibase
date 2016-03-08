<?php

namespace Wikibase\Test;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Repo\LinkedData\EntityDataUriManager;

/**
 * @covers Wikibase\Repo\LinkedData\EntityDataUriManager
 *
 * @group Database
 * ^--- just because Title is a mess
 *
 * @group Wikibase
 * @group WikibaseEntityData
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityDataUriManagerTest extends \MediaWikiTestCase {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	protected function setUp() {
		parent::setUp();

		$this->idParser = new BasicEntityIdParser();
	}

	protected function makeUriManager() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::newFromText( $id->getEntityType() . ':' . $id->getSerialization() );
			} ) );

		$title = Title::newFromText( "Special:EntityDataUriManagerTest" );

		$extensions = array(
			'text' => 'txt',
			'rdfxml' => 'rdf',
		);

		$uriManager = new EntityDataUriManager(
			$title,
			$extensions,
			$titleLookup
		);

		return $uriManager;
	}

	public function provideGetExtension() {
		return array(
			array( 'text', 'txt' ),
			array( 'rdfxml', 'rdf' ),
			array( 'txt', null ),
			array( 'TEXT', null ),
		);
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
		return array(
			array( 'txt', 'text' ),
			array( 'text', 'text' ),
			array( 'TEXT', 'text' ),
			array( 'TXT', 'text' ),
			array( 'xyz', null ),
		);
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
		return array(
			array( '', array( '', '' ) ),
			array( 'foo', array( 'foo', '' ) ),
			array( 'foo.bar', array( 'foo', 'bar' ) ),
		);
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
		return array(
			array( 'Q12', '', 'Q12' ),
			array( 'q12', null, 'Q12' ),
			array( 'Q12', 'text', 'Q12.txt' ),
		);
	}

	/**
	 * @dataProvider provideGetDocName
	 */
	public function testGetDocName( $id, $format, $expected ) {
		$id = $this->idParser->parse( $id );

		$uriManager = $this->makeUriManager();

		$actual = $uriManager->getDocName( $id, $format );
		$this->assertEquals( $expected, $actual );
	}

	public function provideGetDocTitle() {
		$title = Title::newFromText( "Special:EntityDataUriManagerTest" );
		$base = $title->getPrefixedText();

		return array(
			array( 'Q12', '', "$base/Q12" ),
			array( 'q12', null, "$base/Q12" ),
			array( 'Q12', 'text', "$base/Q12.txt" ),
		);
	}

	/**
	 * @dataProvider provideGetDocTitle
	 */
	public function testGetDocTitle( $id, $format, $expected ) {
		$id = $this->idParser->parse( $id );

		$uriManager = $this->makeUriManager();

		$actual = $uriManager->getDocTitle( $id, $format );
		$this->assertEquals( $expected, $actual->getPrefixedText() );
	}

	public function provideGetDocUrl() {
		return array(
			array( 'Q12', '', 0, '!Q12$!' ),
			array( 'q12', null, 0, '!Q12$!' ),
			array( 'q12', null, 2, '!Q12.*oldid=2$!' ),
			array( 'Q12', 'text', 0, '!Q12\.txt$!' ),
			array( 'Q12', 'text', 2, '!Q12\.txt.*oldid=2$!' ),
		);
	}

	/**
	 * @dataProvider provideGetDocUrl
	 */
	public function testGetDocUrl( $id, $format, $revision, $expectedExp ) {
		$id = $this->idParser->parse( $id );

		$uriManager = $this->makeUriManager();

		$actual = $uriManager->getDocUrl( $id, $format, $revision );
		$this->assertRegExp( $expectedExp, $actual );
	}

	public function provideGetCacheableUrls() {
		$title = Title::newFromText( "Special:EntityDataUriManagerTest" );
		$base = $title->getInternalURL();

		return array(
			array( 'Q12', array(), array(
				"$base/Q12.txt",
				"$base/Q12.rdf",
			) ),
			array( 'Q13',
				array( "flavor" => array( "vanilla", "pistachio", "chocolate" ) ),
				array(
				"$base/Q13.txt",
				"$base/Q13.txt?flavor=vanilla",
				"$base/Q13.txt?flavor=pistachio",
				"$base/Q13.txt?flavor=chocolate",
				"$base/Q13.rdf",
				"$base/Q13.rdf?flavor=vanilla",
				"$base/Q13.rdf?flavor=pistachio",
				"$base/Q13.rdf?flavor=chocolate",
				) ),
			array( 'Q14',
				array( "flavor" => array( "vanilla", "chocolate" ), "toppings" => array( "candy", "fruit" ) ),
				array(
				"$base/Q14.txt",
				"$base/Q14.txt?flavor=vanilla",
				"$base/Q14.txt?flavor=chocolate",
				"$base/Q14.txt?toppings=candy",
				"$base/Q14.txt?flavor=vanilla&toppings=candy",
				"$base/Q14.txt?flavor=chocolate&toppings=candy",
				"$base/Q14.txt?toppings=fruit",
				"$base/Q14.txt?flavor=vanilla&toppings=fruit",
				"$base/Q14.txt?flavor=chocolate&toppings=fruit",
				"$base/Q14.rdf",
				"$base/Q14.rdf?flavor=vanilla",
				"$base/Q14.rdf?flavor=chocolate",
				"$base/Q14.rdf?toppings=candy",
				"$base/Q14.rdf?flavor=vanilla&toppings=candy",
				"$base/Q14.rdf?flavor=chocolate&toppings=candy",
				"$base/Q14.rdf?toppings=fruit",
				"$base/Q14.rdf?flavor=vanilla&toppings=fruit",
				"$base/Q14.rdf?flavor=chocolate&toppings=fruit",
				) ),
		);
	}

	/**
	 * @dataProvider provideGetCacheableUrls
	 */
	public function testGetCacheableUrls( $id, $query, $expected ) {
		$id = $this->idParser->parse( $id );

		$uriManager = $this->makeUriManager();

		$actual = $uriManager->getCacheableUrls( $id, $query );
		$this->assertEquals( $expected, $actual );
	}

}
