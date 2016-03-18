<?php

namespace Tests\Wikibase\DataModel\Serializers;

use stdClass;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers Wikibase\DataModel\Serializers\StatementListSerializer
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class StatementListSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$statementSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$statementSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( $statement ) )
			->will( $this->returnValue( array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'normal'
			) ) );

		return new StatementListSerializer( $statementSerializerMock, false );
	}

	public function serializableProvider() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		return array(
			array(
				new StatementList()
			),
			array(
				new StatementList( array(
					$statement
				) )
			),
		);
	}

	public function nonSerializableProvider() {
		return array(
			array(
				5
			),
			array(
				array()
			),
			array(
				new Statement( new PropertyNoValueSnak( 42 ) )
			),
		);
	}

	public function serializationProvider() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		return array(
			array(
				array(),
				new StatementList()
			),
			array(
				array(
					'P42' => array(
						array(
							'mainsnak' => array(
								'snaktype' => 'novalue',
								'property' => 'P42'
							),
							'type' => 'statement',
							'rank' => 'normal'
						)
					)
				),
				new StatementList( array(
					$statement
				) )
			),
		);
	}

	public function testStatementListSerializerWithOptionObjectsForMaps() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );
		$statementSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$statementSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( $statement ) )
			->will( $this->returnValue( array(
				'mockedsuff' => array(),
				'type' => 'statement',
			) ) );
		$serializer = new StatementListSerializer( $statementSerializerMock, true );

		$statementList = new StatementList( array( $statement ) );

		$serial = new stdClass();
		$serial->P42 = array( array(
			'mockedsuff' => array(),
			'type' => 'statement',
		) );
		$this->assertEquals( $serial, $serializer->serialize( $statementList ) );
	}

}
