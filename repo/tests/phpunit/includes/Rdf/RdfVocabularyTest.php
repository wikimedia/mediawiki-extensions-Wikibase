<?php

namespace Wikibase\Repo\Tests\Rdf;

use DataValues\StringValue;
use OutOfBoundsException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
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
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class RdfVocabularyTest extends PHPUnit_Framework_TestCase {

	private function newInstance() {
		return new RdfVocabulary(
			[ '' => '<BASE>', 'foo' => '<BASE-foo>' ],
			[ '' => '<DATA>', 'foo' => '<DATA-foo>' ],
			array( 'German' => 'de' ),
			array( 'acme' => 'http://acme.test/vocab/ACME' )
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

	public function testGetCommonsURI() {
		$actual = $this->newInstance()->getCommonsURI( '!' );
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
		$this->assertContains( '<DATA-foo>', $actual );
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

	public function testGetEntityNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals( RdfVocabulary::NS_ENTITY, $vocabulary->getEntityNamespace( new ItemId( 'Q12' ) ) );
		$this->assertEquals( RdfVocabulary::NS_ENTITY . '-foo', $vocabulary->getEntityNamespace( new ItemId( 'foo:Q12' ) ) );
	}

	public function testGetDataNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals( RdfVocabulary::NS_DATA, $vocabulary->getDataNamespace( new ItemId( 'Q12' ) ) );
		$this->assertEquals( RdfVocabulary::NS_DATA . '-foo', $vocabulary->getDataNamespace( new ItemId( 'foo:Q12' ) ) );
	}

	public function testGetClaimPropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_CLAIM,
			$vocabulary->getClaimPropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_CLAIM . '-foo',
			$vocabulary->getClaimPropertyNamespace( new PropertyId( 'foo:P22' ) )
		);
	}

	public function testGetClaimStatementPropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_CLAIM_STATEMENT,
			$vocabulary->getClaimStatementPropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_CLAIM_STATEMENT . '-foo',
			$vocabulary->getClaimStatementPropertyNamespace( new PropertyId( 'foo:P22' ) )
		);
	}

	public function testGetClaimValuePropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_CLAIM_VALUE,
			$vocabulary->getClaimValuePropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_CLAIM_VALUE . '-foo',
			$vocabulary->getClaimValuePropertyNamespace( new PropertyId( 'foo:P22' ) )
		);
	}

	public function testGetClaimNormalizedValuePropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_CLAIM_VALUE_NORM,
			$vocabulary->getClaimNormalizedValuePropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_CLAIM_VALUE_NORM . '-foo',
			$vocabulary->getClaimNormalizedValuePropertyNamespace( new PropertyId( 'foo:P22' ) )
		);
	}

	public function testGetDirectClaimPropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_DIRECT_CLAIM,
			$vocabulary->getDirectClaimPropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_DIRECT_CLAIM . '-foo',
			$vocabulary->getDirectClaimPropertyNamespace( new PropertyId( 'foo:P22' ) )
		);
	}

	public function testGetQualifierPropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_QUALIFIER,
			$vocabulary->getQualifierPropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_QUALIFIER . '-foo',
			$vocabulary->getQualifierPropertyNamespace( new PropertyId( 'foo:P22' ) )
		);
	}

	public function testGetQualifierValuePropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_QUALIFIER_VALUE,
			$vocabulary->getQualifierValuePropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_QUALIFIER_VALUE . '-foo',
			$vocabulary->getQualifierValuePropertyNamespace( new PropertyId( 'foo:P22' ) )
		);
	}

	public function testGetQualifierNormalizedValuePropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_QUALIFIER_VALUE_NORM,
			$vocabulary->getQualifierNormalizedValuePropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_QUALIFIER_VALUE_NORM . '-foo',
			$vocabulary->getQualifierNormalizedValuePropertyNamespace( new PropertyId( 'foo:P22' ) )
		);
	}

	public function testGetReferencePropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_REFERENCE,
			$vocabulary->getReferencePropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_REFERENCE . '-foo',
			$vocabulary->getReferencePropertyNamespace( new PropertyId( 'foo:P22' ) )
		);
	}

	public function testGetReferenceValuePropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_REFERENCE_VALUE,
			$vocabulary->getReferenceValuePropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_REFERENCE_VALUE . '-foo',
			$vocabulary->getReferenceValuePropertyNamespace( new PropertyId( 'foo:P22' ) )
		);
	}

	public function testGetReferenceNormalizedValuePropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_REFERENCE_VALUE_NORM,
			$vocabulary->getReferenceNormalizedValuePropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_REFERENCE_VALUE_NORM . '-foo',
			$vocabulary->getReferenceNormalizedValuePropertyNamespace( new PropertyId( 'foo:P22' ) )
		);
	}

	public function testGetNoValuePropertyNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			RdfVocabulary::NSP_NOVALUE,
			$vocabulary->getNoValuePropertyNamespace( new PropertyId( 'P22' ) )
		);
		$this->assertEquals(
			RdfVocabulary::NSP_NOVALUE . '-foo',
			$vocabulary->getNoValuePropertyNamespace( new PropertyId( 'foo:P22' ) )
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
