<?php

namespace Wikibase\Test;

use Language;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\LibSerializerFactory;
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
class ParserOutputJsConfigBuilderTest extends MediaWikiTestCase {

	/**
	 * @dataProvider buildProvider
	 */
	public function testBuild( Entity $entity ) {
		$configBuilder = $this->getConfigBuilder( 'en', array( 'de', 'en', 'es', 'fr' ) );
		$configVars = $configBuilder->build( $entity );

		$this->assertInternalType( 'array', $configVars );

		$entityId = $entity->getId()->getSerialization();
		$this->assertEquals( $entityId, $configVars['wbEntityId'], 'wbEntityId' );

		$this->assertSerializationEqualsEntity( $entity, json_decode( $configVars['wbEntity'], true ) );
	}

	public function assertSerializationEqualsEntity( Entity $entity, $serialization ) {
		$serializerFactory = new LibSerializerFactory();
		$options = new SerializationOptions();

		$unserializer = $serializerFactory->newUnserializerForEntity( $entity->getType(), $options );
		$unserializedEntity = $unserializer->newFromSerialization( $serialization );

		$this->assertTrue( $unserializedEntity->equals( $entity ), 'unserialized entity equals entity' );
	}

	public function buildProvider() {
		$entity = $this->getMainItem();

		return array(
			array( $entity )
		);
	}

	private function getConfigBuilder( $languageCode, array $languageCodes ) {
		$configBuilder = new ParserOutputJsConfigBuilder(
			$this->getSerializationOptions( $languageCode, $languageCodes )
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
