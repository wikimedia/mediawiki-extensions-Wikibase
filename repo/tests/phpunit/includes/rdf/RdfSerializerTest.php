<?php

namespace Wikibase\Test;

use EasyRdf_Graph;
use SiteList;
use Wikibase\EntityRevision;
use Wikibase\RdfSerializer;

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

	/**
	 * @var RdfBuilderTest
	 */
	private $rdfTest;

	public function setUp() {
		parent::setUp();
		$this->rdfTest = new RdfBuilderTest();
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
		return $this->rdfTest->getTestEntities();
	}

	/**
	 * @return EasyRdf_Graph[]
	 */
	private function getTestGraphs() {
		return $this->rdfTest->getTestGraphs();
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

				$expectedStrings = RdfBuilderTest::rdf2strings( $expectedValues );
				$actualStrings = RdfBuilderTest::rdf2strings( $actualValues );

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

}
