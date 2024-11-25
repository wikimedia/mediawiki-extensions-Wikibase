<?php

declare( strict_types = 1 );

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers Wikibase\DataModel\Serializers\StatementListSerializer
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class StatementListSerializerTest extends DispatchableSerializerTestCase {

	protected function buildSerializer( bool $useObjectsForEmptyMaps = false ): StatementListSerializer {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$statementSerializerMock = $this->createMock( StatementSerializer::class );
		$statementSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $statement )
			->willReturn( [
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
				'rank' => 'normal',
			] );

		return new StatementListSerializer( $statementSerializerMock, $useObjectsForEmptyMaps );
	}

	public static function serializableProvider(): array {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		return [
			[ new StatementList() ],
			[ new StatementList( $statement ) ],
		];
	}

	public static function nonSerializableProvider(): array {
		return [
			[
				5,
			],
			[
				[],
			],
			[
				new Statement( new PropertyNoValueSnak( 42 ) ),
			],
		];
	}

	public static function serializationProvider(): array {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		return [
			[
				[],
				new StatementList(),
			],
			[
				[
					'P42' => [
						[
							'mainsnak' => [
								'snaktype' => 'novalue',
								'property' => 'P42',
							],
							'type' => 'statement',
							'rank' => 'normal',
						],
					],
				],
				new StatementList( $statement ),
			],
		];
	}

	public function testStatementListSerializerWithoutOptionUseObjectsForEmptyMaps(): void {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );
		$statementSerializerMock = $this->createMock( StatementSerializer::class );
		$statementSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $statement )
			->willReturn( [
				'mockedsuff' => [],
				'type' => 'statement',
			] );
		$serializer = new StatementListSerializer( $statementSerializerMock, false );

		$statementList = new StatementList( $statement );

		$serial = [];
		$serial['P42'] = [ [
			'mockedsuff' => [],
			'type' => 'statement',
		] ];
		$this->assertEquals( $serial, $serializer->serialize( $statementList ) );
	}

	public function testStatementListSerializerSerializesEmptyList(): void {
		$statementSerializerMock = $this->createMock( StatementSerializer::class );
		$serializer = new StatementListSerializer( $statementSerializerMock, false );

		$this->assertEquals( [], $serializer->serialize( new StatementList() ) );
	}

	public function testStatementListSerializerUseObjectForEmptyListWhenFlagIsSet(): void {
		$statementSerializerMock = $this->createMock( StatementSerializer::class );
		$serializer = new StatementListSerializer( $statementSerializerMock, true );

		$this->assertEquals( (object)[], $serializer->serialize( new StatementList() ) );
	}
}
