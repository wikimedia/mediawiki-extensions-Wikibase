<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use SiteSQLStore;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SiteLinkSerializer;

/**
 * @covers Wikibase\Lib\Serializers\SiteLinkSerializer
 *
 * @group Wikibase
 * @group WikibaseSerialization
 * @group WikibaseSiteLinkSerializer
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Michał Łazowik
 */
class SiteLinkSerializerTest extends \PHPUnit_Framework_TestCase {

	public function validSiteLinksProvider() {
		$validArgs = array();

		$options = new SerializationOptions();
		$options->setIndexTags( false );
		$siteLinks = array(
			new SiteLink( "enwiki", "Rome", array( new ItemId( "Q42" ) ) ),
			new SiteLink( "dewiki", "Rom" ),
			new SiteLink( "itwiki", "Roma", array( new ItemId( "Q149" ) ) ),
		);
		$expectedSerialization = array(
			"enwiki" => array( "site" => "enwiki", "title" => "Rome", "badges" => array( "Q42" ) ),
			"dewiki" => array( "site" => "dewiki", "title" => "Rom", "badges" => array() ),
			"itwiki" => array( "site" => "itwiki", "title" => "Roma", "badges" => array( "Q149" ) ),
		);
		$validArgs[] = array( $siteLinks, $options, $expectedSerialization );

		$options = new SerializationOptions();
		$options->setIndexTags( false );
		$options->addToOption( EntitySerializer::OPT_PARTS, "sitelinks/removed" );
		$siteLinks = array(
				new SiteLink( "enwiki", "", array( new ItemId( "Q42" ) ) ),
				new SiteLink( "dewiki", "", array() ),
				new SiteLink( "itwiki", "" ),
		);
		$expectedSerialization = array(
				"enwiki" => array( "site" => "enwiki", "title" => "", "removed" => "" ),
				"dewiki" => array( "site" => "dewiki", "title" => "", "removed" => "" ),
				"itwiki" => array( "site" => "itwiki", "title" => "", "removed" => "" ),
		);
		$validArgs[] = array( $siteLinks, $options, $expectedSerialization );

		$options = new SerializationOptions();
		$options->setIndexTags( true );
		$siteLinks = array(
			new SiteLink( "enwiki", "Rome", array( new ItemId( "Q149" ), new ItemId( "Q49" ) ) ),
			new SiteLink( "dewiki", "Rom", array( new ItemId( "Q42" ) ) ),
			new SiteLink( "itwiki", "Roma" ),
		);
		$expectedSerialization = array(
			array( "site" => "enwiki", "title" => "Rome", "badges" => array( "Q149", "Q49", "_element" => "badge" ) ),
			array( "site" => "dewiki", "title" => "Rom", "badges" => array( "Q42", "_element" => "badge" ) ),
			array( "site" => "itwiki", "title" => "Roma", "badges" => array( "_element" => "badge" ) ),
			"_element" => "sitelink",
		);
		$validArgs[] = array( $siteLinks, $options, $expectedSerialization );

		return $validArgs;
	}

	/**
	 * @dataProvider validSiteLinksProvider
	 */
	public function testGetSerialized( $siteLinks, $options, $expectedSerialization ) {
		$siteStore = SiteSQLStore::newInstance();
		$siteStore->reset();
		$siteLinkSerializer = new SiteLinkSerializer( $options, $siteStore );
		$serializedSiteLinks = $siteLinkSerializer->getSerialized( $siteLinks );

		$this->assertEquals( $expectedSerialization, $serializedSiteLinks );
	}

	public function invalidSiteLinksProvider() {
		return array(
			array( 'foo' ),
			array( 42 ),
		);
	}

	/**
	 * @dataProvider invalidSiteLinksProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidGetSerialized( $siteLinks ) {
		$options = new SerializationOptions();
		$siteStore = SiteSQLStore::newInstance();
		$siteLinkSerializer = new SiteLinkSerializer( $options, $siteStore );
		$siteLinkSerializer->getSerialized( $siteLinks );
	}

	/**
	 * @dataProvider newFromSerializationProvider
	 */
	public function testNewFromSerialization( $expected, $serialized ) {
		// todo inject / mock
		$siteStore = SiteSQLStore::newInstance();
		$options = new SerializationOptions();
		$siteLinkSerializer = new SiteLinkSerializer( $options, $siteStore );

		$siteLinks = $siteLinkSerializer->newFromSerialization( $serialized );
		$this->assertEquals( $expected, $siteLinks );
	}

	public function newFromSerializationProvider() {
		$siteLinks = array();

		$badges = array(
			new ItemId( 'Q944' ),
			new ItemId( 'Q1004' )
		);

		$siteLinks[] = new SiteLink( 'enwiki', 'Cat' );
		$siteLinks[] = new SiteLink( 'dewiki', 'Katze', $badges );

		// todo inject / mock
		$siteStore = SiteSQLStore::newInstance();
		$options = new SerializationOptions();

		$siteLinkSerializer = new SiteLinkSerializer( $options, $siteStore );
		$serialized = $siteLinkSerializer->getSerialized( $siteLinks );

		return array(
			array( $siteLinks, $serialized )
		);
	}

}
