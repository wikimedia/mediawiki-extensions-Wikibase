<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\ItemSerializer;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Serializers\ItemSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ItemSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer( $useObjectsForMaps = false ) {
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

		$statementListSerializerMock = $this->getMock( '\Serializers\Serializer' );
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

		$siteLinkSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$siteLinkSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new SiteLink( 'enwiki', 'Nyan Cat' ) ) )
			->will( $this->returnValue( array(
				'site' => 'enwiki',
				'title' => 'Nyan Cat',
				'badges' => array()
			) ) );

		return new ItemSerializer(
			$termListSerializerMock,
			$aliasGroupListSerializerMock,
			$statementListSerializerMock,
			$siteLinkSerializerMock,
			$useObjectsForMaps
		);
	}

	public function serializableProvider() {
		return array(
			array( new Item() ),
		);
	}

	public function nonSerializableProvider() {
		return array(
			array( 5 ),
			array( array() ),
			array( Property::newFromType( 'string' ) ),
		);
	}

	public function serializationProvider() {
		$provider = array(
			array(
				array(
					'type' => 'item',
					'labels' => array(),
					'descriptions' => array(),
					'aliases' => array(),
					'claims' => array(),
					'sitelinks' => array(),
				),
				new Item()
			),
		);

		$entity = new Item();
		$entity->setId( 42 );
		$provider[] = array(
			array(
				'type' => 'item',
				'id' => 'Q42',
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = new Item();
		$entity->getFingerprint()->setLabel( 'en', 'foo' );
		$provider[] = array(
			array(
				'type' => 'item',
				'labels' => array(
					'en' => array(
						'lang' => 'en',
						'value' => 'foo'
					)
				),
				'descriptions' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = new Item();
		$entity->getFingerprint()->setDescription( 'en', 'foo' );
		$provider[] = array(
			array(
				'type' => 'item',
				'labels' => array(),
				'descriptions' => array(
					'en' => array(
						'lang' => 'en',
						'value' => 'foo'
					)
				),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = new Item();
		$entity->getFingerprint()->setAliasGroup( 'en', array( 'foo', 'bar' ) );
		$provider[] = array(
			array(
				'type' => 'item',
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(
					'en' => array(
						'lang' => 'en',
						'values' => array( 'foo', 'bar' )
					)
				),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = new Item();
		$entity->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = array(
			array(
				'type' => 'item',
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(),
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
				'sitelinks' => array(),
			),
			$entity
		);

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Nyan Cat' );
		$provider[] = array(
			array(
				'type' => 'item',
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(
					'enwiki' => array(
						'site' => 'enwiki',
						'title' => 'Nyan Cat',
						'badges' => array()
					)
				),
			),
			$item
		);

		return $provider;
	}

	public function testItemSerializerWithOptionObjectsForMaps() {
		$serializer = $this->buildSerializer( true );

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Nyan Cat' );

		$sitelinks = new \stdClass();
		$sitelinks->enwiki = array(
			'site' => 'enwiki',
			'title' => 'Nyan Cat',
			'badges' => array(),
		);

		$serial = array(
			'type' => 'item',
			'labels' => array(),
			'descriptions' => array(),
			'aliases' => array(),
			'claims' => array(),
			'sitelinks' => $sitelinks,
		);

		$this->assertEquals( $serial, $serializer->serialize( $item ) );
	}

}
