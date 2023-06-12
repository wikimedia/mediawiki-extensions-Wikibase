<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyPartsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyParts;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyPartsBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\PropertyPartsSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyPartsSerializerTest extends TestCase {

	/**
	 * @var MockObject|LabelsSerializer
	 */
	private $labelsSerializer;

	/**
	 * @var MockObject|DescriptionsSerializer
	 */
	private $descriptionsSerializer;

	/**
	 * @var MockObject|AliasesSerializer
	 */
	private $aliasesSerializer;

	/**
	 * @var MockObject|StatementListSerializer
	 */
	private $statementsSerializer;

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
				'data-type' => $dataType,
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

	public function propertyPartsFieldsProvider(): Generator {
		yield [
			$this->newPropertyPartsBuilderWithSomeId( [] )->build(),
			[ 'id' ],
		];
		yield [
			$this->newPropertyPartsBuilderWithSomeId( [ PropertyParts::FIELD_TYPE ] )->build(),
			[ 'id', 'type' ],
		];
		yield [
			$this->newPropertyPartsBuilderWithSomeId(
				[ PropertyParts::FIELD_LABELS, PropertyParts::FIELD_DESCRIPTIONS, PropertyParts::FIELD_ALIASES ]
			)
				->setLabels( new Labels() )
				->setDescriptions( new Descriptions() )
				->setAliases( new Aliases() )
				->build(),
			[ 'id', 'labels', 'descriptions', 'aliases' ],
		];
		yield [
			$this->newPropertyPartsBuilderWithSomeId( [ PropertyParts::FIELD_STATEMENTS ] )
				->setStatements( new StatementList() )
				->build(),
			[ 'id', 'statements' ],
		];
		yield [
			$this->newPropertyPartsBuilderWithSomeId( PropertyParts::VALID_FIELDS )
				->setDataType( 'string' )
				->setLabels( new Labels() )
				->setDescriptions( new Descriptions() )
				->setAliases( new Aliases() )
				->setStatements( new StatementList() )
				->build(),
			[ 'id', 'type', 'data-type', 'labels', 'descriptions', 'aliases', 'statements' ],
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

	private function newPropertyPartsBuilderWithSomeId( array $requestedFields ): PropertyPartsBuilder {
		return new PropertyPartsBuilder( new NumericPropertyId( 'P666' ), $requestedFields );
	}
}
