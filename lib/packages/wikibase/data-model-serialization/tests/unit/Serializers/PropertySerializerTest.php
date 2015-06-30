<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\PropertySerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Serializers\PropertySerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertySerializerTest extends SerializerBaseTest {

	protected function buildSerializer() {
		$termListSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$termListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( function( TermList $termList ) {
				if ( $termList->isEmpty() ) {
					return array();
				}

				return array(
					'en' => array( 'lang' => 'en', 'value' => 'foo' )
				);
			} ) );

		$aliasGroupListSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$aliasGroupListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( function( AliasGroupList $aliasGroupList ) {
				if ( $aliasGroupList->isEmpty() ) {
					return array();
				}

				return array(
					'en' => array( 'lang' => 'en', 'values' => array( 'foo', 'bar' ) )
				);
			} ) );

		$statementListSerializerMock = $this->getMock( 'Serializers\Serializer' );
		$statementListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( function( StatementList $statementList ) {
				if ( $statementList->isEmpty() ) {
					return array();
				}

				return array(
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
				);
			} ) );

		return new PropertySerializer(
			$termListSerializerMock,
			$aliasGroupListSerializerMock,
			$statementListSerializerMock
		);
	}

	public function serializableProvider() {
		return array(
			array(
				Property::newFromType( 'string' )
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
				new Item()
			),
		);
	}

	public function serializationProvider() {
		$property = Property::newFromType( 'string' );

		$provider = array(
			array(
				array(
					'type' => 'property',
					'datatype' => 'string',
					'labels' => array(),
					'descriptions' => array(),
					'aliases' => array(),
					'claims' => array(),
				),
				$property
			),
		);

		$property = Property::newFromType( '' );
		$property->setId( 42 );
		$provider[] = array(
			array(
				'type' => 'property',
				'id' => 'P42',
				'datatype' => '',
				'claims' => array(),
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(),
			),
			$property
		);

		$property = Property::newFromType( '' );
		$property->getFingerprint()->setLabel( 'en', 'foo' );
		$provider[] = array(
			array(
				'type' => 'property',
				'datatype' => '',
				'claims' => array(),
				'labels' => array(
					'en' => array(
						'lang' => 'en',
						'value' => 'foo'
					)
				),
				'descriptions' => array(),
				'aliases' => array(),
			),
			$property
		);

		$property = Property::newFromType( '' );
		$property->getFingerprint()->setDescription( 'en', 'foo' );
		$provider[] = array(
			array(
				'type' => 'property',
				'datatype' => '',
				'claims' => array(),
				'labels' => array(),
				'descriptions' => array(
					'en' => array(
						'lang' => 'en',
						'value' => 'foo'
					)
				),
				'aliases' => array(),
			),
			$property
		);

		$property = Property::newFromType( '' );
		$property->getFingerprint()->setAliasGroup( 'en', array( 'foo', 'bar' ) );
		$provider[] = array(
			array(
				'type' => 'property',
				'datatype' => '',
				'claims' => array(),
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(
					'en' => array(
						'lang' => 'en',
						'values' => array( 'foo', 'bar' )
					)
				),
			),
			$property
		);

		$property = Property::newFromType( '' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = array(
			array(
				'type' => 'property',
				'datatype' => '',
				'claims' => array(
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
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(),
			),
			$property
		);

		return $provider;
	}

}
