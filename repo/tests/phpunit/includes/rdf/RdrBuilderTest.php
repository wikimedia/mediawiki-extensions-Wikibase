<?php

namespace Wikibase\Test;

use DateTime;
use EasyRdf_Graph;
use EasyRdf_Literal;
use EasyRdf_Namespace;
use EasyRdf_Resource;
use EasyRdf_Format;
use SiteList;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\RdfBuilder;
use Wikibase\RdfProducer;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\RdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 * @group WikibaseRdr
 *
 * @licence GNU GPL v2+
 */
class RdrBuilderTest extends \MediaWikiTestCase {

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

	private $codec;

	/**
	 * @return RdfBuilder
	 */
	private static function newRdfBuilder($produce = RdfProducer::PRODUCE_ALL) {
		\EasyRdf_Format::registerSerialiser('rdr', '\\Wikibase\\RdrSerializer');
		return new RdfBuilder(
			RdfBuilderTest::getSiteList(),
			RdfBuilderTest::URI_BASE,
			RdfBuilderTest::URI_DATA,
			RdfBuilderTest::getMockRepository(),
			$produce
		);
	}

	/**
	 * Load serialized ntriples
	 * @param string $testName
	 * @return array
	 */
	public function getSerializedData( $testName )
	{
		$filename = __DIR__ . "/../../data/rdf/$testName.rdr";
		if ( !file_exists( $filename ) )
		{
			return null;
		}
		return file_get_contents( $filename );
	}

	public function getRdfTests() {
		$rdf = new RdfBuilderTest();
		$rdfTests = array(
				array('Q1', 'Q1_simple'),
				array('Q2', 'Q2_labels'),
				array('Q3', 'Q3_links'),
				array('Q4', 'Q4_claims'),
				array('Q5', 'Q5_badges'),
				array('Q6', 'Q6_qualifiers'),
				array('Q7', 'Q7_references'),
		);

		$testData = array();
		foreach ( $rdfTests as $test ) {
			$testData[$test[1]] = array (
					$rdf->getEntityData( $test[0] ),
					$this->getSerializedData( $test[1] ), $test[1]
			);
		}
		return $testData;
	}

	/**
	 * Extract text test data from RDF builder
	 * @param RdfBuilder $builder
	 * @return multitype:
	 */
	private function getDataFromBuilder( RdfBuilder $builder ) {
		$graph = $builder->getGraph();
		$format = EasyRdf_Format::getFormat( "rdr" );
		$serialiser = $format->newSerialiser();
		return $serialiser->serialise( $graph, "rdr" );
	}

	/**
	 * @dataProvider getRdfTests
	 */
	public function testRdr( Entity $entity, $correctData, $testName ) {
		$builder = self::newRdfBuilder();
		$builder->addEntity( $entity );
		$builder->addEntityRevisionInfo( $entity->getId(), 42, "2014-11-04T03:11:05Z" );
		$data =  $this->getDataFromBuilder( $builder );
		$this->assertEquals( $correctData, $data);
	}

}
