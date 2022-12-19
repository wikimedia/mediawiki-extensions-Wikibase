<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Serializers\Serializer;
use stdClass;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers Wikibase\DataModel\Serializers\StatementListSerializer
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class StatementListSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$statementSerializerMock = $this->createMock( Serializer::class );
		$statementSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( $statement ) )
			->will( $this->returnValue( [
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
				'rank' => 'normal',
			] ) );

		return new StatementListSerializer( $statementSerializerMock, false );
	}

	public function serializableProvider() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		return [
			[ new StatementList() ],
			[ new StatementList( $statement ) ],
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
				new Statement( new PropertyNoValueSnak( 42 ) ),
			],
		];
	}

	public function serializationProvider() {
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

	public function testStatementListSerializerWithOptionObjectsForMaps() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );
		$statementSerializerMock = $this->createMock( Serializer::class );
		$statementSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( $statement ) )
			->will( $this->returnValue( [
				'mockedsuff' => [],
				'type' => 'statement',
			] ) );
		$serializer = new StatementListSerializer( $statementSerializerMock, true );

		$statementList = new StatementList( $statement );

		$serial = new stdClass();
		$serial->P42 = [ [
			'mockedsuff' => [],
			'type' => 'statement',
		] ];
		$this->assertEquals( $serial, $serializer->serialize( $statementList ) );
	}

}
