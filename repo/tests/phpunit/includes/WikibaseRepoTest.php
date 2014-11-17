<?php

namespace Wikibase\Tests\Repo;

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
		$returnValue = $this->getWikibaseRepo()->getDataTypeFactory();
		$this->assertInstanceOf( 'DataTypes\DataTypeFactory', $returnValue );
	}

	public function testGetDataValueFactoryReturnType() {
		$returnValue = $this->getWikibaseRepo()->getDataValueFactory();
		$this->assertInstanceOf( 'DataValues\DataValueFactory', $returnValue );
	}

	public function testGetEntityContentFactoryReturnType() {
		$returnValue = $this->getWikibaseRepo()->getEntityContentFactory();
		$this->assertInstanceOf( 'Wikibase\Repo\Content\EntityContentFactory', $returnValue );
	}

	public function testGetEntityTitleLookupReturnType() {
		$returnValue = $this->getWikibaseRepo()->getEntityTitleLookup();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityTitleLookup', $returnValue );
	}

	public function testGetEntityRevisionLookupReturnType() {
		$returnValue = $this->getWikibaseRepo()->getEntityRevisionLookup();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityRevisionLookup', $returnValue );
	}

	public function testGetEntityStoreReturnType() {
		$returnValue = $this->getWikibaseRepo()->getEntityStore();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityStore', $returnValue );
	}

	public function testGetPropertyDataTypeLookupReturnType() {
		$returnValue = $this->getWikibaseRepo()->getPropertyDataTypeLookup();
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup', $returnValue );
	}

	public function testGetStringNormalizerReturnType() {
		$returnValue = $this->getWikibaseRepo()->getStringNormalizer();
		$this->assertInstanceOf( 'Wikibase\StringNormalizer', $returnValue );
	}

	public function testGetEntityLookupReturnType() {
		$returnValue = $this->getWikibaseRepo()->getEntityLookup();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityLookup', $returnValue );
	}

	public function testGetSnakConstructionServiceReturnType() {
		$returnValue = $this->getWikibaseRepo()->getSnakConstructionService();
		$this->assertInstanceOf( 'Wikibase\Lib\SnakConstructionService', $returnValue );
	}

	public function testGetEntityIdParserReturnType() {
		$returnValue = $this->getWikibaseRepo()->getEntityIdParser();
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\EntityIdParser', $returnValue );
	}

	public function testGetClaimGuidParser() {
		$returnValue = $this->getWikibaseRepo()->getClaimGuidParser();
		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\ClaimGuidParser', $returnValue );
	}

	public function testGetLanguageFallbackChainFactory() {
		$returnValue = $this->getWikibaseRepo()->getLanguageFallbackChainFactory();
		$this->assertInstanceOf( 'Wikibase\LanguageFallbackChainFactory', $returnValue );
	}

	public function testGetClaimGuidValidator() {
		$returnValue = $this->getWikibaseRepo()->getClaimGuidValidator();
		$this->assertInstanceOf( 'Wikibase\Lib\ClaimGuidValidator', $returnValue );
	}

	public function testGetSettingsReturnType() {
		$returnValue = $this->getWikibaseRepo()->getSettings();
		$this->assertInstanceOf( 'Wikibase\SettingsArray', $returnValue );
	}

	public function testGetStoreReturnType() {
		$returnValue = $this->getWikibaseRepo()->getStore();
		$this->assertInstanceOf( 'Wikibase\Store', $returnValue );
	}

	public function testGetSnakFormatterFactory() {
		$returnValue = $this->getWikibaseRepo()->getSnakFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatSnakFormatterFactory', $returnValue );
	}

	public function testGetValueFormatterFactory() {
		$returnValue = $this->getWikibaseRepo()->getValueFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatValueFormatterFactory', $returnValue );
	}

	public function testGetSummaryFormatter() {
		$returnValue = $this->getWikibaseRepo()->getSummaryFormatter();
		$this->assertInstanceOf( 'Wikibase\SummaryFormatter', $returnValue );
	}

	public function testGetChangeOpFactory() {
		$returnValue = $this->getWikibaseRepo()->getChangeOpFactoryProvider();
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOpFactoryProvider', $returnValue );
	}

	public function testGetChangeNotifier() {
		$factory = $this->getWikibaseRepo()->getChangeNotifier();
		$this->assertInstanceOf( 'Wikibase\Repo\Notifications\ChangeNotifier', $factory );
	}

	public function testGetContentModelMappings() {
		$array = $this->getWikibaseRepo()->getContentModelMappings();
		foreach( $array as $entityType => $contentModel ) {
			$this->assertTrue( is_scalar( $entityType ) );
			$this->assertTrue( is_scalar( $contentModel ) );
		}
	}

	public function testGetExceptionLocalizer() {
		$localizer = $this->getWikibaseRepo()->getExceptionLocalizer();
		$this->assertInstanceOf( 'Wikibase\Lib\Localizer\ExceptionLocalizer', $localizer );
	}

	public function testGetEntityContentDataCodec() {
		$codec = $this->getWikibaseRepo()->getEntityContentDataCodec();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityContentDataCodec', $codec );
	}

	public function testGetInternalEntitySerializer() {
		$serializer = $this->getWikibaseRepo()->getInternalEntitySerializer();
		$this->assertInstanceOf( 'Serializers\Serializer', $serializer );
	}

	public function testGetInternalEntityDeserializer() {
		$deserializer = $this->getWikibaseRepo()->getInternalEntityDeserializer();
		$this->assertInstanceOf( 'Deserializers\Deserializer', $deserializer );
	}

	public function testGetEntityChangeFactory() {
		$factory = $this->getWikibaseRepo()->getEntityChangeFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\Changes\EntityChangeFactory', $factory );
	}

	public function testNewItemHandler() {
		$wikibaseRepo = $this->getWikibaseRepo();
		$handler = $wikibaseRepo->newItemHandler();
		$this->assertInstanceOf( 'Wikibase\Repo\Content\EntityHandler', $handler );
	}

	public function testNewPropertyHandler() {
		$wikibaseRepo = $this->getWikibaseRepo();
		$handler = $wikibaseRepo->newPropertyHandler();
		$this->assertInstanceOf( 'Wikibase\Repo\Content\EntityHandler', $handler );
	}

	public function testNewItemHandler_noTransform() {
		$wikibaseRepo = $this->getWikibaseRepo();
		$wikibaseRepo->getSettings()->setSetting( 'transformLegacyFormatOnExport', false );

		$handler = $wikibaseRepo->newItemHandler();
		$this->assertNull( $handler->getLegacyExportFormatDetector() );
	}

	public function testNewPropertyHandler_noTransform() {
		$wikibaseRepo = $this->getWikibaseRepo();
		$wikibaseRepo->getSettings()->setSetting( 'transformLegacyFormatOnExport', false );

		$handler = $wikibaseRepo->newPropertyHandler();
		$this->assertNull( $handler->getLegacyExportFormatDetector() );
	}

	public function testNewItemHandler_withTransform() {
		$wikibaseRepo = $this->getWikibaseRepo();
		$wikibaseRepo->getSettings()->setSetting( 'transformLegacyFormatOnExport', true );
		$wikibaseRepo->getSettings()->setSetting( 'internalEntitySerializerClass', null );

		$handler = $wikibaseRepo->newItemHandler();
		$this->assertNotNull( $handler->getLegacyExportFormatDetector() );
	}

	public function testNewPropertyHandler_withTransform() {
		$wikibaseRepo = $this->getWikibaseRepo();
		$wikibaseRepo->getSettings()->setSetting( 'transformLegacyFormatOnExport', true );
		$wikibaseRepo->getSettings()->setSetting( 'internalEntitySerializerClass', null );

		$handler = $wikibaseRepo->newPropertyHandler();
		$this->assertNotNull( $handler->getLegacyExportFormatDetector() );
	}

	public function testNewItemHandler_badSerializerSetting() {
		$wikibaseRepo = $this->getWikibaseRepo();
		$wikibaseRepo->getSettings()->setSetting( 'transformLegacyFormatOnExport', true );
		$wikibaseRepo->getSettings()->setSetting( 'internalEntitySerializerClass', 'Wikibase\Lib\Serializers\LegacyInternalEntitySerializer' );

		$this->setExpectedException( 'RuntimeException' );
		$wikibaseRepo->newItemHandler();
	}

	public function testNewPropertyHandler_badSerializerSetting() {
		$wikibaseRepo = $this->getWikibaseRepo();
		$wikibaseRepo->getSettings()->setSetting( 'transformLegacyFormatOnExport', true );
		$wikibaseRepo->getSettings()->setSetting( 'internalEntitySerializerClass', 'Wikibase\Lib\Serializers\LegacyInternalEntitySerializer' );

		$this->setExpectedException( 'RuntimeException' );
		$wikibaseRepo->newPropertyHandler();
	}

	/**
	 * @return WikibaseRepo
	 */
	private function getWikibaseRepo() {
		$settings = new SettingsArray( WikibaseRepo::getDefaultInstance()->getSettings()->getArrayCopy() );
		return new WikibaseRepo( $settings );
	}

	public function testGetApiHelperFactory() {
		$factory = $this->getWikibaseRepo()->getApiHelperFactory();
		$this->assertInstanceOf( 'Wikibase\Api\ApiHelperFactory', $factory );
	}

}
