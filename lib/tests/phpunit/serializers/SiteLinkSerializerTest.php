<?php

namespace Wikibase\Test;

use Wikibase\Lib\Serializers\EntitySerializationOptions;
use Wikibase\Lib\Serializers\SiteLinkSerializer;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\DataModel\SimpleSiteLink;

/**
 * @covers Wikibase\Lib\Serializers\SiteLinkSerializer
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
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseSerialization
 * @group WikibaseSiteLinkSerializer
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SiteLinkSerializerTest extends \PHPUnit_Framework_TestCase {

	public function validProvider() {
		$validArgs = array();
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();

		$options = new EntitySerializationOptions( $idFormatter );
		$options->setUseKeys( true );
		$siteLinks = array(
			new SimpleSiteLink( "enwiki", "Rome" ),
			new SimpleSiteLink( "dewiki", "Rom" ),
			new SimpleSiteLink( "itwiki", "Roma" ),
		);
		$expectedSerialization = array(
			"enwiki" => array( "site" => "enwiki", "title" => "Rome" ),
			"dewiki" => array( "site" => "dewiki", "title" => "Rom" ),
			"itwiki" => array( "site" => "itwiki", "title" => "Roma" ),
		);
		$validArgs[] = array( $siteLinks, $options, $expectedSerialization );

		$options = new EntitySerializationOptions( $idFormatter );
		$options->setUseKeys( true );
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
		$options->setUseKeys( false );
		$siteLinks = array(
			new SimpleSiteLink( "enwiki", "Rome" ),
			new SimpleSiteLink( "dewiki", "Rom" ),
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
		$siteStore = \SiteSQLStore::newInstance();
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
		$options = new EntitySerializationOptions( WikibaseRepo::getDefaultInstance()->getIdFormatter() );
		$siteStore = \SiteSQLStore::newInstance();
		$siteLinkSerializer = new SiteLinkSerializer( $options, $siteStore );
		$serializedSiteLinks = $siteLinkSerializer->getSerialized( $sitelinks );
	}
}
