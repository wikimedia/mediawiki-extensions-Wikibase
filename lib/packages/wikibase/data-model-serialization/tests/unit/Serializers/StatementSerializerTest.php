<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Serializers\Serializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Serializers\StatementSerializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class StatementSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		$snakSerializerFake = $this->createMock( Serializer::class );
		$snakSerializerFake->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( [
				'snaktype' => 'novalue',
				'property' => "P42",
			] ) );

		$snaksSerializerFake = $this->createMock( Serializer::class );
		$snaksSerializerFake->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( [
				'P42' => [
					[
						'snaktype' => 'novalue',
						'property' => 'P42',
					],
				],
			] ) );

		$referencesSerializerFake = $this->createMock( Serializer::class );
		$referencesSerializerFake->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( [
				[
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => [],
				],
			] ) );

		return new StatementSerializer(
			$snakSerializerFake,
			$snaksSerializerFake,
			$referencesSerializerFake
		);
	}

	public function serializableProvider() {
		return [
			[
				new Statement( new PropertyNoValueSnak( 42 ) ),
			],
		];
	}

	public function nonSerializableProvider() {
		return [
			[
				5,
			],
			[
				[],
			],
			[
				new ItemId( 'Q42' ),
			],
		];
	}

	public function serializationProvider() {
		$serializations = [];

		$serializations[] = [
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
				'rank' => 'normal',
			],
			new Statement( new PropertyNoValueSnak( 42 ) ),
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'q42' );
		$serializations[] = [
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
				'id' => 'q42',
				'rank' => 'normal',
			],
			$statement,
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_PREFERRED );
		$serializations[] = [
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
				'rank' => 'preferred',
			],
			$statement,
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_DEPRECATED );
		$serializations[] = [
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
				'rank' => 'deprecated',
			],
			$statement,
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( [] ) );
		$serializations[] = [
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => "P42",
				],
				'type' => 'statement',
				'rank' => 'normal',
			],
			$statement,
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( [
			new PropertyNoValueSnak( 42 ),
		] ) );
		$serializations[] = [
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => "P42",
				],
				'type' => 'statement',
				'qualifiers' => [
					'P42' => [
						[
							'snaktype' => 'novalue',
							'property' => 'P42',
						],
					],
				],
				'qualifiers-order' => [
					'P42',
				],
				'rank' => 'normal',
			],
			$statement,
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setReferences( new ReferenceList( [
			new Reference( [ new PropertyNoValueSnak( 1 ) ] ),
		] ) );
		$serializations[] = [
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => "P42",
				],
				'type' => 'statement',
				'rank' => 'normal',
				'references' => [
					[
						'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
						'snaks' => [],
					],
				],
			],
			$statement,
		];

		return $serializations;
	}

	public function testQualifiersOrderSerialization() {
		$snakSerializerMock = $this->createMock( Serializer::class );
		$snakSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( [
				'snaktype' => 'novalue',
				'property' => 'P42',
			] ) );

		$snaksSerializerMock = $this->createMock( Serializer::class );
		$snaksSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( [] ) );

		$referencesSerializerMock = $this->createMock( Serializer::class );
		$statementSerializer = new StatementSerializer(
			$snakSerializerMock,
			$snaksSerializerMock,
			$referencesSerializerMock
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( [
			new PropertyNoValueSnak( 42 ),
			new PropertySomeValueSnak( 24 ),
			new PropertyNoValueSnak( 24 ),
		] ) );
		$this->assertEquals(
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'qualifiers' => [],
				'qualifiers-order' => [
					'P42',
					'P24',
				],
				'type' => 'statement',
				'rank' => 'normal',
			],
			$statementSerializer->serialize( $statement )
		);
	}

}
