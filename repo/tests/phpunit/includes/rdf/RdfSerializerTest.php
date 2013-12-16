<?php

namespace Wikibase\Test;

use Revision;
use Wikibase\Entity;
use Wikibase\RdfSerializer;

/**
 * @covers Wikibase\RdfSerializer
 *
 * @since 0.4
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


	protected static function newRdfSerializer( $formatName ) {
		$format = RdfSerializer::getFormat( $formatName );

		$mockRepo = new MockRepository();

		foreach( self::getTestEntities() as $entity ) {
			$mockRepo->putEntity( $entity );
		}

		return new RdfSerializer(
			$format,
			RdfBuilderTest::URI_BASE,
			RdfBuilderTest::URI_DATA,
			$mockRepo
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

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()->getMock();
		$revision->expects( $this->any() )->method( 'getId' )
			->will( $this->returnValue( 23 ) );
		$revision->expects( $this->any() )->method( 'getTimestamp' )
			->will( $this->returnValue( '20130101000000' ) );

		$cases = array();

		foreach ( $entities as $name => $entity ) {
			if ( array_key_exists( $name, $graphs ) ) {
				$cases[$name] = array(
					$entity,
					$revision,
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
	public function testBuildGraphForEntity( Entity $entity, Revision $revision, \EasyRdf_Graph $expectedGraph ) {
		$serializer = self::newRdfSerializer( 'rdf' );

		$graph = $serializer->buildGraphForEntity( $entity, $revision );
		//TODO: meta-info from Revision

		foreach ( $expectedGraph->resources() as $rc ) {
			foreach ( $expectedGraph->properties( $rc ) as $prop ) {
				$expectedValues = $expectedGraph->all( $rc, $prop );
				$actualValues = $graph->all( $rc, $prop );

				$expectedStrings = RdfBuilderTest::rdf2strings( $expectedValues );
				$actualStrings = RdfBuilderTest::rdf2strings( $actualValues );

				$this->assertArrayEquals( $expectedStrings, $actualStrings );
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
	public function testSerializeRdf( \EasyRdf_Graph $graph, $format, $regexes ) {
		$serializer = self::newRdfSerializer( $format );

		$data = $serializer->serializeRdf( $graph );

		foreach ( $regexes as $regex ) {
			$this->assertRegExp( $regex, $data );
		}
	}

	public function provideSerializeEntity() {
		$entities = self::getTestEntities();
		$patterns = self::getTestDataPatterns();

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()->getMock();
		$revision->expects( $this->any() )->method( 'getId' )
			->will( $this->returnValue( 23 ) );
		$revision->expects( $this->any() )->method( 'getTimestamp' )
			->will( $this->returnValue( '20130101000000' ) );

		$cases = array();

		foreach ( $entities as $name => $entity ) {
			foreach ( self::$formats as $format ) {
				if ( isset( $patterns[$name][$format] ) ) {
					$cases["$name/$format"] = array(
						$entity,
						$revision,
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
	public function testSerializeEntity( Entity $entity, Revision $revision, $format, $regexes ) {
		$serializer = self::newRdfSerializer( $format );

		$data = $serializer->serializeEntity( $entity, $revision );

		foreach ( $regexes as $regex ) {
			$this->assertRegExp( $regex, $data );
		}
	}

}
