<?php

namespace Wikibase\Tests\Repo;

use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Repo\WikibaseRepo
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRepoTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class WikibaseRepoTest extends \MediaWikiTestCase {

	public function testGetDataTypeFactoryReturnType() {
		$returnValue = $this->getDefaultInstance()->getDataTypeFactory();
		$this->assertInstanceOf( 'DataTypes\DataTypeFactory', $returnValue );
	}

	public function testGetDataValueFactoryReturnType() {
		$returnValue = $this->getDefaultInstance()->getDataValueFactory();
		$this->assertInstanceOf( 'DataValues\DataValueFactory', $returnValue );
	}

	public function testGetEntityContentFactoryReturnType() {
		$returnValue = $this->getDefaultInstance()->getEntityContentFactory();
		$this->assertInstanceOf( 'Wikibase\EntityContentFactory', $returnValue );
	}

	public function testGetEntityTitleLookupReturnType() {
		$returnValue = $this->getDefaultInstance()->getEntityTitleLookup();
		$this->assertInstanceOf( 'Wikibase\EntityTitleLookup', $returnValue );
	}

	public function testGetEntityRevisionLookupReturnType() {
		$returnValue = $this->getDefaultInstance()->getEntityRevisionLookup();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityRevisionLookup', $returnValue );
	}

	public function testGetEntityStoreReturnType() {
		$returnValue = $this->getDefaultInstance()->getEntityStore();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityStore', $returnValue );
	}

	public function testGetPropertyDataTypeLookupReturnType() {
		$returnValue = $this->getDefaultInstance()->getPropertyDataTypeLookup();
		$this->assertInstanceOf( 'Wikibase\Lib\PropertyDataTypeLookup', $returnValue );
	}

	public function testGetStringNormalizerReturnType() {
		$returnValue = $this->getDefaultInstance()->getStringNormalizer();
		$this->assertInstanceOf( 'Wikibase\StringNormalizer', $returnValue );
	}

	public function testGetEntityLookupReturnType() {
		$returnValue = $this->getDefaultInstance()->getEntityLookup();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityLookup', $returnValue );
	}

	public function testGetSnakConstructionServiceReturnType() {
		$returnValue = $this->getDefaultInstance()->getSnakConstructionService();
		$this->assertInstanceOf( 'Wikibase\Lib\SnakConstructionService', $returnValue );
	}

	public function testGetEntityIdParserReturnType() {
		$returnValue = $this->getDefaultInstance()->getEntityIdParser();
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\EntityIdParser', $returnValue );
	}

	public function testGetClaimGuidParser() {
		$returnValue = $this->getDefaultInstance()->getClaimGuidParser();
		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\ClaimGuidParser', $returnValue );
	}

	public function testGetLanguageFallbackChainFactory() {
		$returnValue = $this->getDefaultInstance()->getLanguageFallbackChainFactory();
		$this->assertInstanceOf( 'Wikibase\LanguageFallbackChainFactory', $returnValue );
	}

	public function testGetClaimGuidValidator() {
		$returnValue = $this->getDefaultInstance()->getClaimGuidValidator();
		$this->assertInstanceOf( 'Wikibase\Lib\ClaimGuidValidator', $returnValue );
	}

	public function testGetSettingsReturnType() {
		$returnValue = $this->getDefaultInstance()->getSettings();
		$this->assertInstanceOf( 'Wikibase\SettingsArray', $returnValue );
	}

	public function testGetStoreReturnType() {
		$returnValue = $this->getDefaultInstance()->getStore();
		$this->assertInstanceOf( 'Wikibase\Store', $returnValue );
	}

	public function testGetSnakFormatterFactory() {
		$returnValue = $this->getDefaultInstance()->getSnakFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatSnakFormatterFactory', $returnValue );
	}

	public function testGetValueFormatterFactory() {
		$returnValue = $this->getDefaultInstance()->getValueFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatValueFormatterFactory', $returnValue );
	}

	public function testGetSummaryFormatter() {
		$returnValue = $this->getDefaultInstance()->getSummaryFormatter();
		$this->assertInstanceOf( 'Wikibase\SummaryFormatter', $returnValue );
	}

	public function testGetChangeOpFactory() {
		$returnValue = $this->getDefaultInstance()->getChangeOpFactoryProvider();
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOpFactoryProvider', $returnValue );
	}

	public function testGetChangeNotifier() {
		$factory = $this->getDefaultInstance()->getChangeNotifier();
		$this->assertInstanceOf( 'Wikibase\Repo\Notifications\ChangeNotifier', $factory );
	}

	public function testGetContentModelMappings() {
		$array = $this->getDefaultInstance()->getContentModelMappings();
		foreach( $array as $entityType => $contentModel ) {
			$this->assertTrue( is_scalar( $entityType ) );
			$this->assertTrue( is_scalar( $contentModel ) );
		}
	}

	public function testGetExceptionLocalizer() {
		$localizer = $this->getDefaultInstance()->getExceptionLocalizer();
		$this->assertInstanceOf( 'Wikibase\Lib\Localizer\ExceptionLocalizer', $localizer );
	}

	public function testGetEntityContentDataCodec() {
		$codec = $this->getDefaultInstance()->getEntityContentDataCodec();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityContentDataCodec', $codec );
	}

	public function testGetInternalEntitySerializer() {
		$serializer = $this->getDefaultInstance()->getInternalEntitySerializer();
		$this->assertInstanceOf( 'Serializers\Serializer', $serializer );
	}

	public function testGetInternalEntityDeserializer() {
		$deserializer = $this->getDefaultInstance()->getInternalEntityDeserializer();
		$this->assertInstanceOf( 'Deserializers\Deserializer', $deserializer );
	}

	public function testGetEntityChangeFactory() {
		$factory = $this->getDefaultInstance()->getEntityChangeFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\Changes\EntityChangeFactory', $factory );
	}

	public function testGetEntityContentDataCodec_legacy() {
		$item = Item::newEmpty();
		$item->setLabel( 'en', 'Hello' );
		$item->setLabel( 'es', 'Holla' );

		$repo = $this->getDefaultInstance();
		$repo->getSettings()->setSetting( 'internalEntitySerializerClass', 'Wikibase\Lib\Serializers\LegacyInternalEntitySerializer' );

		$codec = $repo->getEntityContentDataCodec();
		$json = $codec->encodeEntity( $item, CONTENT_FORMAT_JSON );
		$data = json_decode( $json, true );

		$this->assertEquals( $item->toArray(), $data );
	}

	public function testGetInternalEntitySerializer_legacy() {
		$item = Item::newEmpty();
		$item->setLabel( 'en', 'Hello' );
		$item->setLabel( 'es', 'Holla' );

		$repo = $this->getDefaultInstance();
		$repo->getSettings()->setSetting( 'internalEntitySerializerClass', 'Wikibase\Lib\Serializers\LegacyInternalEntitySerializer' );

		$serializer = $repo->getInternalEntitySerializer();
		$data = $serializer->serialize( $item );

		$this->assertEquals( $item->toArray(), $data );
	}

	public function testGetInternalClaimSerializer_legacy() {
		$claim = new Statement( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'kittens' );

		$repo = $this->getDefaultInstance();
		$repo->getSettings()->setSetting( 'internalClaimSerializerClass', 'Wikibase\Lib\Serializers\LegacyInternalClaimSerializer' );

		$serializer = $repo->getInternalClaimSerializer();
		$data = $serializer->serialize( $claim );

		$this->assertEquals( $claim->toArray(), $data );
	}

	public function testNewItemHandler() {
		$repo = $this->getDefaultInstance();
		$handler = $repo->newItemHandler();
		$this->assertInstanceOf( 'Wikibase\EntityHandler', $handler );
	}

	public function testNewPropertyHandler() {
		$repo = $this->getDefaultInstance();
		$handler = $repo->newPropertyHandler();
		$this->assertInstanceOf( 'Wikibase\EntityHandler', $handler );
	}

	public function testNewItemHandler_noTransform() {
		$repo = $this->getDefaultInstance();
		$repo->getSettings()->setSetting( 'transformLegacyFormatOnExport', false );

		$handler = $repo->newItemHandler();
		$this->assertNull( $handler->getLegacyExportFormatDetector() );
	}

	public function testNewPropertyHandler_noTransform() {
		$repo = $this->getDefaultInstance();
		$repo->getSettings()->setSetting( 'transformLegacyFormatOnExport', false );

		$handler = $repo->newPropertyHandler();
		$this->assertNull( $handler->getLegacyExportFormatDetector() );
	}

	public function testNewItemHandler_withTransform() {
		$repo = $this->getDefaultInstance();
		$repo->getSettings()->setSetting( 'transformLegacyFormatOnExport', true );
		$repo->getSettings()->setSetting( 'internalEntitySerializerClass', null );

		$handler = $repo->newItemHandler();
		$this->assertNotNull( $handler->getLegacyExportFormatDetector() );
	}

	public function testNewPropertyHandler_withTransform() {
		$repo = $this->getDefaultInstance();
		$repo->getSettings()->setSetting( 'transformLegacyFormatOnExport', true );
		$repo->getSettings()->setSetting( 'internalEntitySerializerClass', null );

		$handler = $repo->newPropertyHandler();
		$this->assertNotNull( $handler->getLegacyExportFormatDetector() );
	}

	public function testNewItemHandler_badSerializerSetting() {
		$repo = $this->getDefaultInstance();
		$repo->getSettings()->setSetting( 'transformLegacyFormatOnExport', true );
		$repo->getSettings()->setSetting( 'internalEntitySerializerClass', 'Wikibase\Lib\Serializers\LegacyInternalEntitySerializer' );

		$this->setExpectedException( 'RuntimeException' );
		$repo->newItemHandler();
	}

	public function testNewPropertyHandler_badSerializerSetting() {
		$repo = $this->getDefaultInstance();
		$repo->getSettings()->setSetting( 'transformLegacyFormatOnExport', true );
		$repo->getSettings()->setSetting( 'internalEntitySerializerClass', 'Wikibase\Lib\Serializers\LegacyInternalEntitySerializer' );

		$this->setExpectedException( 'RuntimeException' );
		$repo->newPropertyHandler();
	}

	/**
	 * @return WikibaseRepo
	 */
	private function getDefaultInstance() {
		$settings = new SettingsArray( WikibaseRepo::getDefaultInstance()->getSettings()->getArrayCopy() );
		return new WikibaseRepo( $settings );
	}

}
