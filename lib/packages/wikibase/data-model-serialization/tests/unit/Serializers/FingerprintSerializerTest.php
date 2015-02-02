<?php

namespace Tests\Wikibase\DataModel\Serializers;

use stdClass;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\FingerprintSerializer;
use Wikibase\DataModel\Serializers\ItemSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Term\AliasGroupFallback;
use Wikibase\DataModel\Term\TermFallback;

/**
 * @covers Wikibase\DataModel\Serializers\FingerprintSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class FingerprintSerializerTest extends SerializerBaseTest {

	protected function buildSerializer() {
		$claimsSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$claimsSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new Claims() ) )
			->will( $this->returnValue( array() ) );

		$siteLinkSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$siteLinkSerializerMock->expects( $this->any() )
			->method( 'serialize' );

		$entitySerializer = new FingerprintSerializer( false );

		return new ItemSerializer( $entitySerializer, $claimsSerializerMock, $siteLinkSerializerMock, false );
	}

	public function serializableProvider() {
		return array(
			array(
				new Item()
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
				new PropertyNoValueSnak( 42 )
			),
		);
	}

	public function serializationProvider() {
		$argumentLists = array();

		$argumentLists['empty item'] = array(
			array(
				'type' => 'item',
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			new Item()
		);

		$entity = new Item( new ItemId( 'Q42' ) );
		$argumentLists['id on item'] = array(
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
		$entity->setLabels( array(
			'en' => 'Nyan Cat',
			'fr' => 'Nyan Cat'
		) );
		$argumentLists['labels on item'] = array(
			array(
				'type' => 'item',
				'labels' => array(
					'en' => array(
						'language' => 'en',
						'value' => 'Nyan Cat'
					),
					'fr' => array(
						'language' => 'fr',
						'value' => 'Nyan Cat'
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
		$entity->getFingerprint()->getLabels()->setTerm(
			new TermFallback( 'de-formal', 'Nyan Cat', 'de', null )
		);
		$argumentLists['label with fallback term on item'] = array(
			array(
				'type' => 'item',
				'labels' => array(
					'de-formal' => array(
						'language' => 'de',
						'value' => 'Nyan Cat',
						'source' => null,
					),
				),
				'descriptions' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = new Item();
		$entity->getFingerprint()->getLabels()->setTerm(
			new TermFallback( 'zh-cn', 'Nyan Cat', 'zh-cn', 'zh-tw' )
		);
		$argumentLists['label with fallback term with source on item'] = array(
			array(
				'type' => 'item',
				'labels' => array(
					'zh-cn' => array(
						'language' => 'zh-cn',
						'value' => 'Nyan Cat',
						'source' => 'zh-tw',
					),
				),
				'descriptions' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = new Item();
		$entity->setDescriptions( array(
			'en' => 'A Nyan Cat',
			'fr' => 'A Nyan Cat'
		) );
		$argumentLists['descriptions on item'] = array(
			array(
				'type' => 'item',
				'descriptions' => array(
					'en' => array(
						'language' => 'en',
						'value' => 'A Nyan Cat'
					),
					'fr' => array(
						'language' => 'fr',
						'value' => 'A Nyan Cat'
					)
				),
				'labels' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = new Item();
		$entity->getFingerprint()->getDescriptions()->setTerm(
			new TermFallback( 'de-formal', 'A Nyan Cat', 'de', null )
		);
		$argumentLists['description with fallback term on item'] = array(
			array(
				'type' => 'item',
				'descriptions' => array(
					'de-formal' => array(
						'language' => 'de',
						'value' => 'A Nyan Cat',
						'source' => null,
					),
				),
				'labels' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = new Item();
		$entity->getFingerprint()->getDescriptions()->setTerm(
			new TermFallback( 'zh-cn', 'A Nyan Cat', 'zh-cn', 'zh-tw' )
		);
		$argumentLists['description with fallback term with source on item'] = array(
			array(
				'type' => 'item',
				'descriptions' => array(
					'zh-cn' => array(
						'language' => 'zh-cn',
						'value' => 'A Nyan Cat',
						'source' => 'zh-tw',
					),
				),
				'labels' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = new Item();
		$entity->setAliases( 'en', array( 'Cat', 'My cat' ) );
		$entity->setAliases( 'fr', array( 'Cat' ) );
		$argumentLists['aliases on item'] = array(
			array(
				'type' => 'item',
				'aliases' => array(
					'en' => array(
						array(
							'language' => 'en',
							'value' => 'Cat'
						),
						array(
							'language' => 'en',
							'value' => 'My cat'
						)
					),
					'fr' => array(
						array(
							'language' => 'fr',
							'value' => 'Cat'
						)
					)
				),
				'labels' => array(),
				'descriptions' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = new Item();
		$entity->getFingerprint()->getAliasGroups()->setGroup(
			new AliasGroupFallback( 'de-formal', array( 'Cat' ), 'de', null )
		);
		$argumentLists['alias with fallback on item'] = array(
			array(
				'type' => 'item',
				'aliases' => array(
					'de-formal' => array(
						array(
							'language' => 'de',
							'value' => 'Cat',
							'source' => null,
						),
					),
				),
				'labels' => array(),
				'descriptions' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = new Item();
		$entity->getFingerprint()->getAliasGroups()->setGroup(
			new AliasGroupFallback( 'zh-cn', array( 'Cat' ), 'zh-cn', 'zh-tw' )
		);
		$argumentLists['alias with fallback with source on item'] = array(
			array(
				'type' => 'item',
				'aliases' => array(
					'zh-cn' => array(
						array(
							'language' => 'zh-cn',
							'value' => 'Cat',
							'source' => 'zh-tw',
						),
					),
				),
				'labels' => array(),
				'descriptions' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		return $argumentLists;
	}

	public function testDescriptionWithOptionObjectsForMaps() {
		$entitySerializer = new FingerprintSerializer( true );

		$entity = new Item();
		$entity->setDescriptions( array(
			'en' => 'A Nyan Cat',
		) );

		$result = array();

		$descriptions = new stdClass();
		$descriptions->en = array(
			'language' => 'en',
			'value' => 'A Nyan Cat'
		);
		$serial = array( 'descriptions' => $descriptions );
		$entitySerializer->addDescriptionsToSerialization( $entity, $result );
		$this->assertEquals( $serial, $result );
	}

	public function testAliasesWithOptionObjectsForMaps() {
		$entitySerializer = new FingerprintSerializer( true );

		$entity = new Item();
		$entity->setAliases( 'fr', array( 'Cat' ) );

		$result = array();

		$aliases = new stdClass();
		$aliases->fr = array( array(
			'language' => 'fr',
			'value' => 'Cat'
		) );
		$serial = array( 'aliases' => $aliases );
		$entitySerializer->addAliasesToSerialization( $entity, $result );
		$this->assertEquals( $serial, $result );
	}

}
