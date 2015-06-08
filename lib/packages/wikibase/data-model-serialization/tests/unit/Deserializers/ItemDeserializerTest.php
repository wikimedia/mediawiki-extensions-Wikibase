<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\ItemDeserializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @covers Wikibase\DataModel\Deserializers\ItemDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ItemDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnValue( new ItemId( 'Q42' ) ) );

		$fingerprintDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$fingerprintDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnValue( new Fingerprint() ) );

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

		$siteLinkDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$siteLinkDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'site' => 'enwiki',
				'title' => 'Nyan Cat',
				'badges' => array()
			) ) )
			->will( $this->returnValue( new SiteLink( 'enwiki', 'Nyan Cat' ) ) );

		return new ItemDeserializer( $entityIdDeserializerMock, $fingerprintDeserializerMock, $statementListDeserializerMock, $siteLinkDeserializerMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'type' => 'item'
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
					'type' => 'property'
				)
			),
		);
	}

	public function deserializationProvider() {
		$provider = array(
			array(
				new Item(),
				array(
					'type' => 'item'
				)
			),
		);

		$item = new Item( new ItemId( 'Q42' ) );
		$provider[] = array(
			$item,
			array(
				'type' => 'item',
				'id' => 'Q42'
			)
		);

		$item = new Item();
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = array(
			$item,
			array(
				'type' => 'item',
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

		$item = new Item();
		$item->addSiteLink( new SiteLink( 'enwiki', 'Nyan Cat' ) );
		$provider[] = array(
			$item,
			array(
				'type' => 'item',
				'sitelinks' => array(
					'enwiki' => array(
						'site' => 'enwiki',
						'title' => 'Nyan Cat',
						'badges' => array()
					)
				)
			)
		);

		return $provider;
	}

}
