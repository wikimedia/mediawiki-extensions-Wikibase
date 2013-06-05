<?php

namespace Wikibase\Test;

use DataTypes\DataTypeFactory;
use Title;
use ValueFormatters\FormatterOptions;
use ValueParsers\ParserOptions;
use Wikibase\Entity;
use Wikibase\EntityContentFactory;
use Wikibase\EntityDataSerializationService;
use Wikibase\EntityDataUriManager;
use Wikibase\EntityId;
use \Wikibase\Item;
use \Wikibase\ItemContent;
use \Wikibase\EntityDataRequestHandler;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Property;

/**
 * @covers \Wikibase\EntityUriManager
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Database
 * ^--- just because Title is a mess
 *
 * @group Wikibase
 * @group WikibaseEntityData
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityDataUriManagerTest extends \MediaWikiTestCase {

	/**
	 * @var EntityIdFormatter
	 */
	protected $idFormatter;

	/**
	 * @var EntityIdParser
	 */
	protected $idParser;

	public function setUp() {
		parent::setUp();

		$prefixes = array(
			Item::ENTITY_TYPE => 'q',
			Property::ENTITY_TYPE => 'p',
		);

		$this->idFormatter = new EntityIdFormatter( new FormatterOptions( array(
			EntityIdFormatter::OPT_PREFIX_MAP => $prefixes
		) ) );

		$this->idParser = new EntityIdParser( new ParserOptions( array(
			EntityIdFormatter::OPT_PREFIX_MAP => array_flip( $prefixes )
		) ) );

}

	protected function makeUriManager() {
		$contentFactory = new EntityContentFactory(
			$this->idFormatter,
			array(
				CONTENT_MODEL_WIKIBASE_ITEM,
				CONTENT_MODEL_WIKIBASE_PROPERTY
			)
		);

		$title = Title::newFromText( "Special:EntityDataUriManagerTest" );

		$extensions = array(
			'text' => 'txt',
			'rdfxml' => 'rdf',
		);

		$uriManager = new EntityDataUriManager(
			$title,
			$extensions,
			$this->idFormatter,
			$contentFactory
		);

		return $uriManager;
	}

	public static function provideGetExtension() {
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

	public static function provideGetFormatName() {
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

	public static function provideParseDocName() {
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

		$actual = $uriManager->parseDocName( $doc, $expected );
		$this->assertEquals( $expected, $actual );
	}

	public static function provideGetDocName() {
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

	public static function provideGetDocTitle() {
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

	public static function provideGetDocUrl() {
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

	public static function provideGetCacheableUrls() {
		$title = Title::newFromText( "Special:EntityDataUriManagerTest" );
		$base = $title->getFullURL();

		return array(
			array( 'Q12', array(
				"$base/Q12.txt",
				"$base/Q12.rdf",
			) ),
		);
	}

	/**
	 * @dataProvider provideGetCacheableUrls
	 */
	public function testGetCacheableUrls( $id, $expected ) {
		$id = $this->idParser->parse( $id );

		$uriManager = $this->makeUriManager();

		$actual = $uriManager->getCacheableUrls( $id );
		$this->assertEquals( $expected, $actual );
	}
}
