<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use SiteSQLStore;
use Wikibase\Lib\Serializers\EntitySerializationOptions;
use Wikibase\Lib\Serializers\SiteLinkSerializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\EntityIdFormatter;

/**
 * @covers Wikibase\Lib\Serializers\SiteLinkSerializer
 *
 * @since 0.4
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

	public function validProvider() {
		$validArgs = array();
		$idFormatter = $this->getIdFormatter();

		$options = new EntitySerializationOptions( $idFormatter );
		$options->setIndexTags( false );
		$options->addProp( "sitelinks/badges" );
		$siteLinks = array(
			new SimpleSiteLink( "enwiki", "Rome", array( new ItemId( "Q42" ) ) ),
			new SimpleSiteLink( "dewiki", "Rom" ),
			new SimpleSiteLink( "itwiki", "Roma", array( new ItemId( "Q149" ) ) ),
		);
		$expectedSerialization = array(
			"enwiki" => array( "site" => "enwiki", "title" => "Rome", "badges" => array( "Q42" ) ),
			"dewiki" => array( "site" => "dewiki", "title" => "Rom", "badges" => array() ),
			"itwiki" => array( "site" => "itwiki", "title" => "Roma", "badges" => array( "Q149" ) ),
		);
		$validArgs[] = array( $siteLinks, $options, $expectedSerialization );

		$options = new EntitySerializationOptions( $idFormatter );
		$options->setIndexTags( false );
		$options->addProp( "sitelinks/removed" );
		$siteLinks = array(
				new SimpleSiteLink( "enwiki", "" ),
				new SimpleSiteLink( "dewiki", "" ),
				new SimpleSiteLink( "itwiki", "" ),
		);
		$expectedSerialization = array(
				"enwiki" => array( "site" => "enwiki", "title" => "", "removed" => "" ),
				"dewiki" => array( "site" => "dewiki", "title" => "", "removed" => "" ),
				"itwiki" => array( "site" => "itwiki", "title" => "", "removed" => "" ),
		);
		$validArgs[] = array( $siteLinks, $options, $expectedSerialization );

		$options = new EntitySerializationOptions( $idFormatter );
		$options->setIndexTags( true );
		$options->addProp( "sitelinks/badges" );
		$siteLinks = array(
			new SimpleSiteLink( "enwiki", "Rome", array( new ItemId( "Q149" ), new ItemId( "Q49" ) ) ),
			new SimpleSiteLink( "dewiki", "Rom", array( new ItemId( "Q42" ) ) ),
			new SimpleSiteLink( "itwiki", "Roma" ),
		);
		$expectedSerialization = array(
			array( "site" => "enwiki", "title" => "Rome", "badges" => array( "Q149", "Q49", "_element" => "badge" ) ),
			array( "site" => "dewiki", "title" => "Rom", "badges" => array( "Q42", "_element" => "badge" ) ),
			array( "site" => "itwiki", "title" => "Roma", "badges" => array( "_element" => "badge" ) ),
			"_element" => "sitelink",
		);
		$validArgs[] = array( $siteLinks, $options, $expectedSerialization );

		// no badges prop
		$options = new EntitySerializationOptions( $idFormatter );
		$options->setIndexTags( true );
		$siteLinks = array(
			new SimpleSiteLink( "enwiki", "Rome", array( new ItemId( "Q149" ), new ItemId( "Q49" ) ) ),
			new SimpleSiteLink( "dewiki", "Rom", array( new ItemId( "Q42" ) ) ),
			new SimpleSiteLink( "itwiki", "Roma" ),
		);
		$expectedSerialization = array(
			array( "site" => "enwiki", "title" => "Rome" ),
			array( "site" => "dewiki", "title" => "Rom" ),
			array( "site" => "itwiki", "title" => "Roma" ),
			"_element" => "sitelink",
		);
		$validArgs[] = array( $siteLinks, $options, $expectedSerialization );

		return $validArgs;
	}

	/**
	 * @dataProvider validProvider
	 */
	public function testGetSerialized( $siteLinks, $options, $expectedSerialization ) {
		$siteStore = SiteSQLStore::newInstance();
		$siteStore->reset();
		$siteLinkSerializer = new SiteLinkSerializer( $options, $siteStore );
		$serializedSiteLinks = $siteLinkSerializer->getSerialized( $siteLinks );

		$this->assertEquals( $expectedSerialization, $serializedSiteLinks );
	}

	public function invalidProvider() {
		$invalidArgs = array();

		$invalidArgs[] = array( 'foo' );
		$invalidArgs[] = array( 42 );

		return $invalidArgs;
	}

	/**
	 * @dataProvider invalidProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidGetSerialized( $sitelinks ) {
		$options = new EntitySerializationOptions( $this->getIdFormatter() );
		$siteStore = SiteSQLStore::newInstance();
		$siteLinkSerializer = new SiteLinkSerializer( $options, $siteStore );
		$siteLinkSerializer->getSerialized( $sitelinks );
	}

	protected function getIdFormatter() {
		$formatterOptions = new FormatterOptions();
		return new EntityIdFormatter( $formatterOptions );
	}
}
