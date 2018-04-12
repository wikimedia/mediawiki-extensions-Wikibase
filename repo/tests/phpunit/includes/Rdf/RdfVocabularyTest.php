<?php

namespace Wikibase\Repo\Tests\Rdf;

use DataValues\StringValue;
use OutOfBoundsException;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Rdf\RdfVocabulary;

/**
 * @covers Wikibase\Rdf\RdfVocabulary
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class RdfVocabularyTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	private function newInstance() {
		return new RdfVocabulary(
			[ '' => '<BASE>', 'foo' => '<BASE-foo>' ],
			'<DATA>',
			[ 'German' => 'de' ],
			[ 'acme' => 'http://acme.test/vocab/ACME' ]
		);
	}

	public function testGetCanonicalLanguageCode_withNonStandardCode() {
		$actual = $this->newInstance()->getCanonicalLanguageCode( 'German' );
		$this->assertSame( 'de', $actual );
	}

	public function testGetCanonicalLanguageCode_withStandardCode() {
		$actual = $this->newInstance()->getCanonicalLanguageCode( 'DE-at-x-GIBBERISH' );
		$this->assertSame( 'de-AT-x-gibberish', $actual );
	}

	public function testGetMediaFileURI() {
		$actual = $this->newInstance()->getMediaFileURI( '!' );
		$this->assertSame( 'http://commons.wikimedia.org/wiki/Special:FilePath/%21', $actual );
	}

	public function testGetDataTypeURI() {
		$property = Property::newFromType( 'some-type' );
		$vocab = $this->newInstance();

		// test generic uri construction
		$actual = $vocab->getDataTypeURI( $property );
		$expected = $vocab->getNamespaceURI( RdfVocabulary::NS_ONTOLOGY ) . 'SomeType';
		$this->assertSame( $expected, $actual );

		// test a type for which we have explicitly defined a uri
		$property = Property::newFromType( 'acme' );
		$actual = $vocab->getDataTypeURI( $property );
		$this->assertSame( 'http://acme.test/vocab/ACME', $actual );
	}

	public function testGetEntityLName() {
		$entityId = new PropertyId( 'P1' );
		$actual = $this->newInstance()->getEntityLName( $entityId );
		$this->assertSame( 'P1', $actual );
	}

	public function testGetEntityLName_foreignEntity() {
		$entityId = new PropertyId( 'foo:P1' );
		$actual = $this->newInstance()->getEntityLName( $entityId );
		$this->assertSame( 'P1', $actual );
	}

	public function testGetEntityLName_foreignEntityMultiplePrefixes() {
		$entityId = new PropertyId( 'foo:bar:P1' );
		$actual = $this->newInstance()->getEntityLName( $entityId );
		$this->assertSame( 'bar.P1', $actual );
	}

	public function testGetEntityTypeName() {
		$actual = $this->newInstance()->getEntityTypeName( 'type' );
		$this->assertSame( 'Type', $actual );
	}

	public function testGetNamespaces() {
		$actual = $this->newInstance()->getNamespaces();
		$this->assertInternalType( 'array', $actual );
		$this->assertContainsOnly( 'string', $actual );
		$this->assertContains( '<BASE>', $actual );
		$this->assertContains( '<DATA>', $actual );
		$this->assertContains( '<BASE-foo>', $actual );
	}

	public function testGetNamespaceURI() {
		$vocab = $this->newInstance();
		$all = $vocab->getNamespaces();

		$this->assertEquals( '<DATA>', $vocab->getNamespaceURI( RdfVocabulary::NS_DATA ) );
		$this->assertEquals( '<BASE>', $vocab->getNamespaceURI( RdfVocabulary::NS_ENTITY ) );

		foreach ( $all as $ns => $uri ) {
			$this->assertEquals( $uri, $vocab->getNamespaceURI( $ns ) );
		}

		$this->setExpectedException( OutOfBoundsException::class );
		$vocab->getNamespaceURI( 'NonExistingNamespaceForGetNamespaceUriTest' );
	}

	public function testEntityNamespaceNames() {
		$vocabulary = $this->newInstance();

		$this->assertEquals( [ '' => 'wd', 'foo' => 'wd-foo' ], $vocabulary->entityNamespaceNames );
	}

	public function testPropertyNamespaceNames() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			[
				'' => [
					'wdt' => 'wdt',
					'p' => 'p',
					'ps' => 'ps',
					'psv' => 'psv',
					'psn' => 'psn',
					'pq' => 'pq',
					'pqv' => 'pqv',
					'pqn' => 'pqn',
					'pr' => 'pr',
					'prv' => 'prv',
					'prn' => 'prn',
					'wdno' => 'wdno',
					'wdtn' => 'wdtn',
				],
				'foo' => [
					'wdt' => 'wdt-foo',
					'p' => 'p-foo',
					'ps' => 'ps-foo',
					'psv' => 'psv-foo',
					'psn' => 'psn-foo',
					'pq' => 'pq-foo',
					'pqv' => 'pqv-foo',
					'pqn' => 'pqn-foo',
					'pr' => 'pr-foo',
					'prv' => 'prv-foo',
					'prn' => 'prn-foo',
					'wdno' => 'wdno-foo',
					'wdtn' => 'wdtn-foo',
				],
			],
			$vocabulary->propertyNamespaceNames
		);
	}

	public function testGetOntologyURI() {
		$actual = $this->newInstance()->getOntologyURI();
		$this->assertStringStartsWith( 'http://wikiba.se/ontology-', $actual );
		$this->assertStringEndsWith( '.owl', $actual );
	}

	public function testGetStatementLName() {
		$statement = new Statement( new PropertyNoValueSnak( 1 ), null, null, '<GUID>' );
		$actual = $this->newInstance()->getStatementLName( $statement );
		$this->assertSame( '-GUID-', $actual );
	}

	public function testGetValueTypeName() {
		$dataValue = new StringValue( '' );
		$actual = $this->newInstance()->getValueTypeName( $dataValue );
		$this->assertSame( 'StringValue', $actual );
	}

}
