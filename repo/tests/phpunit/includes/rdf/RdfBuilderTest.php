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
use Wikibase\DataModel\Entity\Property;
use DataValues\TimeValue;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\Snaks;

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
	 * @var RdfBuilder
	 */
	private $builder;

	/**
	 * @var array
	 */
	private $entities;

	/**
	 * @var string
	 */
	private $refHash;

	/**
	 * @return Entity[]
	 */
	public function getTestEntities() {
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
		$link = new SiteLink('enwiki', 'San Francisco', array( ItemId::newFromNumber( 42 ) ) );
		$entity->addSiteLink($link);
		$link = new SiteLink('ruwiki', 'Сан Франциско');
		$entity->addSiteLink($link);

		// test simple claims
		$snakFactory = new SnakFactory();
		$dataFactory = new DataValueFactory();
		$entity = new Item();
		$entities['claims'] = $entity;
		// Entity-type
		foreach ( self::getSnaks() as $snakData ) {
			list( $propId, $type, $dataType, $data ) = $snakData;
			if ( $dataType ) {
				$value = $dataFactory->newDataValue( $dataType, $data );
			} else {
				$value = null;
			}
			$mainSnak = $snakFactory->newSnak( PropertyId::newFromNumber( $propId ), $type, $value );
			$claim = new Claim( $mainSnak );
			$claim->setGuid( "TEST-Statement-$propId-{$mainSnak->getHash()}" );
			$statement = new Statement( $claim );
			if ( isset($snakData[4]) ) {
				$statement->setRank( $snakData[4] );
			}
			$entity->addClaim( $statement );
		}

		// test full claims
		$entities['statements'] = clone $entities['claims'];

		// test qualifiers
		$entity = new Item();
		$entities['qualifiers'] = $entity;
		$mainSnak = $snakFactory->newSnak( PropertyId::newFromNumber( 7 ), 'value',  $dataFactory->newDataValue( 'string', 'string' ));
		$qualifiers = new SnakList();

		$this->addSnaksFromArray( $qualifiers, self::getSnaks() );

		$claim = new Claim( $mainSnak, $qualifiers );
		$claim->setGuid( "TEST-Qualifiers" );
		$statement = new Statement( $claim );

		$entity->addClaim( $statement );

		// test references
		$entity = new Item();
		$entities['references'] = $entity;
		$mainSnak = $snakFactory->newSnak( PropertyId::newFromNumber( 7 ), 'value',  $dataFactory->newDataValue( 'string', 'string' ));
		$snaks = new SnakList();

		$this->addSnaksFromArray( $snaks, self::getSnaks() );

		$refs = new ReferenceList();
		$ref1 = new Reference( $snaks );
		$ref2 = new Reference( $snaks );

		$refs->addReference( $ref1 );
		$this->refHash = $ref1->getHash();

		$claim = new Claim( $mainSnak );
		$claim->setGuid( "TEST-References" );
		$statement = new Statement( $claim, $refs );

		$entity->addClaim( $statement );

		$mainSnak = $snakFactory->newSnak( PropertyId::newFromNumber( 7 ), 'value',  $dataFactory->newDataValue( 'string', 'string2' ));
		$refs = new ReferenceList();
		$refs->addReference( $ref2 );
		$claim = new Claim( $mainSnak );
		$claim->setGuid( "TEST-References-2" );
		$statement = new Statement( $claim, $refs );

		$entity->addClaim( $statement );

		// test composite values
		$entities['values'] = clone $entities['claims'];

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
	 * Add snaks from array to snak list
	 * @param Snaks $snaks
	 * @param array $snakData
	 */
	private function addSnaksFromArray( Snaks $snaks, $snakData) {
		$snakFactory = new SnakFactory();
		$dataFactory = new DataValueFactory();
		foreach ( $snakData as $snakData ) {
			list( $propId, $type, $dataType, $data ) = $snakData;
			if ( $dataType ) {
				$value = $dataFactory->newDataValue( $dataType, $data );
			} else {
				$value = null;
			}
			$snak = $snakFactory->newSnak( PropertyId::newFromNumber( $propId ), $type, $value );
			$snaks->addSnak( $snak );
		}
	}

	/**
	 * Get test snaks
	 * @return array
	 */
	private static function getSnaks() {
		return array(
			// property, type, valuetype, data
			array(2, 'value', 'wikibase-entityid', array('entity-type' => 'item', 'numeric-id' => 42), Statement::RANK_PREFERRED),
			array(3, 'value', 'string', 'Universe.svg'),
			array(4, 'value', 'globecoordinate', array('latitude' => 12.345, 'longitude' => 67.89)),
			array(5, 'value', 'monolingualtext', array('language' => 'ru', 'text' => 'превед')),
			array(6, 'value', 'quantity', array('amount' => 19.768, "unit" => "1", "upperBound" => 19.769, "lowerBound" => 19.767)),
			array(7, 'value', 'string', 'simplestring'),
			array(8, 'value', 'time', array( 'time' => "-00000000200-00-00T00:00:00Z", 'timezone' => 0, 'before' => 0, 'after' => 0, 'precision' => TimeValue::PRECISION_YEAR, 'calendarmodel' => 'http://calendar.acme.test/' )),
			array(9, 'value', 'string', 'http://url.acme.test/'),
			array(3, 'novalue', null, null),
			array(5, 'somevalue', null, null),
			// test preferred rank
			array(2, 'value', 'wikibase-entityid', array('entity-type' => 'item', 'numeric-id' => 666) ),
			// test deprecated rank
			array(5, 'value', 'monolingualtext', array('language' => 'ru', 'text' => 'бред'), Statement::RANK_DEPRECATED),
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
	private function makeEntityGraph( EntityId $entityId, $entityProps, $dataProps, $graph = null ) {
		if ( !$graph ) {
			$graph = new EasyRdf_Graph();
		}

		$entityUri = $this->builder->getEntityQName( RdfBuilder::NS_ENTITY, $entityId );
		$dataUri = $this->builder->getDataURL( $entityId );
		$entityResource = $graph->resource( $entityUri );
		$dataResource = $graph->resource( $dataUri );

		if ( $entityProps ) {
			$this->addProperties( $graph, $entityResource, $entityProps );
		}

		if ( $dataProps ) {
			$this->addProperties( $graph, $dataResource, $dataProps );
		}

		return $graph;
	}

	/**
	 * @param EasyRdf_Graph $graph
	 * @param EasyRdf_Resource $resource
	 * @param array $properties
	 */
	private function addProperties( EasyRdf_Graph $graph, EasyRdf_Resource $resource, $properties ) {
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
	 * Add sitelink to a graph
	 * @param EasyRdf_Graph $graph
	 * @param string $sitelink
	 * @param array $properties
	 */
	private function addToSitelink( EasyRdf_Graph $graph, $sitelink, $properties ) {
		$res = $graph->resource( $sitelink );

		foreach ( $properties as $prop => $val ) {
			if ( is_string( $val ) ) {
				$val = $graph->resource( $val );
			}
			$res->add( $prop, $val );
		}
	}

	private function makeEmptyGraph( $name ) {
		return $this->makeEntityGraph(
			$this->entities[$name]->getId(),
			array(
				'rdf:type' => RdfBuilder::NS_ONTOLOGY . ':Item',
			),
			array(
				'rdf:type' => RdfBuilder::NS_SCHEMA_ORG . ':Dataset',
				'schema:about' => $this->builder->getEntityQName( RdfBuilder::NS_ENTITY, $this->entities[$name]->getId() ),
				'schema:version' => new EasyRdf_Literal( 23, null, 'xsd:integer' ),
				'schema:dateModified' => new EasyRdf_Literal( '2013-01-01T00:00:00Z', null, 'xsd:dateTime' ),
			)
		);

	}

	private function makeTermsGraph( $name ) {
		$graph = $this->makeEmptyGraph($name);
		return $this->makeEntityGraph(
			$this->entities[$name]->getId(),
			array(
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
			null,
			$graph
		);
	}

	private function makeLinksGraph( $name ) {
		$graph = $this->makeEmptyGraph($name);
		self::addToSitelink( $graph, 'http://enwiki.acme.test/San%20Francisco', array(
			'rdf:type' => RdfBuilder::NS_SCHEMA_ORG . ':Article',
			RdfBuilder::NS_SCHEMA_ORG . ':inLanguage' => new EasyRdf_Literal( 'en' ),
			RdfBuilder::NS_SCHEMA_ORG . ':about' => $this->builder->getEntityQName( RdfBuilder::NS_ENTITY, $this->entities[$name]->getId() ),
			RdfBuilder::WIKIBASE_BADGE_QNAME => $this->builder->getEntityQName( RdfBuilder::NS_ENTITY, ItemId::newFromNumber( 42 ) )
		));
		self::addToSitelink( $graph, 'http://ruwiki.acme.test/%D0%A1%D0%B0%D0%BD%20%D0%A4%D1%80%D0%B0%D0%BD%D1%86%D0%B8%D1%81%D0%BA%D0%BE', array(
			'rdf:type' => RdfBuilder::NS_SCHEMA_ORG . ':Article',
			RdfBuilder::NS_SCHEMA_ORG . ':inLanguage' => new EasyRdf_Literal( 'ru' ),
			RdfBuilder::NS_SCHEMA_ORG . ':about' => $this->builder->getEntityQName( RdfBuilder::NS_ENTITY, $this->entities[$name]->getId() ),
		));
		return $graph;
	}

	private function makeSimpleClaimsGraph( $name ) {
		$graph = $this->makeEmptyGraph($name);
		return $this->makeEntityGraph(
			$this->entities[ $name ]->getId(),
			array(
				RdfBuilder::NS_DIRECT_CLAIM . ":P2" => $this->builder->getEntityQName( RdfBuilder::NS_ENTITY, ItemId::newFromNumber( 42 ) ),
				RdfBuilder::NS_DIRECT_CLAIM . ":P3" => array(
					RdfBuilder::COMMONS_URI . "Universe.svg",
					RdfBuilder::WIKIBASE_NOVALUE_QNAME
				),
				RdfBuilder::NS_DIRECT_CLAIM . ":P4" => new \EasyRdf_Literal("Point(12.345 67.89)", null, 'geo:wktLiteral'),
				RdfBuilder::NS_DIRECT_CLAIM . ":P5" => array(
					new \EasyRdf_Literal('превед', 'ru'),
					RdfBuilder::WIKIBASE_SOMEVALUE_QNAME
				),
				RdfBuilder::NS_DIRECT_CLAIM . ":P6" => new \EasyRdf_Literal_Decimal(19.768),
				RdfBuilder::NS_DIRECT_CLAIM . ":P7" => new \EasyRdf_Literal('simplestring'),
				RdfBuilder::NS_DIRECT_CLAIM . ":P8" => new \EasyRdf_Literal_DateTime('-00000000200-00-00T00:00:00Z'),
				RdfBuilder::NS_DIRECT_CLAIM . ":P9" => 'http://url.acme.test/',
			),
			null,
			$graph
		);

	}

	/**
	 * Add data for statement node
	 * @param EasyRdf_Graph $graph
	 * @param EasyRdf_Resource $claim
	 * @param int $valueId Property id
	 * @param mixed $data Property value
	 */
	private function addStatementClaim( EasyRdf_Graph $graph, EasyRdf_Resource $claim, $valueId, $data, $rank = null ) {
		$this->addProperties($graph, $claim, array(
			'rdf:type' => RdfBuilder::WIKIBASE_STATEMENT_QNAME,
			$this->builder->getEntityQName( RdfBuilder::NS_VALUE, PropertyId::newFromNumber( $valueId ) ) => $data
		));
		if ( !is_null( $rank ) ) {
			$claim->addResource( RdfBuilder::WIKIBASE_RANK_QNAME, RdfBuilder::$rank_map[$rank] );
		}
	}

	private function makeClaimsGraph( $name ) {
		$graph = $this->makeEmptyGraph($name);

		$claim2 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-2-423614cd831ed4e8da1138c9229cb65cf96f9366" );
		$this->addStatementClaim( $graph, $claim2, 2,
			$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, ItemId::newFromNumber( 42 ) ),
			Statement::RANK_PREFERRED
		);
		$claim21 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-2-475ae31b07cff4f0e33531030b1ba58f004fcd4b" );
		$this->addStatementClaim( $graph, $claim21, 2,
			$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, ItemId::newFromNumber( 666 ) )
		);

		$claim3 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-3-b181ddac61642fe80bbf8e4a8eaa1da425cb0ac9" );
		$this->addStatementClaim( $graph, $claim3, 3,
			RdfBuilder::COMMONS_URI . "Universe.svg"
		);

		$claim31 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-3-12914044e0dbab210aa9d81168bd50471bbde12d" );
		$this->addStatementClaim( $graph, $claim31, 3,
			RdfBuilder::WIKIBASE_NOVALUE_QNAME
		);

		$claim4 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-4-8749fa158a249e1befa6ed077f648c56197a2b2d" );
		$this->addStatementClaim( $graph, $claim4, 4,
			new \EasyRdf_Literal( "Point(12.345 67.89)", null, 'geo:wktLiteral' )
		);

		$claim5 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-5-93da31338cb80c2eb0f92a5459a186bd59579180" );
		$this->addStatementClaim( $graph, $claim5, 5,
			new \EasyRdf_Literal( 'превед', 'ru' )
		);
		$claim51 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-5-8c5d9fe1bfe1fe52e5ab706ae3e5d62f4aaa8d5b" );
		$this->addStatementClaim( $graph, $claim51, 5,
			RdfBuilder::WIKIBASE_SOMEVALUE_QNAME
		);
		$claim52 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-5-b27fe5a95fa506ca99acebd9e97c9c5a81e14f99" );
		$this->addStatementClaim( $graph, $claim52, 5,
			new \EasyRdf_Literal( 'бред', 'ru' ),
			Statement::RANK_DEPRECATED
		);

		$claim6 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-6-9ae284048af6d9ab0f2815ef104216cb8b22e8bc" );
		$this->addStatementClaim( $graph, $claim6, 6,
			new \EasyRdf_Literal_Decimal( 19.768 )
		);

		$claim7 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-7-6063d202e584b79a2e9f89ab92b51e7f22ef9886" );
		$this->addStatementClaim( $graph, $claim7, 7,
			new \EasyRdf_Literal( 'simplestring' )
		);

		$claim8 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-8-5dd0f6624a7545401bc306a068ac1bbe8148bfac" );
		$this->addStatementClaim( $graph, $claim8, 8,
			new \EasyRdf_Literal_DateTime( '-00000000200-00-00T00:00:00Z' )
		);

		$claim9 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-9-2669d541dfd2d6cc0105927bff02bbe0eec0e921" );
		$this->addStatementClaim( $graph, $claim9, 9,
			'http://url.acme.test/'
		);

		return $this->makeEntityGraph(
			$this->entities[ $name ]->getId(),
			array(
				$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, PropertyId::newFromNumber( 2 ) ) => array($claim2, $claim21),
				$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, PropertyId::newFromNumber( 3 ) ) => array($claim3, $claim31),
				$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, PropertyId::newFromNumber( 4 ) ) => $claim4,
				$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, PropertyId::newFromNumber( 5 ) ) => array($claim5, $claim51, $claim52),
				$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, PropertyId::newFromNumber( 6 ) ) => $claim6,
				$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, PropertyId::newFromNumber( 7 ) ) => $claim7,
				$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, PropertyId::newFromNumber( 8 ) ) => $claim8,
				$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, PropertyId::newFromNumber( 9 ) ) => $claim9,
			),
			null,
			$graph
		);
	}

	private function makeQualifiersGraph( $name ) {
		$graph = $this->makeEmptyGraph( $name );
		$claim = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Qualifiers" );
		$this->addStatementClaim( $graph, $claim, 7,
			new \EasyRdf_Literal( 'string' )
		);

		// qualifiers
		$this->addProperties( $graph, $claim, array(
			RdfBuilder::NS_QUALIFIER . ":P2" => array(
				$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, ItemId::newFromNumber( 42 ) ),
				$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, ItemId::newFromNumber( 666 ) ),
			),
			RdfBuilder::NS_QUALIFIER . ":P3" => array(
				RdfBuilder::COMMONS_URI . "Universe.svg",
				RdfBuilder::WIKIBASE_NOVALUE_QNAME
			),
			RdfBuilder::NS_QUALIFIER . ":P4" => new \EasyRdf_Literal("Point(12.345 67.89)", null, 'geo:wktLiteral'),
			RdfBuilder::NS_QUALIFIER . ":P5" => array(
				new \EasyRdf_Literal('превед', 'ru'),
				new \EasyRdf_Literal('бред', 'ru'),
				RdfBuilder::WIKIBASE_SOMEVALUE_QNAME
			),
			RdfBuilder::NS_QUALIFIER . ":P6" => new \EasyRdf_Literal_Decimal(19.768),
			RdfBuilder::NS_QUALIFIER . ":P7" => new \EasyRdf_Literal('simplestring'),
			RdfBuilder::NS_QUALIFIER . ":P8" => new \EasyRdf_Literal_DateTime('-00000000200-00-00T00:00:00Z'),
			RdfBuilder::NS_QUALIFIER . ":P9" => 'http://url.acme.test/',
		));

		return $this->makeEntityGraph(
				$this->entities[ $name ]->getId(),
				array(
					$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, PropertyId::newFromNumber( 7 ) ) => $claim
				),
				null,
				$graph
		);

	}

	private function makeReferencesGraph( $name ) {
		$graph = $this->makeEmptyGraph($name);

		$claim = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-References" );
		$this->addStatementClaim( $graph, $claim, 7,
				new \EasyRdf_Literal( 'string' )
		);
		$this->addProperties( $graph, $claim, array(
				RdfBuilder::PROV_QNAME => RdfBuilder::NS_REFERENCE . ":" . $this->refHash
			)
		);

		$claim2 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-References-2" );
		$this->addStatementClaim($graph, $claim2, 7,
				new \EasyRdf_Literal( 'string2' )
		);
		$this->addProperties( $graph, $claim2, array(
				RdfBuilder::PROV_QNAME => RdfBuilder::NS_REFERENCE . ":" . $this->refHash
			)
		);

		$ref = $graph->resource( RdfBuilder::NS_REFERENCE . ":" . $this->refHash );

		$this->addProperties( $graph, $ref, array(
				'rdf:type' => RdfBuilder::WIKIBASE_REFERENCE_QNAME,
				RdfBuilder::NS_VALUE . ":P2" => array(
						$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, ItemId::newFromNumber( 42 ) ),
						$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, ItemId::newFromNumber( 666 ) ),
				),
				RdfBuilder::NS_VALUE . ":P3" => array(
						RdfBuilder::COMMONS_URI . "Universe.svg",
						RdfBuilder::WIKIBASE_NOVALUE_QNAME
				),
				RdfBuilder::NS_VALUE . ":P4" => new \EasyRdf_Literal( "Point(12.345 67.89)", null, 'geo:wktLiteral' ),
				RdfBuilder::NS_VALUE . ":P5" => array(
						new \EasyRdf_Literal( 'превед', 'ru' ),
						new \EasyRdf_Literal( 'бред', 'ru' ),
						RdfBuilder::WIKIBASE_SOMEVALUE_QNAME
				),
				RdfBuilder::NS_VALUE . ":P6" => new \EasyRdf_Literal_Decimal( 19.768 ),
				RdfBuilder::NS_VALUE . ":P7" => new \EasyRdf_Literal( 'simplestring' ),
				RdfBuilder::NS_VALUE . ":P8" => new \EasyRdf_Literal_DateTime( '-00000000200-00-00T00:00:00Z' ),
				RdfBuilder::NS_VALUE . ":P9" => 'http://url.acme.test/',
		));

		return $this->makeEntityGraph(
				$this->entities[ $name ]->getId(),
				array(
					$this->builder->getEntityQName( RdfBuilder::NS_ENTITY, PropertyId::newFromNumber( 7 ) ) => array($claim, $claim2)
				),
				null,
				$graph
		);
	}

	public function makeValuesGraph( $name ) {
		$graph = $this->makeClaimsGraph( $name );

		// P4 geo
		$claim4 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-4-8749fa158a249e1befa6ed077f648c56197a2b2d" );
		$claim4->addResource(
			$this->builder->getEntityQName( RdfBuilder::NS_VALUE, PropertyId::newFromNumber( 4 ) ) . "-value",
			RdfBuilder::NS_VALUE . ':a490f3ae6258459a479723fa2f0d8141' );
		$value = $graph->resource( RdfBuilder::NS_VALUE . ':a490f3ae6258459a479723fa2f0d8141' );
		$this->addProperties( $graph, $value, array(
			'rdf:type' => RdfBuilder::WIKIBASE_VALUE_QNAME,
			RdfBuilder::NS_ONTOLOGY . ":Latitude" => 12.345,
			RdfBuilder::NS_ONTOLOGY . ":Longitude" => 67.89,
		));
		// P6 quantity
		$claim6 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-6-9ae284048af6d9ab0f2815ef104216cb8b22e8bc" );
		$claim6->addResource(
			$this->builder->getEntityQName( RdfBuilder::NS_VALUE, PropertyId::newFromNumber( 6 ) ) . "-value",
			RdfBuilder::NS_VALUE . ':1e09d673624819aacd170165aae555a1'  );
		$value = $graph->resource( RdfBuilder::NS_VALUE . ':1e09d673624819aacd170165aae555a1'  );
		$this->addProperties( $graph, $value, array(
			'rdf:type' => RdfBuilder::WIKIBASE_VALUE_QNAME,
			RdfBuilder::NS_ONTOLOGY . ":Amount" => 19.768,
			RdfBuilder::NS_ONTOLOGY . ":Unit" => new \EasyRdf_Literal("1"),
			RdfBuilder::NS_ONTOLOGY . ":UpperBound" => 19.769,
			RdfBuilder::NS_ONTOLOGY . ":LowerBound" => 19.767,
		));
		// P8 dateTime
		$claim8 = $graph->resource( RdfBuilder::NS_STATEMENT . ":TEST-Statement-8-5dd0f6624a7545401bc306a068ac1bbe8148bfac" );
		$claim8->addResource(
			$this->builder->getEntityQName( RdfBuilder::NS_VALUE, PropertyId::newFromNumber( 8 ) ) . "-value",
			RdfBuilder::NS_VALUE . ':c7499b57a1a474e6a89f4c3b42ca01e7'  );
		$value = $graph->resource( RdfBuilder::NS_VALUE . ':c7499b57a1a474e6a89f4c3b42ca01e7'  );
		$this->addProperties( $graph, $value, array(
			'rdf:type' => RdfBuilder::WIKIBASE_VALUE_QNAME,
			RdfBuilder::NS_ONTOLOGY . ":Time" => new \EasyRdf_Literal_DateTime('-00000000200-00-00T00:00:00Z'),
			RdfBuilder::NS_ONTOLOGY . ":Precision" => TimeValue::PRECISION_YEAR,
			RdfBuilder::NS_ONTOLOGY . ":Timezone" => 0,
			RdfBuilder::NS_ONTOLOGY . ":CalendarModel" => 'http://calendar.acme.test/',
		));
		return $graph;
	}

	/**
	 * @return EasyRdf_Graph[]|null
	 */
	public function getTestGraphs() {
		static $graphs = array();

		if ( !empty( $graphs ) ) {
			return $graphs;
		}

		$this->builder = self::newRdfBuilder( 'rdf' ); //XXX: ugh, dummy object

		foreach ( $this->builder->getNamespaces() as $gname => $uri ) {
			EasyRdf_Namespace::set( $gname, $uri );
		}

		$this->entities = $this->getTestEntities();

		$graphs['empty'] = $this->makeEmptyGraph( 'empty' );
		$graphs['terms'] = $this->makeTermsGraph( 'terms' );
		$graphs['links'] = $this->makeLinksGraph( 'links' );
		$graphs['claims'] = $this->makeSimpleClaimsGraph( 'claims' );
		$graphs['statements'] = $this->makeClaimsGraph( 'statements' );
		$graphs['qualifiers'] = $this->makeQualifiersGraph( 'qualifiers' );
		$graphs['references'] = $this->makeReferencesGraph( 'references' );
		$graphs['values'] = $this->makeValuesGraph( 'values' );

		return $graphs;
	}

	/**
	 * Get site list
	 * @return \SiteList
	 */
	public static function getSiteList() {
		$list = new SiteList();

		$wiki = new \Site();
		$wiki->setGlobalId( 'enwiki' );
		$wiki->setLanguageCode( 'en' );
		$wiki->setLinkPath( 'http://enwiki.acme.test/$1' );
		$list['enwiki'] = $wiki;

		$wiki = new \Site();
		$wiki->setGlobalId( 'ruwiki' );
		$wiki->setLanguageCode( 'ru' );
		$wiki->setLinkPath( 'http://ruwiki.acme.test/$1' );
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
	public static function getMockRepository() {
		static $repo;

		if ( !empty($repo) ) {
			return $repo;
		}

		$repo = new MockRepository();

		foreach( self::getTestProperties() as $prop ) {
			list($id, $type) = $prop;
			$fingerprint = Fingerprint::newEmpty();
			$fingerprint->setLabel( 'en', "Property$id" );
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
		$entities = $this->getTestEntities();
		$graphs = $this->getTestGraphs();

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
