<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\EntityRevision;
use Wikibase\RdfSerializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
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
		static $entities = array();

		if ( !empty( $entities ) ) {
			return $entities;
		}

		$entity = new Item();
		$entities['empty'] = $entity;

		$entity = new Item();
		$entities['terms'] = $entity;
		$entity->setFingerprint( $this->newTestFingerprint() );

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
	private function newTestFingerprint() {
		$fingerprint = new Fingerprint();

		$fingerprint->setLabel( 'en', 'Berlin' );
		$fingerprint->setLabel( 'ru', 'Берлин' );

		$fingerprint->setDescription( 'en', 'German city' );
		$fingerprint->setDescription( 'ru', 'столица и одновременно земля Германии' );

		$fingerprint->setAliasGroup( 'en', array( 'Berlin, Germany', 'Land Berlin' ) );
		$fingerprint->setAliasGroup( 'ru', array( 'Berlin' ) );

		return $fingerprint;
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
			'!<rdf:Description.*rdf:about=".*?/Q2"!s',
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
		$emitter = RdfSerializer::getRdfWriter( $formatName );
		$mockRepository = RdfBuilderTest::getMockRepository();

		foreach ( $this->getTestEntities() as $entity ) {
			$mockRepository->putEntity( $entity );
		}

		$serializer = new RdfSerializer(
			$emitter,
			RdfBuilderTest::URI_BASE,
			RdfBuilderTest::URI_DATA,
			RdfBuilderTest::getSiteList(),
			$mockRepository,
			$mockRepository,
			RdfSerializer::PRODUCE_ALL
		);
		return $serializer;
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
		$format = RdfSerializer::getRdfWriter( $name );

		$this->assertNotNull( $format, $name );
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
		$data = $serializer->startDocument();

		$data .= $serializer->serializeEntityRevision( $entityRevision );
		$data .= $serializer->finishDocument();

		foreach ( $regexes as $regex ) {
			$this->assertRegExp( $regex, $data );
		}
	}

}
