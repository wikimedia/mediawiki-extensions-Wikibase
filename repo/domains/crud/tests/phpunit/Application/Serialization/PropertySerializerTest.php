<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertySerializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\PropertySerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertySerializerTest extends TestCase {

	private StatementListSerializer $statementsSerializer;

	protected function setUp(): void {
		parent::setUp();

		$this->statementsSerializer = $this->createStub( StatementListSerializer::class );
	}

	public function testSerialize(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$property = new Property(
			$propertyId,
			'wikibase-item',
			new Labels( new Label( 'en', 'en label' ) ),
			new Descriptions( new Description( 'en', 'en description' ) ),
			new Aliases( new AliasesInLanguage( 'en', [ 'en alias' ] ) ),
			new StatementList()
		);
		$statementSerialization = new ArrayObject();
		$expectedSerialization = [
			'id' => "$propertyId",
			'type' => 'property',
			'data_type' => 'wikibase-item',
			'labels' => new ArrayObject( [ 'en' => 'en label' ] ),
			'descriptions' => new ArrayObject( [ 'en' => 'en description' ] ),
			'aliases' => new ArrayObject( [ 'en' => [ 'en alias' ] ] ),
			'statements' => $statementSerialization,
		];

		$this->statementsSerializer = $this->createStub( StatementListSerializer::class );
		$this->statementsSerializer->method( 'serialize' )->willReturn( $statementSerialization );

		$this->assertEquals( $expectedSerialization, $this->newPropertySerializer()->serialize( $property ) );
	}

	private function newPropertySerializer(): PropertySerializer {
		return new PropertySerializer(
			new LabelsSerializer(),
			new DescriptionsSerializer(),
			new AliasesSerializer(),
			$this->statementsSerializer
		);
	}

}
