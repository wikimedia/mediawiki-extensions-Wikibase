<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use ArrayObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDataSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyData;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\PropertyDataSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyDataSerializerTest extends TestCase {

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

		$propertyData = new PropertyData(
			new NumericPropertyId( $propertyId ),
			$dataType,
			$labels,
			$descriptions,
			$aliases,
			$statements
		);

		$this->assertEquals(
			$this->newSerializer()->serialize( $propertyData ),
			[
				'id' => $propertyId,
				'type' => PropertyData::TYPE,
				'data-type' => $dataType,
				'labels' => $expectedLabelsSerialization,
				'descriptions' => $expectedDescriptionsSerialization,
				'aliases' => $expectedAliasesSerialization,
				'statements' => $expectedStatementsSerialization,
			]
		);
	}

	private function newSerializer(): PropertyDataSerializer {
		return new PropertyDataSerializer(
			$this->labelsSerializer,
			$this->descriptionsSerializer,
			$this->aliasesSerializer,
			$this->statementsSerializer
		);
	}

}
