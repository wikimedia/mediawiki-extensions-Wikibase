<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyData;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyDataBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\PropertyDataBuilder
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\PropertyData
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyDataBuilderTest extends TestCase {

	public function testId(): void {
		$id = new NumericPropertyId( 'P123' );
		$propertyParts = ( new PropertyDataBuilder( $id, [] ) )
			->build();
		$this->assertSame( $id, $propertyParts->getId() );
	}

	public function testDataType(): void {
		$dataType = 'wikibase-item';
		$propertyParts = $this->newBuilderWithSomeId( [ PropertyData::FIELD_DATA_TYPE ] )
			->setDataType( $dataType )
			->build();
		$this->assertSame( $dataType, $propertyParts->getDataType() );
	}

	public function testLabels(): void {
		$labels = new Labels( new Label( 'en', 'potato' ) );
		$propertyParts = $this->newBuilderWithSomeId( [ PropertyData::FIELD_LABELS ] )
			->setLabels( $labels )
			->build();
		$this->assertSame( $labels, $propertyParts->getLabels() );
	}

	public function testDescriptions(): void {
		$descriptions = new Descriptions( new Description( 'en', 'root vegetable' ) );
		$propertyParts = $this->newBuilderWithSomeId( [ PropertyData::FIELD_DESCRIPTIONS ] )
			->setDescriptions( $descriptions )
			->build();
		$this->assertSame( $descriptions, $propertyParts->getDescriptions() );
	}

	public function testAliases(): void {
		$aliases = new Aliases();
		$propertyParts = $this->newBuilderWithSomeId( [ PropertyData::FIELD_ALIASES ] )
			->setAliases( $aliases )
			->build();
		$this->assertSame( $aliases, $propertyParts->getAliases() );
	}

	public function testStatements(): void {
		$statements = new StatementList();
		$propertyParts = $this->newBuilderWithSomeId( [ PropertyData::FIELD_STATEMENTS ] )
			->setStatements( $statements )
			->build();
		$this->assertSame( $statements, $propertyParts->getStatements() );
	}

	public function testAll(): void {
		$dataType = 'wikibase-item';
		$labels = new Labels( new Label( 'en', 'potato' ) );
		$descriptions = new Descriptions( new Description( 'en', 'root vegetable' ) );
		$aliases = new Aliases();
		$statements = new StatementList();

		$propertyParts = $this->newBuilderWithSomeId( PropertyData::VALID_FIELDS )
			->setDataType( $dataType )
			->setLabels( $labels )
			->setDescriptions( $descriptions )
			->setAliases( $aliases )
			->setStatements( $statements )
			->build();

		$this->assertSame( $dataType, $propertyParts->getDataType() );
		$this->assertSame( $labels, $propertyParts->getLabels() );
		$this->assertSame( $descriptions, $propertyParts->getDescriptions() );
		$this->assertSame( $aliases, $propertyParts->getAliases() );
		$this->assertSame( $statements, $propertyParts->getStatements() );
	}

	/**
	 * @dataProvider nonRequiredFields
	 *
	 * @param mixed $param
	 */
	public function testNonRequiredField( string $field, string $setterFunction, $param ): void {
		$builder = $this->newBuilderWithSomeId( [] );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'cannot set unrequested ' . PropertyData::class . " field '$field'" );
		$builder->$setterFunction( $param )->build();
	}

	public function nonRequiredFields(): Generator {
		yield 'data-type' => [
			PropertyData::FIELD_DATA_TYPE,
			'setDataType',
			'wikibase-item',
		];

		yield 'labels' => [
			PropertyData::FIELD_LABELS,
			'setLabels',
			new Labels( new Label( 'en', 'potato' ) ),
		];

		yield 'descriptions' => [
			PropertyData::FIELD_DESCRIPTIONS,
			'setDescriptions',
			new Descriptions( new Description( 'en', 'root vegetable' ) ),
		];

		yield 'aliases' => [
			PropertyData::FIELD_ALIASES,
			'setAliases',
			new Aliases(),
		];

		yield 'statements' => [
			PropertyData::FIELD_STATEMENTS,
			'setStatements',
			new StatementList(),
		];
	}

	private function newBuilderWithSomeId( array $requestedFields ): PropertyDataBuilder {
		return ( new PropertyDataBuilder( new NumericPropertyId( 'P666' ), $requestedFields ) );
	}
}
