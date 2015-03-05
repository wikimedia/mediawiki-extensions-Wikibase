<?php

namespace Wikibase\Test;

use EasyRdf_Graph;
use EasyRdf_Namespace;
use EasyRdf_Literal;
use EasyRdf_Resource;
use SiteList;
use DateTime;
use Wikibase\EntityRevision;
use Wikibase\RdfSerializer;
use Wikibase\RdfBuilder;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\RdfProducer;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @covers Wikibase\RdfSerializer
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RdfSerializerTest extends \MediaWikiTestCase {

	private static $formats = array(
		'rdf',
		'application/rdf+xml',
		'n3',
		'text/n3',
		'nt',
		'ntriples',
		'turtle',
	);

	public function setUp() {
		parent::setUp();
	}

	/**
	 * @return EntityRevision[]
	 */
	private function getTestEntityRevisions() {
		$entities = $this->getTestEntities();
		$revisions = array();

		foreach ( $entities as $name => $entity ) {
			$revisions[$name] = new EntityRevision( $entity, 23, '20130101000000' );
		}

		return $revisions;
	}

	/**
	 * @return Entity[]
	 */
	private function getTestEntities() {
		static $entities = array ();

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
		 *
		 * @var Entity $entity
		 */
		foreach ( $entities as $entity ) {
			$entity->setId( ItemId::newFromNumber( $i ++ ) );
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
	 * @return RdfBuilder
	 */
	private static function newRdfBuilder() {
		return new RdfBuilder(
				new SiteList(),
				RdfBuilderTest::URI_BASE,
				RdfBuilderTest::URI_DATA,
				RdfBuilderTest::getMockRepository(),
				RdfProducer::PRODUCE_ALL
		);
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
	 * @return EasyRdf_Graph[]
	 */
	private function getTestGraphs() {
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

	private function getTestDataPatterns() {
		static $patterns = array();

		if ( !empty( $patterns ) ) {
			return $patterns;
		}

		$patterns['empty']['rdf'] = array( '!<rdf:RDF.*</rdf:RDF>!s' );
		$patterns['empty']['n3']  = array( '!!s' );

		$patterns['terms']['rdf'] = array(
			'!<rdf:RDF.*</rdf:RDF>!s',
			'!<wikibase:Item.*rdf:about=".*?/Q2"!s',
			'!<rdfs:label xml:lang="en">Berlin</rdfs:label>!s',
			'!<skos:prefLabel xml:lang="en">Berlin</skos:prefLabel>!s',
			'!<schema:name xml:lang="en">Berlin</schema:name>!s',
			'!<schema:description xml:lang="en">German city</schema:description>!s',
			'!<skos:altLabel xml:lang="en">Berlin, Germany</skos:altLabel>!s',
			'!<schema:version rdf:datatype="http://www.w3.org/2001/XMLSchema#integer">23</schema:version>!s',
			'!<schema:dateModified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2013-01-01T00:00:00Z</schema:dateModified>!s',
		);

		$patterns['terms']['n3']  = array(
			'!entity:Q2!s',
			'!rdfs:label +"Berlin"@en *[,;.]!s',
			'!skos:prefLabel +"Berlin"@en *[,;.]!s',
			'!schema:name +"Berlin"@en *[,;.]!s',
			'!schema:description +"German city"@en *[,;.]!s',
			'!skos:altLabel +"Berlin, Germany"@en *[,;.]!s',
			'!schema:version +("23"\^\^xsd:integer|23) *[,;.]!s',
			'!schema:dateModified +"2013-01-01T00:00:00Z"\^\^xsd:dateTime *[,;.]!s',
		);

		$patterns['terms']['turtle'] = $patterns['terms']['n3'];

		// TODO: test links
		// TODO: test data values

		return $patterns;
	}

	private function newRdfSerializer( $formatName ) {
		$format = RdfSerializer::getFormat( $formatName );
		$mockRepo = RdfBuilderTest::getMockRepository();

		foreach( $this->getTestEntities() as $entity ) {
			$mockRepo->putEntity( $entity );
		}

		return new RdfSerializer(
			$format,
			RdfBuilderTest::URI_BASE,
			RdfBuilderTest::URI_DATA,
			RdfBuilderTest::getSiteList(),
			$mockRepo,
			RdfSerializer::PRODUCE_ALL
		);
	}

	public function provideGetFormat() {
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

	public function provideBuildGraphForEntityRevision() {
		$this->rdfTest = new RdfBuilderTest();
		$entityRevs = $this->getTestEntityRevisions();
		$graphs = $this->getTestGraphs();

		$cases = array();

		foreach ( $entityRevs as $name => $entityRev ) {
			if ( array_key_exists( $name, $graphs ) ) {
				$cases[$name] = array(
					$entityRev,
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
	 * @dataProvider provideBuildGraphForEntityRevision
	 */
	public function testBuildGraphForEntityRevision( EntityRevision $entityRevision, EasyRdf_Graph $expectedGraph ) {
		$serializer = $this->newRdfSerializer( 'rdf' );

		$graph = $serializer->buildGraphForEntityRevision( $entityRevision );

		foreach ( $expectedGraph->resources() as $rc ) {
			foreach ( $expectedGraph->properties( $rc ) as $prop ) {
				$expectedValues = $expectedGraph->all( $rc, $prop );
				$actualValues = $graph->all( $rc, $prop );

				$expectedStrings = self::rdf2strings( $expectedValues );
				$actualStrings = self::rdf2strings( $actualValues );

				$this->assertArrayEquals( $expectedStrings, $actualStrings );
			}
		}
	}

	public function provideSerializeRdf() {
		$this->rdfTest = new RdfBuilderTest();
		$graphs = $this->getTestGraphs();
		$patterns = $this->getTestDataPatterns();

		$cases = array();

		foreach ( $graphs as $name => $graph ) {
			foreach ( self::$formats as $format ) {
				if ( isset( $patterns[$name][$format] ) ) {
					$cases["$name/$format"] = array(
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
	public function testSerializeRdf( EasyRdf_Graph $graph, $format, $regexes ) {
		$serializer = $this->newRdfSerializer( $format );

		$data = $serializer->serializeRdf( $graph );

		foreach ( $regexes as $regex ) {
			$this->assertRegExp( $regex, $data );
		}
	}

	public function provideSerializeEntityRevision() {
		$this->rdfTest = new RdfBuilderTest();
		$entityRevs = $this->getTestEntityRevisions();
		$patterns = $this->getTestDataPatterns();

		$cases = array();

		foreach ( $entityRevs as $name => $entityRev ) {
			foreach ( self::$formats as $format ) {
				if ( isset( $patterns[$name][$format] ) ) {
					$cases["$name/$format"] = array(
						$entityRev,
						$format,
						$patterns[$name][$format],
					);
				}
			}
		}

		return $cases;
	}

	/**
	 * @dataProvider provideSerializeEntityRevision
	 */
	public function testSerializeEntityRevision( EntityRevision $entityRevision, $format, $regexes ) {
		$serializer = $this->newRdfSerializer( $format );

		$data = $serializer->serializeEntityRevision( $entityRevision );

		foreach ( $regexes as $regex ) {
			$this->assertRegExp( $regex, $data );
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
}
