<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Serialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertyPartsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PropertyParts;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PropertyPartsBuilder;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Serialization\PropertyPartsSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyPartsSerializerTest extends TestCase {

	private LabelsSerializer $labelsSerializer;
	private DescriptionsSerializer $descriptionsSerializer;
	private AliasesSerializer $aliasesSerializer;
	private StatementListSerializer $statementsSerializer;

	protected function setUp(): void {
		$this->labelsSerializer = $this->createStub( LabelsSerializer::class );
		$this->descriptionsSerializer = $this->createStub( DescriptionsSerializer::class );
		$this->aliasesSerializer = $this->createStub( AliasesSerializer::class );
		$this->statementsSerializer = $this->createStub( StatementListSerializer::class );
	}

	public function testSerialize(): void {
		$propertyId = 'P1';
		$dataType = 'string';
		$labels = $this->createStub( Labels::class );
		$expectedLabelsSerialization = new ArrayObject( [ 'en' => 'myLabel' ] );
		$descriptions = $this->createStub( Descriptions::class );
		$expectedDescriptionsSerialization = new ArrayObject( [ 'en' => 'myDescription' ] );
		$aliases = $this->createStub( Aliases::class );
		$expectedAliasesSerialization = new ArrayObject( [ 'en' => [ 'my', 'aliases' ] ] );
		$statements = $this->createStub( StatementList::class );
		$expectedStatementsSerialization = new ArrayObject( [ 'myStatements' ] );

		$this->labelsSerializer = $this->createMock( LabelsSerializer::class );
		$this->labelsSerializer
			->expects( $this->once() )
			->method( 'serialize' )
			->with( $labels )
			->willReturn( $expectedLabelsSerialization );

		$this->descriptionsSerializer = $this->createMock( DescriptionsSerializer::class );
		$this->descriptionsSerializer
			->expects( $this->once() )
			->method( 'serialize' )
			->with( $descriptions )
			->willReturn( $expectedDescriptionsSerialization );

		$this->aliasesSerializer = $this->createMock( AliasesSerializer::class );
		$this->aliasesSerializer
			->expects( $this->once() )
			->method( 'serialize' )
			->with( $aliases )
			->willReturn( $expectedAliasesSerialization );

		$this->statementsSerializer = $this->createStub( StatementListSerializer::class );
		$this->statementsSerializer
			->expects( $this->once() )
			->method( 'serialize' )
			->with( $statements )
			->willReturn( $expectedStatementsSerialization );

		$propertyParts = new PropertyParts(
			new NumericPropertyId( $propertyId ),
			PropertyParts::VALID_FIELDS,
			$dataType,
			$labels,
			$descriptions,
			$aliases,
			$statements
		);

		$this->assertEquals(
			$this->newSerializer()->serialize( $propertyParts ),
			[
				'id' => $propertyId,
				'type' => PropertyParts::TYPE,
				'data_type' => $dataType,
				'labels' => $expectedLabelsSerialization,
				'descriptions' => $expectedDescriptionsSerialization,
				'aliases' => $expectedAliasesSerialization,
				'statements' => $expectedStatementsSerialization,
			]
		);
	}

	/**
	 * @dataProvider propertyPartsFieldsProvider
	 */
	public function testSkipsFieldsThatAreNotSet( PropertyParts $propertyParts, array $fields ): void {
		$serialization = $this->newSerializer()->serialize( $propertyParts );
		$serializationFields = array_keys( $serialization );

		$this->assertEqualsCanonicalizing( $fields, $serializationFields );
	}

	public static function propertyPartsFieldsProvider(): Generator {
		yield [
			self::newPropertyPartsBuilderWithSomeId( [] )->build(),
			[ 'id' ],
		];
		yield [
			self::newPropertyPartsBuilderWithSomeId( [ PropertyParts::FIELD_TYPE ] )->build(),
			[ 'id', 'type' ],
		];
		yield [
			self::newPropertyPartsBuilderWithSomeId(
				[ PropertyParts::FIELD_LABELS, PropertyParts::FIELD_DESCRIPTIONS, PropertyParts::FIELD_ALIASES ]
			)
				->setLabels( new Labels() )
				->setDescriptions( new Descriptions() )
				->setAliases( new Aliases() )
				->build(),
			[ 'id', 'labels', 'descriptions', 'aliases' ],
		];
		yield [
			self::newPropertyPartsBuilderWithSomeId( [ PropertyParts::FIELD_STATEMENTS ] )
				->setStatements( new StatementList() )
				->build(),
			[ 'id', 'statements' ],
		];
		yield [
			self::newPropertyPartsBuilderWithSomeId( PropertyParts::VALID_FIELDS )
				->setDataType( 'string' )
				->setLabels( new Labels() )
				->setDescriptions( new Descriptions() )
				->setAliases( new Aliases() )
				->setStatements( new StatementList() )
				->build(),
			[ 'id', 'type', 'data_type', 'labels', 'descriptions', 'aliases', 'statements' ],
		];
	}

	private function newSerializer(): PropertyPartsSerializer {
		return new PropertyPartsSerializer(
			$this->labelsSerializer,
			$this->descriptionsSerializer,
			$this->aliasesSerializer,
			$this->statementsSerializer
		);
	}

	private static function newPropertyPartsBuilderWithSomeId( array $requestedFields ): PropertyPartsBuilder {
		return new PropertyPartsBuilder( new NumericPropertyId( 'P666' ), $requestedFields );
	}
}
