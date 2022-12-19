<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Deserializers\PropertyDeserializer;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Deserializers\PropertyDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyDeserializerTest extends DispatchableDeserializerTest {

	protected function buildDeserializer() {
		$entityIdDeserializerMock = $this->createMock( Deserializer::class );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( 'P42' ) )
			->will( $this->returnValue( new NumericPropertyId( 'P42' ) ) );

		$termListDeserializerMock = $this->createMock( Deserializer::class );
		$termListDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
				'en' => [
					'lang' => 'en',
					'value' => 'foo',
				],
			] ) )
			->will( $this->returnValue( new TermList( [ new Term( 'en', 'foo' ) ] ) ) );

		$aliasGroupListDeserializerMock = $this->createMock( Deserializer::class );
		$aliasGroupListDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
				'en' => [
					'lang' => 'en',
					'values' => [ 'foo', 'bar' ],
				],
			] ) )
			->will( $this->returnValue(
				new AliasGroupList( [ new AliasGroup( 'en', [ 'foo', 'bar' ] ) ] ) )
			);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$statementListDeserializerMock = $this->createMock( Deserializer::class );
		$statementListDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
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
			] ) )
			->will( $this->returnValue( new StatementList( $statement ) ) );

		return new PropertyDeserializer(
			$entityIdDeserializerMock,
			$termListDeserializerMock,
			$aliasGroupListDeserializerMock,
			$statementListDeserializerMock
		);
	}

	public function deserializableProvider() {
		return [
			[
				[
					'type' => 'property',
				],
			],
		];
	}

	public function nonDeserializableProvider() {
		return [
			[
				5,
			],
			[
				[],
			],
			[
				[
					'type' => 'item',
				],
			],
		];
	}

	public function deserializationProvider() {
		$property = Property::newFromType( 'string' );

		$provider = [
			[
				$property,
				[
					'type' => 'property',
					'datatype' => 'string',
				],
			],
		];

		$property = new Property( new NumericPropertyId( 'P42' ), null, 'string' );
		$provider[] = [
			$property,
			[
				'type' => 'property',
				'datatype' => 'string',
				'id' => 'P42',
			],
		];

		$property = Property::newFromType( 'string' );
		$property->setLabel( 'en', 'foo' );
		$provider[] = [
			$property,
			[
				'type' => 'property',
				'datatype' => 'string',
				'labels' => [
					'en' => [
						'lang' => 'en',
						'value' => 'foo',
					],
				],
			],
		];

		$property = Property::newFromType( 'string' );
		$property->setDescription( 'en', 'foo' );
		$provider[] = [
			$property,
			[
				'type' => 'property',
				'datatype' => 'string',
				'descriptions' => [
					'en' => [
						'lang' => 'en',
						'value' => 'foo',
					],
				],
			],
		];

		$property = Property::newFromType( 'string' );
		$property->setAliases( 'en', [ 'foo', 'bar' ] );
		$provider[] = [
			$property,
			[
				'type' => 'property',
				'datatype' => 'string',
				'aliases' => [
					'en' => [
						'lang' => 'en',
						'values' => [ 'foo', 'bar' ],
					],
				],
			],
		];

		$property = Property::newFromType( 'string' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = [
			$property,
			[
				'type' => 'property',
				'datatype' => 'string',
				'claims' => [
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
			],
		];

		$property = Property::newFromType( 'string' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = [
			$property,
			[
				'type' => 'property',
				'datatype' => 'string',
				'claims' => [
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
			],
		];

		return $provider;
	}

}
