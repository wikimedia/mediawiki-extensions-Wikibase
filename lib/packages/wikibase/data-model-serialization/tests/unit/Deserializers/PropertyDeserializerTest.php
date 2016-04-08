<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\PropertyDeserializer;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
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
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyDeserializerTest extends DispatchableDeserializerTest {

	protected function buildDeserializer() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( 'P42' ) )
			->will( $this->returnValue( new PropertyId( 'P42' ) ) );

		$termListDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$termListDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'en' => array(
					'lang' => 'en',
					'value' => 'foo'
				)
			) ) )
			->will( $this->returnValue( new TermList( array( new Term( 'en', 'foo' ) ) ) ) );

		$aliasGroupListDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$aliasGroupListDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'en' => array(
					'lang' => 'en',
					'values' => array( 'foo', 'bar' )
				)
			) ) )
			->will( $this->returnValue( new AliasGroupList( array( new AliasGroup( 'en', array( 'foo', 'bar' ) ) ) ) ) );

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$statementListDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$statementListDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
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
			) ) )
			->will( $this->returnValue( new StatementList( array( $statement ) ) ) );

		return new PropertyDeserializer(
			$entityIdDeserializerMock,
			$termListDeserializerMock,
			$aliasGroupListDeserializerMock,
			$statementListDeserializerMock
		);
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'type' => 'property'
				)
			),
		);
	}

	public function nonDeserializableProvider() {
		return array(
			array(
				5
			),
			array(
				array()
			),
			array(
				array(
					'type' => 'item'
				)
			),
		);
	}

	public function deserializationProvider() {
		$property = Property::newFromType( 'string' );

		$provider = array(
			array(
				$property,
				array(
					'type' => 'property',
					'datatype' => 'string'
				)
			),
		);

		$property = new Property( new PropertyId( 'P42' ), null, 'string' );
		$provider[] = array(
			$property,
			array(
				'type' => 'property',
				'datatype' => 'string',
				'id' => 'P42'
			)
		);

		$property = Property::newFromType( 'string' );
		$property->getFingerprint()->setLabel( 'en', 'foo' );
		$provider[] = array(
			$property,
			array(
				'type' => 'property',
				'datatype' => 'string',
				'labels' => array(
					'en' => array(
						'lang' => 'en',
						'value' => 'foo'
					)
				)
			)
		);

		$property = Property::newFromType( 'string' );
		$property->getFingerprint()->setDescription( 'en', 'foo' );
		$provider[] = array(
			$property,
			array(
				'type' => 'property',
				'datatype' => 'string',
				'descriptions' => array(
					'en' => array(
						'lang' => 'en',
						'value' => 'foo'
					)
				)
			)
		);

		$property = Property::newFromType( 'string' );
		$property->getFingerprint()->setAliasGroup( 'en', array( 'foo', 'bar' ) );
		$provider[] = array(
			$property,
			array(
				'type' => 'property',
				'datatype' => 'string',
				'aliases' => array(
					'en' => array(
						'lang' => 'en',
						'values' => array( 'foo', 'bar' )
					)
				)
			)
		);

		$property = Property::newFromType( 'string' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = array(
			$property,
			array(
				'type' => 'property',
				'datatype' => 'string',
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
				)
			)
		);

		$property = Property::newFromType( 'string' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = array(
			$property,
			array(
				'type' => 'property',
				'datatype' => 'string',
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
				)
			)
		);

		return $provider;
	}

}
