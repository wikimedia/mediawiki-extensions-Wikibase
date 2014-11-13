<?php

namespace Wikibase\Test;

use Language;
use Title;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\ParserOutputJsConfigBuilder;

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
class ParserOutputJsConfigBuilderTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider buildProvider
	 */
	public function testBuild( array $usedEntities, Entity $entity, array $entityInfo ) {
		$configBuilder = $this->getConfigBuilder( 'en', array( 'de', 'en', 'es', 'fr' ) );
		$configVars = $configBuilder->build( $entity, $entityInfo );

		$this->assertInternalType( 'array', $configVars );

		$entityId = $entity->getId()->getSerialization();
		$this->assertEquals( $entityId, $configVars['wbEntityId'], 'wbEntityId' );

		$usedEntitiesVar = json_decode( $configVars['wbUsedEntities'], true );
		$this->assertEquals( $usedEntities, $usedEntitiesVar, 'wbUsedEntities' );

		$this->assertSerializationEqualsEntity( $entity, json_decode( $configVars['wbEntity'], true ) );
	}

	public function assertSerializationEqualsEntity( Entity $entity, $serialization ) {
		$serializerFactory = new SerializerFactory();
		$options = new SerializationOptions();

		$unserializer = $serializerFactory->newUnserializerForEntity( $entity->getType(), $options );
		$unserializedEntity = $unserializer->newFromSerialization( $serialization );

		$this->assertTrue( $unserializedEntity->equals( $entity ), 'unserialized entity equals entity' );
	}

	public function buildProvider() {
		$entity = $this->getMainItem();

		$referencedItem = $this->getReferencedItem();
		$entityInfo = $this->getEntityInfo( $referencedItem );

		$usedEntities = array(
			array(
				'content' => $this->getEntityInfoContent( $referencedItem ),
				'title' => 'item:Q55'
			)
		);

		return array(
			array( $usedEntities, $entity, $entityInfo )
		);
	}

	private function getConfigBuilder( $languageCode, array $languageCodes ) {
		$configBuilder = new ParserOutputJsConfigBuilder(
			new BasicEntityIdParser(),
			$this->getEntityTitleLookupMock(),
			$this->getSerializationOptions( $languageCode, $languageCodes ),
			$languageCode
		);

		return $configBuilder;
	}

	/**
	 * @param string $langCode
	 *
	 * @return LanguageFallbackChain
	 */
	private function getLanguageFallbackChain( $langCode ) {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();

		$languageFallbackChain = $languageFallbackChainFactory->newFromLanguage(
			Language::factory( $langCode )
		);

		return $languageFallbackChain;
	}

	private function getSerializationOptions( $langCode, $langCodes ) {
		$fallbackChain = $this->getLanguageFallbackChain( $langCode );
		$langCodes = $langCodes + array( $langCode => $fallbackChain );

		$options = new SerializationOptions();
		$options->setLanguages( $langCodes );

		return $options;
	}

	private function getMainItem() {
		$item = Item::newEmpty();
		$itemId = new ItemId( 'Q5881' );
		$item->setId( $itemId );
		$item->setLabel( 'en', 'Cake' );

		$snak = new PropertyValueSnak(
			new PropertyId( 'P794' ),
			new EntityIdValue( new ItemId( 'Q9000' ) )
		);

		$statement = new Statement( new Claim( $snak ) );
		$statement->setGuid( 'P794$muahahaha' );

		$item->addClaim( $statement );

		return $item;
	}

	private function getReferencedItem() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q55' ) );
		$item->setLabel( 'en', 'Vanilla' );

		return $item;
	}

	private function getEntityInfo( Entity $entity ) {
		return array(
			$this->getEntityInfoContent( $entity )
		);
	}

	private function getEntityInfoContent( Entity $entity ) {
		return array(
			'id' => $entity->getId()->getSerialization(),
			'type' => $entity->getType(),
			'labels' => array(
				'en' => array(
					'language' => 'en',
					'value' => $entity->getLabel( 'en' )
				)
			),
			'descriptions' => $entity->getDescriptions()
		);
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Title
	 */
	public function getTitleForId( EntityId $entityId ) {
		$name = $entityId->getEntityType() . ':' . $entityId->getSerialization();
		return Title::makeTitle( NS_MAIN, $name );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookupMock() {
		$lookup = $this->getMockBuilder( 'Wikibase\Lib\Store\EntityTitleLookup' )
			->disableOriginalConstructor()
			->getMock();

		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

}
