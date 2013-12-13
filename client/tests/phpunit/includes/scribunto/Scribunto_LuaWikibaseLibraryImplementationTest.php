<?php

namespace Wikibase\Test;

use Scribunto_LuaWikibaseLibraryImplementation;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\WikibaseClient;
use \Language;
use Wikibase\Lib\SnakFormatter;
use \Site;
use \Wikibase\DirectSqlStore;
use \MediaWikiSite;

/**
 * @covers Wikibase\Scribunto_LuaWikibaseLibraryImplementation
 *
 * @since 0.4
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
		$language = new Language( "en" );
		$siteLinkLookup = $this->getMockBuilder( '\Wikibase\SiteLinkTable' )
			->disableOriginalConstructor()
			->getMock();
		$formatterOptions = new FormatterOptions();
		return new Scribunto_LuaWikibaseLibraryImplementation(
			WikibaseClient::getDefaultInstance()->getEntityIdParser(), // EntityIdParser
			$entityLookup,
			WikibaseClient::getDefaultInstance()->getEntityIdFormatter(), // EntityIdFormatter
			WikibaseClient::getDefaultInstance()->getSnakFormatterFactory()->getSnakFormatter( SnakFormatter::FORMAT_WIKI, $formatterOptions ),
			$siteLinkLookup, // SiteLinkLookup
			$language, // language
			"enwiki" // siteId
		);
	}

	/**
	 * @dataProvider provideEntity
	 */
	public function testGetEntity( $entity ) {
		$entityArr = $this->getWikibaseLibraryImplementation()->getEntity( $entity );
		$this->assertInternalType( 'array', $entityArr );
	}

	public function provideEntity() {
		return array( array( 'q42' ), array( 'q23' ) );
	}

	/**
	 * @dataProvider provideTitle
	 */
	public function testGetEntityId( $title ) {
		$id = $this->getWikibaseLibraryImplementation()->getEntityId( $title );
		$this->assertInternalType( 'array', $id );
	}

	public function provideTitle() {
		return array( array( 'Gold' ), array( 'Silver' ) );
	}

	/**
	 * @dataProvider provideEntityIdAndPropertyId
	 */
	public function testRenderForEntityId ( $entityId, $propertyId ) {
		$status = $this->getWikibaseLibraryImplementation()->renderForEntityId( $entityId, $propertyId );
		$this->assertTrue( $status->isOK() );
		$text = $status->getValue();
		$this->assertInternalType( 'string', $text );
	}

	public function provideEntityIdAndPropertyId () {
		return array( array( 'q42', 'p15' ), array( 'q23', 'p17' ) );
	}
}
