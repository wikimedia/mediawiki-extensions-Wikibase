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
use Wikibase\RdfBuilder;

/**
 * Tests for the Wikibase\RdfBuilder class.
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
class RdfBuilderTest extends \MediaWikiTestCase {

	const URI_BASE = 'http://acme.test/';
	const URI_DATA = 'http://data.acme.test/';

	public function setUp() {
		parent::setUp();

		if ( !RdfBuilder::isSupported() ) {
			$this->markTestSkipped( "RDF library not found" );
		}
	}

	/**
	 * @return Entity[]
	 */
	public static function getTestEntities() {
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

		// TODO: test links
		// TODO: test data values

		$i = 1;
		foreach ( $entities as $entity ) {
			$entity->setId( new EntityId( Item::ENTITY_TYPE, $i++ ) );
		}

		return $entities;
	}

	/**
	 * @param \Wikibase\EntityId $entityId
	 * @param array $entityProps
	 * @param array $dataProps
	 *
	 * @return \EasyRdf_Graph
	 */
	protected static function makeEntityGraph( EntityId $entityId, $entityProps, $dataProps ) {
		$graph = new \EasyRdf_Graph();

		$builder = self::newRdfBuilder( 'rdf' ); //XXX: ugh, dummy object

		$entityUri = $builder->getEntityQName( RdfBuilder::NS_ENTITY, $entityId );
		$dataUri = $builder->getDataURL( $entityId );
		$entityResource = $graph->resource( $entityUri );
		$dataResource = $graph->resource( $dataUri );

		self::addProperties( $graph, $entityResource, $entityProps );
		self::addProperties( $graph, $dataResource, $dataProps );

		return $graph;
	}

	/**
	 * @param \EasyRdf_Graph    $graph
	 * @param \EasyRdf_Resource $resources
	 * @param array  $properties
	 */
	protected static function addProperties( \EasyRdf_Graph $graph, \EasyRdf_Resource $resource, $properties ) {
		foreach ( $properties as $prop => $values ) {
			if ( is_scalar( $values ) ) {
				$values = array( $values );
			}

			foreach ( $values as $val ) {
				if ( is_string( $val ) ) {
					$val = $graph->resource( $val );
				}

				$resource->add( $prop, $val );
			}
		}
	}

	/**
	 * @return \EasyRdf_Graph[]|null
	 */
	public static function getTestGraphs() {
		static $graphs = array();

		if ( !empty( $graphs ) ) {
			return $graphs;
		}

		if ( !RdfBuilder::isSupported() ) {
			// test will be skipped anyway
			return array();
		}

		$builder = self::newRdfBuilder( 'rdf' ); //XXX: ugh, dummy object

		foreach ( $builder->getNamespaces() as $gname => $uri ) {
			EasyRdf_Namespace::set( $gname, $uri );
		}

		$entities = self::getTestEntities();

		$graphs['empty'] = self::makeEntityGraph(
			$entities['empty']->getId(),
			array(
				'rdf:type' => $builder->getEntityTypeQName( Item::ENTITY_TYPE ),
			),
			array(
				'rdf:type' => RdfBuilder::NS_SCHEMA_ORG . ':Dataset',
			)
		);

		$graphs['terms'] = self::makeEntityGraph(
			$entities['terms']->getId(),
			array(
				'rdf:type' => $builder->getEntityTypeQName( Item::ENTITY_TYPE ),
				'rdfs:label' => array(
					new \EasyRdf_Literal( 'Berlin', 'en' ),
					new \EasyRdf_Literal( 'Берлин', 'ru' )
				),
				'skos:prefLabel' => array(
					new \EasyRdf_Literal( 'Berlin', 'en' ),
					new \EasyRdf_Literal( 'Берлин', 'ru' )
				),
				'schema:name' => array(
					new \EasyRdf_Literal( 'Berlin', 'en' ),
					new \EasyRdf_Literal( 'Берлин', 'ru' )
				),
				'schema:description' => array(
					new \EasyRdf_Literal( 'German city', 'en' ),
					new \EasyRdf_Literal( 'столица и одновременно земля Германии', 'ru' )
				),
				'skos:altLabel' => array(
					new \EasyRdf_Literal( 'Berlin, Germany', 'en' ),
					new \EasyRdf_Literal( 'Land Berlin', 'en' ),
					new \EasyRdf_Literal( 'Berlin', 'ru' )
				),
			),
			array(
				'rdf:type' => RdfBuilder::NS_SCHEMA_ORG . ':Dataset',
				'schema:about' => $builder->getEntityQName( RdfBuilder::NS_ENTITY, $entities['terms']->getId() ),
				'schema:url' => $builder->getDataURL( $entities['terms']->getId() ),
			)
		);

		// TODO: test links
		// TODO: test data values

		return $graphs;
	}

	/**
	 * @return RdfBuilder
	 */
	protected static function newRdfBuilder() {
		$idFormatter = new EntityIdFormatter( new FormatterOptions( array(
			EntityIdFormatter::OPT_PREFIX_MAP => array(
				Item::ENTITY_TYPE => 'Q',
				Property::ENTITY_TYPE => 'P',
			)
		) ) );

		return new RdfBuilder(
			self::URI_BASE,
			$idFormatter
		);
	}

	public static function provideAddEntity() {
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
	 * @dataProvider provideAddEntity
	 */
	public function testAddEntity( Entity $entity, \EasyRdf_Graph $expectedGraph ) {
		$builder = $this->newRdfBuilder();

		//TODO: also test revision meta data
		$builder->addEntity( $entity );
		$graph = $builder->getGraph();

		foreach ( $expectedGraph->resources() as $rc ) {
			$props = $expectedGraph->properties( $rc );

			foreach ( $props as $prop ) {
				$expectedValues = $expectedGraph->all( $rc, $prop );
				$actualValues = $graph->all( $rc, $prop );

				$this->assertArrayEquals(
					self::rdf2strings( $expectedValues ),
					self::rdf2strings( $actualValues )
				);
			}
		}
	}

	public static function rdf2strings( array $data ) {
		$strings = array();

		foreach ( $data as $obj ) {
			$strings[] = self::rdf2string( $obj );
		}

		return $strings;
	}

	public static function rdf2string( $obj ) {
		if ( $obj instanceof \EasyRdf_Resource ) {
			return '<' . $obj->getUri() . '>';
		} elseif ( $obj instanceof \EasyRdf_Literal ) {
			$s = '"' . $obj->getValue() . '"';

			if ( $obj->getDatatype() ) {
				$s .= '^^' . $obj->getDatatype();
			} elseif ( $obj->getLang() ) {
				$s .= '@' . $obj->getLang();
			}

			return $s;
		} else {
			return strval( $obj );
		}
	}

	//TODO: test resolveMentionedEntities
	//TODO: test all the addXXX methods
	//TODO: test all the getXXX methods

}
