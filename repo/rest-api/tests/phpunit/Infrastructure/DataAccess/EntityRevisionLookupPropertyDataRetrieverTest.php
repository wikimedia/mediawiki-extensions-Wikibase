<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as PropertyWriteModel;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\StatementList as StatementListWriteModel;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyParts;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyPartsBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupPropertyDataRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupPropertyDataRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupPropertyDataRetrieverTest extends TestCase {

	use StatementReadModelHelper;

	private EntityRevisionLookup $entityRevisionLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->entityRevisionLookup = $this->createStub( EntityRevisionLookup::class );
	}

	public function testGetPropertyParts(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$expectedStatement = NewStatement::someValueFor( 'P321' )
			->withGuid( 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();
		$property = new PropertyWriteModel(
			$propertyId,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'potato' ) ] ),
				new TermList( [ new Term( 'en', 'root vegetable' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'spud', 'tater' ] ) ] )
			),
			'string',
			new StatementListWriteModel( $expectedStatement )
		);

		$this->entityRevisionLookup = $this->createStub( EntityRevisionLookup::class );
		$this->entityRevisionLookup->method( 'getEntityRevision' )
			->willReturn( new EntityRevision( $property, 123, '20201010998877' ) );

		$propertyParts = $this->newRetriever()->getPropertyParts( $propertyId, PropertyParts::VALID_FIELDS );

		$this->assertSame( $propertyId, $propertyParts->getId() );
		$this->assertEquals( Labels::fromTermList( $property->getLabels() ), $propertyParts->getLabels() );
		$this->assertEquals( Descriptions::fromTermList( $property->getDescriptions() ), $propertyParts->getDescriptions() );
		$this->assertEquals( Aliases::fromAliasGroupList( $property->getAliasGroups() ), $propertyParts->getAliases() );
		$this->assertEquals(
			new StatementList( $this->newStatementReadModelConverter()->convert( $expectedStatement ) ),
			$propertyParts->getStatements()
		);
	}

	public function testGivenPropertyDoesNotExist_getPropertyPartsReturnsNull(): void {
		$propertyId = new NumericPropertyId( 'P234' );
		$this->entityRevisionLookup = $this->createStub( EntityRevisionLookup::class );
		$this->entityRevisionLookup->method( 'getEntityRevision' )
			->willReturn( null );

		$this->assertNull( $this->newRetriever()->getPropertyParts( $propertyId, PropertyParts::VALID_FIELDS ) );
	}

	/**
	 * @dataProvider propertyPartsWithFieldsProvider
	 */
	public function testGivenFields_getPropertyPartsReturnsOnlyRequestFields(
		PropertyWriteModel $property,
		array $fields,
		PropertyParts $propertyParts
	): void {
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $property->getId(), $property );

		$this->assertEquals(
			$propertyParts,
			$this->newRetriever()->getPropertyParts( $property->getId(), $fields )
		);
	}

	public function propertyPartsWithFieldsProvider(): Generator {
		$statement = NewStatement::someValueFor( 'P123' )
			->withGuid( 'P666$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();

		$property = new PropertyWriteModel(
			new NumericPropertyId( 'P666' ),
			new Fingerprint(
				new TermList( [ new Term( 'en', 'potato' ) ] ),
				new TermList( [ new Term( 'en', 'root vegetable' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'spud', 'tater' ] ) ] )
			),
			'wikibase-item',
			new StatementListWriteModel( $statement )
		);

		$fields = [ PropertyParts::FIELD_LABELS, PropertyParts::FIELD_DESCRIPTIONS, PropertyParts::FIELD_ALIASES ];

		yield 'labels, descriptions, aliases' => [
			$property,
			$fields,
			( new PropertyPartsBuilder( $property->getId(), $fields ) )
				->setLabels( Labels::fromTermList( $property->getLabels() ) )
				->setDescriptions( Descriptions::fromTermList( $property->getDescriptions() ) )
				->setAliases( Aliases::fromAliasGroupList( $property->getAliasGroups() ) )
				->build(),
		];

		yield 'statements only' => [
			$property,
			[ PropertyParts::FIELD_STATEMENTS ],
			( new PropertyPartsBuilder( $property->getId(), [ PropertyParts::FIELD_STATEMENTS ] ) )
				->setStatements( new StatementList( $this->newStatementReadModelConverter()->convert( $statement ) ) )
				->build(),
		];

		yield 'all fields' => [
			$property,
			PropertyParts::VALID_FIELDS,
			( new PropertyPartsBuilder( $property->getId(), PropertyParts::VALID_FIELDS ) )
				->setDataType( $property->getDataTypeId() )
				->setLabels( Labels::fromTermList( $property->getLabels() ) )
				->setDescriptions( Descriptions::fromTermList( $property->getDescriptions() ) )
				->setAliases( Aliases::fromAliasGroupList( $property->getAliasGroups() ) )
				->setStatements( new StatementList( $this->newStatementReadModelConverter()->convert( $statement ) ) )
				->build(),
		];
	}

	public function testGetPropertyWriteModel(): void {
		$id = new NumericPropertyId( 'P123' );
		$expectedProperty = new PropertyWriteModel(
			$id,
			null,
			'string'
		);
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue(
			$id,
			$expectedProperty
		);

		$this->assertSame( $expectedProperty, $this->newRetriever()->getPropertyWriteModel( $id ) );
	}

	public function testGivenPropertyNotFound_getPropertyWriteModelReturnsNull(): void {
		$id = new NumericPropertyId( 'P123' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $id, null );

		$this->assertNull( $this->newRetriever()->getPropertyWriteModel( $id ) );
	}

	public function testGetProperty(): void {
		$id = new NumericPropertyId( 'P123' );
		$propertyWriteModel = new PropertyWriteModel(
			$id,
			null,
			'string'
		);
		$expectedProperty = new Property(
			$id,
			'string',
			new Labels(),
			new Descriptions(),
			new Aliases(),
			new StatementList()
		);
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $id, $propertyWriteModel );

		$this->assertEquals( $expectedProperty, $this->newRetriever()->getProperty( $id ) );
	}

	public function testGivenPropertyNotFound_getPropertyReturnsNull(): void {
		$id = new NumericPropertyId( 'P123' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $id, null );

		$this->assertNull( $this->newRetriever()->getProperty( $id ) );
	}

	public function testGetStatements(): void {
		$statement1 = NewStatement::forProperty( 'P123' )
			->withGuid( 'P666$c48c32c3-42b5-498f-9586-84608b88747c' )
			->withValue( 'potato' )
			->build();
		$statement2 = NewStatement::forProperty( 'P321' )
			->withGuid( 'P666$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->withValue( 'banana' )
			->build();

		$property = new PropertyWriteModel(
			new NumericPropertyId( 'P666' ),
			new Fingerprint(
				new TermList( [ new Term( 'en', 'potato' ) ] ),
				new TermList( [ new Term( 'en', 'root vegetable' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'spud', 'tater' ] ) ] )
			),
			'wikibase-item',
			new StatementListWriteModel( $statement1, $statement2 )
		);

		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $property->getId(), $property );

		$this->assertEquals(
			new StatementList(
				$this->newStatementReadModelConverter()->convert( $statement1 ),
				$this->newStatementReadModelConverter()->convert( $statement2 )
			),
			$this->newRetriever()->getStatements( $property->getId() )
		);
	}

	public function testGivenFilterProperty_getStatementsReturnsStatementGroup(): void {
		$statement1 = NewStatement::forProperty( 'P123' )
			->withGuid( 'P666$c48c32c3-42b5-498f-9586-84608b88747c' )
			->withValue( 'potato' )
			->build();
		$statement2 = NewStatement::forProperty( 'P321' )
			->withGuid( 'P666$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->withValue( 'banana' )
			->build();

		$property = new PropertyWriteModel(
			new NumericPropertyId( 'P666' ),
			new Fingerprint(
				new TermList( [ new Term( 'en', 'potato' ) ] ),
				new TermList( [ new Term( 'en', 'root vegetable' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'spud', 'tater' ] ) ] )
			),
			'wikibase-item',
			new StatementListWriteModel( $statement1, $statement2 )
		);

		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $property->getId(), $property );

		$this->assertEquals(
			new StatementList( $this->newStatementReadModelConverter()->convert( $statement1 ) ),
			$this->newRetriever()->getStatements( $property->getId(), new NumericPropertyId( 'P123' ) )
		);
	}

	public function testGivenPropertyDoesNotExist_getStatementsReturnsNull(): void {
		$nonexistentPropertyId = new NumericPropertyId( 'P321' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $nonexistentPropertyId, null );

		$this->assertNull( $this->newRetriever()->getStatements( $nonexistentPropertyId ) );
	}

	private function newRetriever(): EntityRevisionLookupPropertyDataRetriever {
		return new EntityRevisionLookupPropertyDataRetriever(
			$this->entityRevisionLookup,
			$this->newStatementReadModelConverter()
		);
	}

	private function newEntityRevisionLookupForIdWithReturnValue( PropertyId $id, ?PropertyWriteModel $returnValue ): EntityRevisionLookup {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $id )
			->willReturn( $returnValue ? new EntityRevision( $returnValue ) : null );

		return $entityRevisionLookup;
	}

}
