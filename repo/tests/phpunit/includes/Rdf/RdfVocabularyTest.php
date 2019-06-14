<?php

namespace Wikibase\Repo\Tests\Rdf;

use DataValues\StringValue;
use OutOfBoundsException;
use PHPUnit4And6Compat;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Rdf\RdfVocabulary;

/**
 * @covers \Wikibase\Rdf\RdfVocabulary
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class RdfVocabularyTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testGivenConceptBaseUriNotDefinedForDefaultRepository_constructorThrowsException() {
		$this->expectException( \InvalidArgumentException::class );

		new RdfVocabulary(
			[ 'foo' => '<BASE-foo>' ],
			'<DATA>',
			new DataAccessSettings( 100, false, false, DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION ),
			new EntitySourceDefinitions( [] ),
			''
		);
	}

	public function testGivenNoConceptBaseUriDefinedForLocalEntitySource_constructorThrowsException() {
		$this->expectException( \InvalidArgumentException::class );

		new RdfVocabulary(
			[ 'foo' => '<BASE-foo>' ],
			'<DATA>',
			new DataAccessSettings( 100, false, false, DataAccessSettings::USE_ENTITY_SOURCE_BASED_FEDERATION ),
			new EntitySourceDefinitions( [
				new EntitySource( 'local', 'localdb', [ 'item' => [ 'namespaceId' => 1234, 'slot' => 'main' ] ], '<BASE>', 'wd', '', '' ),
				new EntitySource( 'foo', 'otherbd', [ 'property' => [ 'namespaceId' => 4321, 'slot' => 'main' ] ], '<BASE-foo>', 'other', 'other', '' ),
			] ),
			'local'
		);
	}

	private function newInstance() {
		return new RdfVocabulary(
			[ '' => '<BASE>', 'foo' => '<BASE-foo>' ],
			'<DATA>',
			new DataAccessSettings( 100, false, false, DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION ),
			new EntitySourceDefinitions( [] ),
			'',
			[ 'German' => 'de' ],
			[ 'acme' => 'http://acme.test/vocab/ACME' ],
			[],
			'http://cc0.test/'
		);
	}

	private function newInstanceForEntitySourceBasedFederation() {
		return new RdfVocabulary(
			[ 'localwiki' => '<BASE>', 'otherwiki' => '<BASE-other>' ],
			'<DATA>',
			new DataAccessSettings( 100, false, false, DataAccessSettings::USE_ENTITY_SOURCE_BASED_FEDERATION ),
			new EntitySourceDefinitions( [
				new EntitySource(
					'localwiki',
					'localdb',
					[ 'item' => [ 'namespaceId' => 1234, 'slot' => 'main' ] ],
					'<BASE>',
					'wd',
					'',
					''
				),
				new EntitySource(
					'otherwiki',
					'otherdb',
					[ 'property' => [ 'namespaceId' => 4321, 'slot' => 'main' ] ],
					'<BASE-other>',
					'other',
					'other',
					''
				),
			] ),
			'localwiki',
			[ 'German' => 'de' ],
			[ 'acme' => 'http://acme.test/vocab/ACME' ],
			[],
			'http://cc0.test/'
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

	public function testGetEntityLName_entitySourceBasedFederation() {
		$entityId = new PropertyId( 'P1' );
		$actual = $this->newInstanceForEntitySourceBasedFederation()->getEntityLName( $entityId );
		$this->assertSame( 'P1', $actual );
	}

	public function testGetRepositoryName_entityFromLocalSource_entitySourceBasedFederation() {
		$id = new ItemId( 'Q1' );
		$vocabulary = $this->newInstanceForEntitySourceBasedFederation();
		$this->assertSame( 'localwiki', $vocabulary->getEntityRepositoryName( $id ) );
	}

	public function testGetRepositoryName_entityFromNonLocalSource_entitySourceBasedFederation() {
		$id = new PropertyId( 'P1' );
		$vocabulary = $this->newInstanceForEntitySourceBasedFederation();
		$this->assertSame( 'otherwiki', $vocabulary->getEntityRepositoryName( $id ) );
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

	public function testGetNamespaces_entitySourceBasedFederation() {
		$actual = $this->newInstanceForEntitySourceBasedFederation()->getNamespaces();
		$this->assertInternalType( 'array', $actual );
		$this->assertContainsOnly( 'string', $actual );
		$this->assertContains( '<BASE>', $actual );
		$this->assertContains( '<DATA>', $actual );
		$this->assertContains( '<BASE-other>', $actual );
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

	public function testGetNamespaceURI_entitySourceBasedFederation() {
		$vocab = $this->newInstanceForEntitySourceBasedFederation();
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

	public function testEntityNamespaceNames_entitySourceBasedFederation() {
		$vocabulary = $this->newInstanceForEntitySourceBasedFederation();

		$this->assertEquals( [ 'localwiki' => 'wd', 'otherwiki' => 'wd-otherwiki' ], $vocabulary->entityNamespaceNames );
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

	public function testPropertyNamespaceNames_entitySourceBasedFederation() {
		$vocabulary = $this->newInstanceForEntitySourceBasedFederation();

		$this->assertEquals(
			[
				'localwiki' => [
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
				'otherwiki' => [
					'wdt' => 'wdt-otherwiki',
					'p' => 'p-otherwiki',
					'ps' => 'ps-otherwiki',
					'psv' => 'psv-otherwiki',
					'psn' => 'psn-otherwiki',
					'pq' => 'pq-otherwiki',
					'pqv' => 'pqv-otherwiki',
					'pqn' => 'pqn-otherwiki',
					'pr' => 'pr-otherwiki',
					'prv' => 'prv-otherwiki',
					'prn' => 'prn-otherwiki',
					'wdno' => 'wdno-otherwiki',
					'wdtn' => 'wdtn-otherwiki',
				],
			],
			$vocabulary->propertyNamespaceNames
		);
	}

	public function testClaimToValue() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			[
				'ps' => 'psv',
				'pq' => 'pqv',
				'pr' => 'prv',
				'ps-foo' => 'psv-foo',
				'pq-foo' => 'pqv-foo',
				'pr-foo' => 'prv-foo',
			],
			$vocabulary->claimToValue
		);
	}

	public function testClaimToValue_entitySourceBasedFederation() {
		$vocabulary = $this->newInstanceForEntitySourceBasedFederation();

		$this->assertEquals(
			[
				'ps' => 'psv',
				'pq' => 'pqv',
				'pr' => 'prv',
				'ps-otherwiki' => 'psv-otherwiki',
				'pq-otherwiki' => 'pqv-otherwiki',
				'pr-otherwiki' => 'prv-otherwiki',
			],
			$vocabulary->claimToValue
		);
	}

	public function testClaimToValueNormalized() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			[
				'ps' => 'psn',
				'pq' => 'pqn',
				'pr' => 'prn',
				'ps-foo' => 'psn-foo',
				'pq-foo' => 'pqn-foo',
				'pr-foo' => 'prn-foo',
			],
			$vocabulary->claimToValueNormalized
		);
	}

	public function testClaimToValueNormalized_entitySourceBasedFederation() {
		$vocabulary = $this->newInstanceForEntitySourceBasedFederation();

		$this->assertEquals(
			[
				'ps' => 'psn',
				'pq' => 'pqn',
				'pr' => 'prn',
				'ps-otherwiki' => 'psn-otherwiki',
				'pq-otherwiki' => 'pqn-otherwiki',
				'pr-otherwiki' => 'prn-otherwiki',
			],
			$vocabulary->claimToValueNormalized
		);
	}

	public function testNormalizedPropertyValueNamespace() {
		$vocabulary = $this->newInstance();

		$this->assertEquals(
			[
				'wdt' => 'wdtn',
				'ps' => 'psn',
				'pq' => 'pqn',
				'pr' => 'prn',
				'wdt-foo' => 'wdtn-foo',
				'ps-foo' => 'psn-foo',
				'pq-foo' => 'pqn-foo',
				'pr-foo' => 'prn-foo',
			],
			$vocabulary->normalizedPropertyValueNamespace
		);
	}

	public function testNormalizedPropertyValueNamespace_entitySourceBasedFederation() {
		$vocabulary = $this->newInstanceForEntitySourceBasedFederation();

		$this->assertEquals(
			[
				'wdt' => 'wdtn',
				'ps' => 'psn',
				'pq' => 'pqn',
				'pr' => 'prn',
				'wdt-otherwiki' => 'wdtn-otherwiki',
				'ps-otherwiki' => 'psn-otherwiki',
				'pq-otherwiki' => 'pqn-otherwiki',
				'pr-otherwiki' => 'prn-otherwiki',
			],
			$vocabulary->normalizedPropertyValueNamespace
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

	public function testGetLicenseUrl() {
		$actual = $this->newInstance()->getLicenseUrl();
		$this->assertSame( 'http://cc0.test/', $actual );
	}

}
