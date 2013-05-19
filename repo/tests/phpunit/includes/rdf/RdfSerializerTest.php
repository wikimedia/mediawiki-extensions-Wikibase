<?php

namespace Wikibase\Test;
use DataTypes\DataTypeFactory;
use EasyRdf_Namespace;
use MediaWikiSite;
use ValueFormatters\FormatterOptions;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Property;
use Wikibase\RdfSerializer;
use Wikibase\SiteLink;

/**
 * Tests for the Wikibase\RdfSerializer class.
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
 * @ingroup RDF
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RdfSerializerTest extends \MediaWikiTestCase {

	protected static $formats = array(
		'rdf',
		'application/rdf+xml',
		'n3',
		'text/n3',
		'nt',
		'ntriples',
		'turtle',
	);

	protected static $dataTypes = array(
		'commonsMedia' => array(
			'datavalue' => 'string',
		),
		'string' => array(
			'datavalue' => 'string',
		),
		'geo-coordinate' => array(
			'datavalue' => 'geocoordinate',
		),
		'quantity' => array(
			'datavalue' => 'quantity',
		),
		'monolingual-text' => array(
			'datavalue' => 'monolingualtext',
		),
		'multilingual-text' => array(
			'datavalue' => 'multilingualtext',
		),
		'time' => array(
			'datavalue' => 'time',
		),
	);

	public function setUp() {
		parent::setUp();

		if ( !RdfSerializer::isSupported() ) {
			$this->markTestSkipped( "RDF library not found" );
		}
	}

	/**
	 * @return Entity[]
	 */
	protected static function getTestEntities() {
		return RdfBuilderTest::getTestEntities();
	}

	/**
	 * @return \EasyRdf_Graph[]
	 */
	protected static function getTestGraphs() {
		return RdfBuilderTest::getTestGraphs();
	}

	protected static function getTestDataPatterns() {
		static $patterns = array();

		if ( !empty( $patterns ) ) {
			return $patterns;
		}

		$patterns['empty']['rdf'] = array( '!<rdf:RDF.*</rdf:RDF>!s' );
		$patterns['empty']['n3']  = array( '!!s' );

		$patterns['terms']['rdf'] = array(
			'!<rdf:RDF.*</rdf:RDF>!s',
			'!<wikibase:Item.*rdf:about=".*?entity/Q2"!s',
			'!<rdfs:label xml:lang="en">Berlin</rdfs:label>!s',
			'!<skos:prefLabel xml:lang="en">Berlin</skos:prefLabel>!s',
			'!<schema:name xml:lang="en">Berlin</schema:name>!s',
			'!<schema:description xml:lang="en">German city</schema:description>!s',
			'!<skos:altLabel xml:lang="en">Berlin, Germany</skos:altLabel>!s',
		);

		$patterns['terms']['n3']  = array(
			'!entity:Q2!s',
			'!rdfs:label +"Berlin"@en,!s',
			'!skos:prefLabel +"Berlin"@en,!s',
			'!schema:name +"Berlin"@en,!s',
			'!schema:description +"German city"@en,!s',
			'!skos:altLabel +"Berlin, Germany"@en,!s',
		);

		// TODO: check meta
		// TODO: test links
		// TODO: test data values

		return $patterns;
	}


	protected static function newRdfSerializer( $formatName ) {
		$format = RdfSerializer::getFormat( $formatName );

		$dataTypes = new DataTypeFactory( self::$dataTypes );
		$idSerializer = new EntityIdFormatter( new FormatterOptions( array(
			EntityIdFormatter::OPT_PREFIX_MAP => array(
				Item::ENTITY_TYPE => 'Q',
				Property::ENTITY_TYPE => 'P',
			)
		) ) );

		$mockRepo = new MockRepository();

		foreach( self::getTestEntities() as $entity ) {
			$mockRepo->putEntity( $entity );
		}

		return new RdfSerializer(
			$format,
			RdfBuilderTest::URI_BASE,
			RdfBuilderTest::URI_DATA,
			$mockRepo,
			$dataTypes,
			$idSerializer
		);
	}

	public static function provideGetFormat() {
		return array_map(
			function ( $format ) {
				return array( $format );
			},
			self::$formats
		);
	}

	/**
	 * @dataProvider provideGetFormat
	 */
	public function testGetFormat( $name ) {
		$format = RdfSerializer::getFormat( $name );

		$this->assertNotNull( $format, $name );
	}

	public function provideBuildGraphForEntity() {
		$entities = self::getTestEntities();
		$graphs = self::getTestGraphs();

		$cases = array();

		foreach ( $entities as $name => $entity ) {
			if ( array_key_exists( $name, $graphs ) ) {
				$cases[] = array(
					$entity,
					$graphs[$name],
				);
			}
		}

		if ( count( $cases ) == 0 ) {
			//test should be skipped
			return null;
		}

		return $cases;
	}

	/**
	 * @dataProvider provideBuildGraphForEntity
	 */
	public function testBuildGraphForEntity( Entity $entity, \EasyRdf_Graph $expectedGraph ) {
		$serializer = self::newRdfSerializer( 'rdf' );

		$graph = $serializer->buildGraphForEntity( $entity );
		//TODO: meta-info from Revision

		foreach ( $expectedGraph->resources() as $rc ) {
			foreach ( $expectedGraph->properties( $rc ) as $prop ) {
				$expectedValues = $expectedGraph->all( $rc, $prop );
				$actualValues = $graph->all( $rc, $prop );

				$this->assertArrayEquals(
					RdfBuilderTest::rdf2strings( $expectedValues ),
					RdfBuilderTest::rdf2strings( $actualValues )
				);
			}
		}
	}

	public static function provideSerializeRdf() {
		$graphs = self::getTestGraphs();
		$patterns = self::getTestDataPatterns();

		$cases = array();

		foreach ( $graphs as $name => $graph ) {
			foreach ( self::$formats as $format ) {
				if ( isset( $patterns[$name][$format] ) ) {
					$cases[] = array(
						$graph,
						$format,
						$patterns[$name][$format],
					);
				}
			}
		}

		if ( count( $cases ) == 0 ) {
			//test should be skipped
			return null;
		}

		return $cases;
	}

	/**
	 * @dataProvider provideSerializeRdf
	 */
	public function testSerializeRdf( \EasyRdf_Graph $graph, $format, $regexes ) {
		$serializer = self::newRdfSerializer( $format );

		$data = $serializer->serializeRdf( $graph );

		foreach ( $regexes as $regex ) {
			$this->assertRegExp( $regex, $data );
		}
	}

	public static function provideSerializeEntity() {
		$entities = self::getTestEntities();
		$patterns = self::getTestDataPatterns();

		$cases = array();

		foreach ( $entities as $name => $entity ) {
			foreach ( self::$formats as $format ) {
				if ( isset( $patterns[$name][$format] ) ) {
					$cases[] = array(
						$entity,
						$format,
						$patterns[$name][$format],
					);
				}
			}
		}

		return $cases;
	}

	/**
	 * @dataProvider provideSerializeEntity
	 */
	public function testSerializeEntity( Entity $entity, $format, $regexes ) {
		$serializer = self::newRdfSerializer( $format );

		$data = $serializer->serializeEntity( $entity );

		foreach ( $regexes as $regex ) {
			$this->assertRegExp( $regex, $data );
		}
	}

}
