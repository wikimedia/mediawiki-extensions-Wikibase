<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\ItemDeserializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Deserializers\ItemDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ItemDeserializerTest extends DispatchableDeserializerTest {

	protected function buildDeserializer() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( 'Q42' ) )
			->will( $this->returnValue( new ItemId( 'Q42' ) ) );

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

		$siteLinkDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$siteLinkDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'site' => 'enwiki',
				'title' => 'Nyan Cat',
				'badges' => array()
			) ) )
			->will( $this->returnValue( new SiteLink( 'enwiki', 'Nyan Cat' ) ) );

		return new ItemDeserializer(
			$entityIdDeserializerMock,
			$termListDeserializerMock,
			$aliasGroupListDeserializerMock,
			$statementListDeserializerMock,
			$siteLinkDeserializerMock
		);
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
		$item->getFingerprint()->setLabel( 'en', 'foo' );
		$provider[] = array(
			$item,
			array(
				'type' => 'item',
				'labels' => array(
					'en' => array(
						'lang' => 'en',
						'value' => 'foo'
					)
				)
			)
		);

		$item = new Item();
		$item->getFingerprint()->setDescription( 'en', 'foo' );
		$provider[] = array(
			$item,
			array(
				'type' => 'item',
				'descriptions' => array(
					'en' => array(
						'lang' => 'en',
						'value' => 'foo'
					)
				)
			)
		);

		$item = new Item();
		$item->getFingerprint()->setAliasGroup( 'en', array( 'foo', 'bar' ) );
		$provider[] = array(
			$item,
			array(
				'type' => 'item',
				'aliases' => array(
					'en' => array(
						'lang' => 'en',
						'values' => array( 'foo', 'bar' )
					)
				)
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
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Nyan Cat' );
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
