<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Language;
use Title;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityTitleLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\ParserOutputJsConfigBuilder;
use Wikibase\ReferencedEntitiesFinder;

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
	public function testBuild( Entity $entity, $usedEntities ) {
		$langCode = 'en';
		$langCodes = array( 'de', 'en', 'es', 'fr' );

		$configBuilder = $this->getConfigBuilder( $langCode );
		$options = $this->getSerializationOptions( $langCode, $langCodes );

		$configVars = $configBuilder->build( $entity, $options );

		$this->assertInternalType( 'array', $configVars );

		$entityId = $entity->getId()->getSerialization();
		$this->assertEquals( $entityId, $configVars['wbEntityId'], 'wbEntityId' );

		$usedEntitiesVar = json_decode( $configVars['wbUsedEntities'], true );
		$this->assertEquals( $usedEntities, $usedEntitiesVar, 'wbUsedEntities' );

		$this->assertSerializationEqualsEntity( $entity, json_decode( $configVars['wbEntity'], true ) );
	}

	public function assertSerializationEqualsEntity( $entity, $serialization ) {
		$serializerFactory = new SerializerFactory();
		$options = new SerializationOptions();

		$unserializer = $serializerFactory->newUnserializerForEntity( $entity->getType(), $options );
		$unserializedEntity = $unserializer->newFromSerialization( $serialization );

		$this->assertTrue( $unserializedEntity->equals( $entity ), 'unserialized entity equals entity' );
	}

	public function buildProvider() {
		$entity = $this->getEntity();

		$property = $this->getProperty();
		$usedEntities = $this->getUsedEntities( $property );

		return array(
			array( $entity, $usedEntities, true )
		);
	}

	private function getConfigBuilder( $langCode ) {
		$configBuilder = new ParserOutputJsConfigBuilder(
			$this->getMockRepository(),
			new BasicEntityIdParser(),
			$this->getEntityTitleLookupMock(),
			new ReferencedEntitiesFinder(),
			$langCode
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

	private function getEntity() {
		$item = Item::newEmpty();
		$itemId = new ItemId( 'Q5881' );
		$item->setId( $itemId );
		$item->setLabel( 'en', 'Cake' );

		$snak = new PropertyValueSnak( new PropertyId( 'P794' ), new StringValue( 'a' ) );

		$claim = new Claim( $snak );
		$claim->setGuid( 'P794$muahahaha' );

		$item->addClaim( $claim );

		return $item;
	}

	private function getProperty() {
		$property = Property::newFromType( 'string' );
		$property->setId( new PropertyId( 'P794' ) );
		$property->setLabel( 'en', 'AwesomeID' );

		return $property;
	}

	private function getUsedEntities( Property $property ) {
		$propertyId = $property->getId()->getSerialization();

		$usedEntities = array(
			$propertyId => array(
				'content' => array(
					'id' => $propertyId,
					'type' => 'property',
					'labels' => array(
						'en' => array(
							'language' => 'en',
							'value' => $property->getLabel( 'en' )
						)
					),
					'descriptions' => $property->getDescriptions(),
					'datatype' => $property->getDataTypeId(),
				),
				'title' => "property:$propertyId"
			)
		);

		return $usedEntities;
	}

	private function getMockRepository() {
		$mockRepo = new MockRepository();

		$entity = $this->getEntity();
		$mockRepo->putEntity( $entity );

		$property = $this->getProperty();
		$mockRepo->putEntity( $property );

		return $mockRepo;
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
		$lookup = $this->getMockBuilder( 'Wikibase\EntityTitleLookup' )
			->disableOriginalConstructor()
			->getMock();

		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

}
