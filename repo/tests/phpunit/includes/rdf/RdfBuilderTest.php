<?php

namespace Wikibase\Test;

use DateTime;
use EasyRdf_Graph;
use EasyRdf_Literal;
use EasyRdf_Namespace;
use EasyRdf_Resource;
use SiteList;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\EntityRevision;
use Wikibase\RdfBuilder;

/**
 * @covers Wikibase\RdfBuilder
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

	/**
	 * @return Entity[]
	 */
	public static function getTestEntities() {
		static $entities = array();

		if ( !empty( $entities ) ) {
			return $entities;
		}

		$entity = new Item();
		$entities['empty'] = $entity;


		$entity = new Item();
		$entities['terms'] = $entity;
		$entity->setFingerprint( self::newTestFingerprint() );

		// TODO: test links
		// TODO: test data values

		$i = 1;

		/**
		 * @var Entity $entity
		 */
		foreach ( $entities as $entity ) {
			$entity->setId( ItemId::newFromNumber( $i++ ) );
		}

		return $entities;
	}

	private static function newTestFingerprint() {
		$fingerprint = Fingerprint::newEmpty();

		$fingerprint->setLabel( 'en', 'Berlin' );
		$fingerprint->setLabel( 'ru', 'Берлин' );

		$fingerprint->setDescription( 'en', 'German city' );
		$fingerprint->setDescription( 'ru', 'столица и одновременно земля Германии' );

		$fingerprint->setAliasGroup( 'en', array( 'Berlin, Germany', 'Land Berlin' ) );
		$fingerprint->setAliasGroup( 'ru', array( 'Berlin' ) );

		return $fingerprint;
	}

	/**
	 * @param EntityId $entityId
	 * @param array $entityProps
	 * @param array $dataProps
	 *
	 * @return EasyRdf_Graph
	 */
	private static function makeEntityGraph( EntityId $entityId, $entityProps, $dataProps ) {
		$graph = new EasyRdf_Graph();

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
	 * @param EasyRdf_Graph $graph
	 * @param EasyRdf_Resource $resource
	 * @param array $properties
	 */
	private static function addProperties( EasyRdf_Graph $graph, EasyRdf_Resource $resource, $properties ) {
		foreach ( $properties as $prop => $values ) {
			if ( !is_array( $values ) ) {
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
	 * @return EasyRdf_Graph[]|null
	 */
	public static function getTestGraphs() {
		static $graphs = array();

		if ( !empty( $graphs ) ) {
			return $graphs;
		}

		$builder = self::newRdfBuilder( 'rdf' ); //XXX: ugh, dummy object

		foreach ( $builder->getNamespaces() as $gname => $uri ) {
			EasyRdf_Namespace::set( $gname, $uri );
		}

		$entities = self::getTestEntities();

		$graphs['empty'] = self::makeEntityGraph(
			$entities['empty']->getId(),
			array(
				'rdf:type' => RdfBuilder::NS_ONTOLOGY . ':Item',
			),
			array(
				'rdf:type' => RdfBuilder::NS_SCHEMA_ORG . ':Dataset',
				'schema:about' => $builder->getEntityQName( RdfBuilder::NS_ENTITY, $entities['empty']->getId() ),
				'schema:version' => new EasyRdf_Literal( 23, null, 'xsd:integer' ),
				'schema:dateModified' => new EasyRdf_Literal( '2013-01-01T00:00:00Z', null, 'xsd:dateTime' ),
			)
		);

		$graphs['terms'] = self::makeEntityGraph(
			$entities['terms']->getId(),
			array(
				'rdf:type' => RdfBuilder::NS_ONTOLOGY . ':Item',
				'rdfs:label' => array(
					new EasyRdf_Literal( 'Berlin', 'en' ),
					new EasyRdf_Literal( 'Берлин', 'ru' )
				),
				'skos:prefLabel' => array(
					new EasyRdf_Literal( 'Berlin', 'en' ),
					new EasyRdf_Literal( 'Берлин', 'ru' )
				),
				'schema:name' => array(
					new EasyRdf_Literal( 'Berlin', 'en' ),
					new EasyRdf_Literal( 'Берлин', 'ru' )
				),
				'schema:description' => array(
					new EasyRdf_Literal( 'German city', 'en' ),
					new EasyRdf_Literal( 'столица и одновременно земля Германии', 'ru' )
				),
				'skos:altLabel' => array(
					new EasyRdf_Literal( 'Berlin, Germany', 'en' ),
					new EasyRdf_Literal( 'Land Berlin', 'en' ),
					new EasyRdf_Literal( 'Berlin', 'ru' )
				),
			),

			array(
				'rdf:type' => RdfBuilder::NS_SCHEMA_ORG . ':Dataset',
				'schema:about' => $builder->getEntityQName( RdfBuilder::NS_ENTITY, $entities['terms']->getId() ),
				'schema:version' => new EasyRdf_Literal( 23, null, 'xsd:integer' ),
				'schema:dateModified' => new EasyRdf_Literal( '2013-01-01T00:00:00Z', null, 'xsd:dateTime' ),
			)
		);

		// TODO: test links
		// TODO: test data values

		return $graphs;
	}

	/**
	 * @return RdfBuilder
	 */
	private static function newRdfBuilder() {
		return new RdfBuilder(
			new SiteList(),
			self::URI_BASE,
			self::URI_DATA
		);
	}

	public function provideAddEntity() {
		$entities = self::getTestEntities();
		$graphs = self::getTestGraphs();

		$cases = array();

		foreach ( $entities as $name => $entity ) {
			if ( array_key_exists( $name, $graphs ) ) {
				$cases[] = array(
					new EntityRevision( $entity, 23, '20130101000000' ),
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
	public function testAddEntity( EntityRevision $entityRevision, EasyRdf_Graph $expectedGraph ) {
		$builder = self::newRdfBuilder();

		$builder->addEntity( $entityRevision->getEntity() );
		$builder->addEntityRevisionInfo( $entityRevision->getEntity()->getId(), $entityRevision->getRevisionId(), $entityRevision->getTimestamp() );
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
		if ( $obj instanceof EasyRdf_Resource ) {
			return '<' . $obj->getUri() . '>';
		} elseif ( $obj instanceof EasyRdf_Literal ) {
			$value = $obj->getValue();

			if ( $value instanceof DateTime ) {
				$value = wfTimestamp( TS_ISO_8601, $value->getTimestamp() );
			}

			if ( is_int( $value ) ) {
				$s = strval( $value );
			} elseif ( is_bool( $value ) ) {
				$s = $value ? 'true' : 'false';
			} else {
				$s = '"' . strval( $value ) . '"';

				if ( $obj->getDatatype() ) {
					$s .= '^^' . $obj->getDatatype();
				} elseif ( $obj->getLang() ) {
					$s .= '@' . $obj->getLang();
				}
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
