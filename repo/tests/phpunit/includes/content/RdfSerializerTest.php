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
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseProperty
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
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

	protected static $uriBase = 'http://acme.test';

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
		static $entities = array();

		if ( !empty( $entities ) ) {
			return $entities;
		}

		$entity = Item::newEmpty();
		$entities['empty'] = $entity;


		$entity = Item::newEmpty();
		$entities['terms'] = $entity;

		$entity->setLabel( 'en', 'Berlin' );
		$entity->setLabel( 'ru', 'Берлин' );

		$entity->setDescription( 'en', 'German city' );
		$entity->setDescription( 'ru', 'столица и одновременно земля Германии' );

		$entity->addAliases( 'en', array( 'Berlin, Germany', 'Land Berlin' ) );
		$entity->addAliases( 'ru', array( 'Berlin' ) );

		// not yet supported
		/*
		$entity = Item::newEmpty();
		$entities['links'] = $entity;

		$entity->setLabel( 'en', 'Berlin' );

		$entity->addSiteLink( new SiteLink( MediaWikiSite::newFromGlobalId( 'enwiki' ), 'Berlin' ) );
		$entity->addSiteLink( new SiteLink( MediaWikiSite::newFromGlobalId( 'dewiki' ), 'Berlin' ) );
		$entity->addSiteLink( new SiteLink( MediaWikiSite::newFromGlobalId( 'ruwiki' ), 'Берлин' ) );
		*/

		$i = 1;
		foreach ( $entities as $entity ) {
			$entity->setId( new EntityId( Item::ENTITY_TYPE, $i++ ) );
		}

		return $entities;
	}

	/**
	 * @param \Wikibase\EntityId $entityId
	 * @param array $properties
	 *
	 * @return \EasyRdf_Graph
	 */
	protected static function makeEntityGraph( EntityId $entityId, $properties ) {
		$graph = new \EasyRdf_Graph();

		$entityUri = 'wikibase_id:' . ucfirst( $entityId->getPrefixedId() );
		$entityResource = $graph->resource( $entityUri );

		/* @var \EasyRdf_Resource $entityResource */
		foreach ( $properties as $prop => $values ) {
			if ( is_scalar( $values ) ) {
				$values = array( $values );
			}

			foreach ( $values as $val ) {
				$entityResource->add( $prop, $val );
			}
		}

		return $graph;
	}

	/**
	 * @return \EasyRdf_Graph[]
	 */
	protected static function getTestGraphs() {
		static $graphs = array();

		if ( !empty( $graphs ) ) {
			return $graphs;
		}

		$serializer = self::newRdfSerializer( 'rdf' ); // dummy

		foreach ( $serializer->getNamespaces() as $gname => $uri ) {
			EasyRdf_Namespace::set( $gname, $uri );
		}

		$entities = self::getTestEntities();

		$graphs['empty'] = self::makeEntityGraph(
			$entities['empty']->getId(),
			array()
		);

		$graphs['terms'] = self::makeEntityGraph(
			$entities['terms']->getId(),
			array(
				'rdfs:label' => array(
					new \EasyRdf_Literal( 'Berlin', 'en' ),
					new \EasyRdf_Literal( 'Берлин', 'ru' )
				),
				'rdfs:comment' => array(
					new \EasyRdf_Literal( 'German city', 'en' ),
					new \EasyRdf_Literal( 'столица и одновременно земля Германии', 'ru' )
				),
				'wikibase_ontology:knownAs' => array(
					new \EasyRdf_Literal( 'Berlin, Germany', 'en' ),
					new \EasyRdf_Literal( 'Land Berlin', 'en' ),
					new \EasyRdf_Literal( 'Berlin', 'ru' )
				),
			)
		);

		// not yet supportted
		/*
		$graphs['links'] = self::makeEntityGraph(
			$entities['links']->getId(),
			array(
				//'rdfs:label', new \EasyRdf_Literal( '', '' )
			)
		);
		*/

		return $graphs;
	}

	protected static function getTestDataPatterns() {
		static $patterns = array();

		if ( !empty( $patterns ) ) {
			return $patterns;
		}

		$patterns['empty']['rdf'] = '!<rdf:RDF.*</rdf:RDF>!s';
		$patterns['empty']['n3']  = '!!s';

		$patterns['terms']['rdf'] = '!<rdf:RDF.*'
			. '<rdf:Description.*rdf:about="wikibase_id:Q2".*'
			. '<rdfs:label xml:lang="en">Berlin</rdfs:label>.*'
			. '<rdfs:comment xml:lang="en">German city</rdfs:comment>.*'
			. '<wikibase_ontology:knownAs xml:lang="en">Berlin, Germany</wikibase_ontology:knownAs>.*'
			. '</rdf:RDF>!s';
		$patterns['terms']['n3']  = '!<wikibase_id:Q2>.*'
			. 'rdfs:label +"Berlin"@en,.*'
			. 'rdfs:comment +"German city"@en,.*'
			. 'wikibase_ontology:knownAs +"Berlin, Germany"@en,.*'
			. '!s';

		// links not yet supported
		/*
		$patterns['links']['rdf'] = '!xxx!s';
		$patterns['links']['n3']  = '!xxx!s';
		*/

		return $patterns;
	}


	protected static function newRdfSerializer( $formatName ) {
		$format = RdfSerializer::getFormat( $formatName );

		$dataTypes = new DataTypeFactory( self::$dataTypes );
		$idSerializer = new EntityIdFormatter( new FormatterOptions( array(
			EntityIdFormatter::OPT_PREFIX_MAP => array(
				Item::ENTITY_TYPE => 'q',
				Property::ENTITY_TYPE => 'p',
			)
		) ) );

		$mockRepo = new MockRepository();

		foreach( self::getTestEntities() as $entity ) {
			$mockRepo->putEntity( $entity );
		}

		return new RdfSerializer(
			$format,
			self::$uriBase,
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

	public static function provideBuildGraphForEntity() {
		$entities = self::getTestEntities();
		$graphs = self::getTestGraphs();

		$cases = array();

		foreach ( $entities as $name => $entity ) {
			$cases[] = array(
				$entity,
				$graphs[$name],
			);
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
				$this->assertArrayEquals( $expectedValues, $actualValues );
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

		return $cases;
	}

	/**
	 * @dataProvider provideSerializeRdf
	 */
	public function testSerializeRdf( \EasyRdf_Graph $graph, $format, $regex ) {
		$serializer = self::newRdfSerializer( $format );

		$data = $serializer->serializeRdf( $graph );
		$this->assertRegExp( $regex, $data );
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
	public function TestSerializeEntity( Entity $entity, $format, $regex ) {
		$serializer = self::newRdfSerializer( $format );

		$data = $serializer->serializeEntity( $entity );
		$this->assertRegExp( $regex, $data );
	}

}
