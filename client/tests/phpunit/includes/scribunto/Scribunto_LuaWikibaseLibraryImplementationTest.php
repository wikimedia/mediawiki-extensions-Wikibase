<?php

namespace Wikibase\Test;

use Scribunto_LuaWikibaseLibraryImplementation;
use Wikibase\Client\WikibaseClient;

/**
 * @covers Wikibase\Scribunto_LuaWikibaseLibraryImplementation
 *
 * @since 0.5
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group Scribunto_LuaWikibaseLibraryImplementationTest
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */
class Scribunto_LuaWikibaseLibraryImplementationTest extends \PHPUnit_Framework_TestCase {

	public function getWikibaseLibraryImplementation() {
		$entityLookup = new MockRepository();
		$siteLinkLookup = $this->getMockBuilder( '\Wikibase\SiteLinkLookup' )->disableOriginalConstructor()->getMock();
		return new Scribunto_LuaWikibaseLibraryImplementation(
			WikibaseClient::getDefaultInstance()->getEntityIdParser(), // EntityIdParser
			$entityLookup,
			WikibaseClient::getDefaultInstance()->getEntityIdFormatter(), // EntityIdFormatter
			$siteLinkLookup // SiteLinkLookup
		);
	}

	/**
	 * @dataProvider provideEntity
	 */
	public function testGetEntity( $entity ) {
		$entityArr = $this->getWikibaseLibraryImplementation()->getEntity( $entity );
		$this->assertEquals( is_array( $entityArr ), true );
	}

	public function provideEntity() {
		return array( array( 'q42' ), array( 'q23' ) );
	}

	/**
	 * @dataProvider provideZeroIndexedArray
	 */
	public function testZeroIndexArray ( $array ) {
		$this->getWikibaseLibraryImplementation()->renumber( $array );
		$this->assertEquals( is_array( $array ), true );
		$this->assertEquals( array_key_exists( 0, $array["nyancat"] ), false );
		$this->assertEquals( $array["nyancat"][1], 'nyan' );
		$this->assertEquals( $array["nyancat"][2], 'cat' );
	}

	public function provideZeroIndexedArray() {
		return array( array( array( "nyancat" => array( "0" => "nyan", "1" => "cat" ) ) ) );
	}
}
