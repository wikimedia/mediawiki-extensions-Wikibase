<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Serializers\Serializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\PropertySerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Serializers\PropertySerializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertySerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		$termListSerializerMock = $this->createMock( Serializer::class );
		$termListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( static function( TermList $termList ) {
				if ( $termList->isEmpty() ) {
					return [];
				}

				return [
					'en' => [ 'lang' => 'en', 'value' => 'foo' ],
				];
			} ) );

		$aliasGroupListSerializerMock = $this->createMock( Serializer::class );
		$aliasGroupListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( static function( AliasGroupList $aliasGroupList ) {
				if ( $aliasGroupList->isEmpty() ) {
					return [];
				}

				return [
					'en' => [ 'lang' => 'en', 'values' => [ 'foo', 'bar' ] ],
				];
			} ) );

		$statementListSerializerMock = $this->createMock( Serializer::class );
		$statementListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( static function( StatementList $statementList ) {
				if ( $statementList->isEmpty() ) {
					return [];
				}

				return [
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
				];
			} ) );

		return new PropertySerializer(
			$termListSerializerMock,
			$aliasGroupListSerializerMock,
			$statementListSerializerMock
		);
	}

	public function serializableProvider() {
		return [
			[
				Property::newFromType( 'string' ),
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
				new Item(),
			],
		];
	}

	public function serializationProvider() {
		$property = Property::newFromType( 'string' );

		$provider = [
			[
				[
					'type' => 'property',
					'datatype' => 'string',
					'labels' => [],
					'descriptions' => [],
					'aliases' => [],
					'claims' => [],
				],
				$property,
			],
		];

		$property = new Property( new NumericPropertyId( 'P42' ), null, 'string' );
		$provider[] = [
			[
				'type' => 'property',
				'datatype' => 'string',
				'id' => 'P42',
				'labels' => [],
				'descriptions' => [],
				'aliases' => [],
				'claims' => [],
			],
			$property,
		];

		$property = Property::newFromType( 'string' );
		$property->setLabel( 'en', 'foo' );
		$provider[] = [
			[
				'type' => 'property',
				'datatype' => 'string',
				'labels' => [
					'en' => [
						'lang' => 'en',
						'value' => 'foo',
					],
				],
				'descriptions' => [],
				'aliases' => [],
				'claims' => [],
			],
			$property,
		];

		$property = Property::newFromType( 'string' );
		$property->setDescription( 'en', 'foo' );
		$provider[] = [
			[
				'type' => 'property',
				'datatype' => 'string',
				'labels' => [],
				'descriptions' => [
					'en' => [
						'lang' => 'en',
						'value' => 'foo',
					],
				],
				'aliases' => [],
				'claims' => [],
			],
			$property,
		];

		$property = Property::newFromType( 'string' );
		$property->setAliases( 'en', [ 'foo', 'bar' ] );
		$provider[] = [
			[
				'type' => 'property',
				'datatype' => 'string',
				'labels' => [],
				'descriptions' => [],
				'aliases' => [
					'en' => [
						'lang' => 'en',
						'values' => [ 'foo', 'bar' ],
					],
				],
				'claims' => [],
			],
			$property,
		];

		$property = Property::newFromType( 'string' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = [
			[
				'type' => 'property',
				'datatype' => 'string',
				'labels' => [],
				'descriptions' => [],
				'aliases' => [],
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
			$property,
		];

		return $provider;
	}

}
