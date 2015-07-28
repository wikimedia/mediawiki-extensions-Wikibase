<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\ParserOutputJsConfigBuilder;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\ParserOutputJsConfigBuilder
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParserOutputJsConfigBuilderTest extends MediaWikiTestCase {

	/**
	 * @dataProvider buildProvider
	 */
	public function testBuild( Entity $entity ) {
		$configBuilder = $this->getConfigBuilder();
		$configVars = $configBuilder->build( $entity );

		$this->assertInternalType( 'array', $configVars );

		$entityId = $entity->getId()->getSerialization();
		$this->assertEquals( $entityId, $configVars['wbEntityId'], 'wbEntityId' );

		$this->assertSerializationEqualsEntity( $entity, json_decode( $configVars['wbEntity'], true ) );
	}

	public function assertSerializationEqualsEntity( Entity $entity, $serialization ) {
		$deserializer = WikibaseRepo::getDefaultInstance()->getEntityDeserializer();
		$unserializedEntity = $deserializer->deserialize( $serialization );

		$this->assertTrue( $unserializedEntity->equals( $entity ), 'unserialized entity equals entity' );
	}

	public function buildProvider() {
		$entity = $this->getMainItem();

		return array(
			array( $entity )
		);
	}

	private function getConfigBuilder() {
		$configBuilder = new ParserOutputJsConfigBuilder();

		return $configBuilder;
	}

	private function getMainItem() {
		$item = new Item( new ItemId( 'Q5881' ) );
		$item->setLabel( 'en', 'Cake' );

		$snak = new PropertyValueSnak(
			new PropertyId( 'P794' ),
			new EntityIdValue( new ItemId( 'Q9000' ) )
		);
		$guid = 'P794$muahahaha';
		$item->getStatements()->addNewStatement( $snak, null, null, $guid );

		return $item;
	}

}
