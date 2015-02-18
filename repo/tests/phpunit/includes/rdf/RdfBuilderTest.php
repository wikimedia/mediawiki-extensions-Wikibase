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
use Wikibase\DataModel\SiteLink;
use Wikibase\RdfProducer;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\SnakFactory;
use Wikibase\DataModel\Entity\PropertyId;
use DataValues\DataValueFactory;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\DataModel\Entity\Property;
use DataValues\TimeValue;

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

		// test labels & descriptions
		$entity = new Item();
		$entities['terms'] = $entity;
		$entity->setFingerprint( self::newTestFingerprint() );

		// test links
		$entity = new Item();
		$entities['links'] = $entity;
		$link = new SiteLink('enwiki', 'San Francisco');
		$entity->addSiteLink($link);
		$link = new SiteLink('ruwiki', 'Сан Франциско');
		$entity->addSiteLink($link);

		// TODO: test simple claims
		$snakFactory = new SnakFactory();
		$dataFactory = new DataValueFactory();
		$guidGen = new ClaimGuidGenerator();
		$fakeId = ItemId::newFromNumber(42);
		$entity = new Item();
		$entities['claims'] = $entity;
		// Entity-type
		foreach(self::getSnaks() as $snakData) {
			list($propId, $type, $dataType, $data) = $snakData;
			if( $dataType ) {
				$value = $dataFactory->newDataValue( $dataType, $data );
			} else {
				$value = null;
			}
			$mainSnak = $snakFactory->newSnak( PropertyId::newFromNumber( $propId ), $type, $value );
			$claim = new Claim( $mainSnak );
			$claim->setGuid( $guidGen->newGuid( $fakeId ) );
			$entity->addClaim( new Statement( $claim ) );
		}

		// TODO: test full claims
		// TODO: test references
		// TODO: test composite values

		$i = 1;

		/**
		 * @var Entity $entity
		 */
		foreach ( $entities as $entity ) {
			$entity->setId( ItemId::newFromNumber( $i++ ) );
		}

		return $entities;
	}

	/**
	 * Get test snaks
	 * @return array
	 */
	private static function getSnaks() {
		return array(
				// property, type, valuetype, data
				array(2, 'value', 'wikibase-entityid', array('entity-type' => 'item', 'numeric-id' => 42)),
				array(3, 'value', 'string', 'Universe.svg'),
				//array(4, 'value', 'string', 'Universe.svg'),
				array(5, 'value', 'monolingualtext', array('language' => 'ru', 'text' => 'привет')),
				array(6, 'value', 'quantity', array('amount' => 19.768, "unit" => "1", "upperBound" => 19.769, "lowerBound" => 19.767)),
				array(7, 'value', 'string', 'simplestring'),
				array(8, 'value', 'time', array( 'time' => "-00000000200-00-00T00:00:00Z", 'timezone' => 0, 'before' => 0, 'after' => 0, 'precision' => TimeValue::PRECISION_YEAR, 'calendarmodel' => 'http://calendar.acme.test/' )),
				array(9, 'value', 'string', 'http://url.acme.test/'),
		);
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

	private static function addToSitelink( EasyRdf_Graph $graph, $sitelink, $properties) {
		$res = $graph->resource( $sitelink );

		foreach($properties as $prop => $val) {
			if ( is_string( $val ) ) {
				$val = $graph->resource( $val );
			}
			$res->add( $prop, $val );
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

		// test links
		$graphs['links'] = self::makeEntityGraph(
			$entities['links']->getId(),
			array(
				'rdf:type' => RdfBuilder::NS_ONTOLOGY . ':Item',

			),
			array(
				'rdf:type' => RdfBuilder::NS_SCHEMA_ORG . ':Dataset',
				'schema:about' => $builder->getEntityQName( RdfBuilder::NS_ENTITY, $entities['links']->getId() ),
				'schema:version' => new EasyRdf_Literal( 23, null, 'xsd:integer' ),
				'schema:dateModified' => new EasyRdf_Literal( '2013-01-01T00:00:00Z', null, 'xsd:dateTime' ),
			)
		);
		self::addToSitelink($graphs['links'], 'http://enwiki.acme.test/San%20Francisco', array(
				'rdf:type' => RdfBuilder::NS_SCHEMA_ORG . ':Article',
				RdfBuilder::NS_SCHEMA_ORG . ':inLanguage' => new EasyRdf_Literal( 'en' ),
				RdfBuilder::NS_SCHEMA_ORG . ':about' => $builder->getEntityQName( RdfBuilder::NS_ENTITY, $entities['links']->getId() ),
		));
		self::addToSitelink($graphs['links'], 'http://ruwiki.acme.test/%D0%A1%D0%B0%D0%BD%20%D0%A4%D1%80%D0%B0%D0%BD%D1%86%D0%B8%D1%81%D0%BA%D0%BE', array(
				'rdf:type' => RdfBuilder::NS_SCHEMA_ORG . ':Article',
				RdfBuilder::NS_SCHEMA_ORG . ':inLanguage' => new EasyRdf_Literal( 'ru' ),
				RdfBuilder::NS_SCHEMA_ORG . ':about' => $builder->getEntityQName( RdfBuilder::NS_ENTITY, $entities['links']->getId() ),
		));

		// test claims
		$graphs['claims'] = self::makeEntityGraph(
				$entities['claims']->getId(),
				array(
						'rdf:type' => RdfBuilder::NS_ONTOLOGY . ':Item',
						RdfBuilder::NS_DIRECT_CLAIM . ":P2" => $builder->getEntityQName( RdfBuilder::NS_ENTITY, ItemId::newFromNumber( 42 ) ),
						RdfBuilder::NS_DIRECT_CLAIM . ":P3" => RdfBuilder::COMMONS_URI . "Universe.svg",
						RdfBuilder::NS_DIRECT_CLAIM . ":P5" => new \EasyRdf_Literal('привет', 'ru'),
						RdfBuilder::NS_DIRECT_CLAIM . ":P6" => new \EasyRdf_Literal_Decimal(19.768),
						RdfBuilder::NS_DIRECT_CLAIM . ":P7" => new \EasyRdf_Literal('simplestring'),
						RdfBuilder::NS_DIRECT_CLAIM . ":P8" => new \EasyRdf_Literal_DateTime('-00000000200-00-00T00:00:00Z'),
						RdfBuilder::NS_DIRECT_CLAIM . ":P9" => 'http://url.acme.test/',
				),
				array(
						'rdf:type' => RdfBuilder::NS_SCHEMA_ORG . ':Dataset',
						'schema:about' => $builder->getEntityQName( RdfBuilder::NS_ENTITY, $entities['claims']->getId() ),
						'schema:version' => new EasyRdf_Literal( 23, null, 'xsd:integer' ),
						'schema:dateModified' => new EasyRdf_Literal( '2013-01-01T00:00:00Z', null, 'xsd:dateTime' ),
				)
		);

		// TODO: test data values

		return $graphs;
	}

	private static function getSiteList() {
		$list = new SiteList();

		$wiki = new \Site();
		$wiki->setGlobalId('enwiki');
		$wiki->setLanguageCode( 'en' );
		$wiki->setLinkPath('http://enwiki.acme.test/$1');
		$list['enwiki'] = $wiki;

		$wiki = new \Site();
		$wiki->setGlobalId('ruwiki');
		$wiki->setLanguageCode( 'ru' );
		$wiki->setLinkPath('http://ruwiki.acme.test/$1');
		$list['ruwiki'] = $wiki;

		return $list;
	}

	/**
	 * Define a set of fake properties
	 * @return array
	 */
	private static function getTestProperties() {
		return array(
				array(2, 'wikibase-entityid'),
				array(3, 'commonsMedia'),
				array(4, 'globecoordinate'),
				array(5, 'monolingualtext'),
				array(6, 'quantity'),
				array(7, 'string'),
				array(8, 'time'),
				array(9, 'url'),
		);
	}

	/**
	 * Construct mock repository
	 * @return \Wikibase\Test\MockRepository
	 */
	private static function getMockRepository() {
		static $repo;

		if( !empty($repo) ) {
			return $repo;
		}

		$repo = new MockRepository();

		foreach(self::getTestProperties() as $prop) {
			list($id, $type) = $prop;
			$fingerprint = Fingerprint::newEmpty();
			$fingerprint->setLabel( 'en', "P$id" );
			$entity = new Property( PropertyId::newFromNumber($id), $fingerprint, $type );
			$repo->putEntity( $entity );
		}
		return $repo;
	}

	/**
	 * @return RdfBuilder
	 */
	private static function newRdfBuilder() {
		return new RdfBuilder(
			self::getSiteList(),
			self::URI_BASE,
			self::URI_DATA,
			self::getMockRepository(),
			RdfProducer::PRODUCE_ALL
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
//var_dump($graph->resources());
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
		} elseif ( $obj instanceof \EasyRdf_Literal_DateTime ) {
			$value = $obj->toRdfPhp();
			return $value['value'] . '^^' . $value['type'];
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
