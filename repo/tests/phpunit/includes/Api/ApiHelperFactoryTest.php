<?php

namespace Wikibase\Repo\Tests\Api;

use ApiBase;
use ApiResult;
use HashSiteStore;
use MediaWiki\MediaWikiServices;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\Api\EntityLoadingHelper;
use Wikibase\Repo\Api\EntitySavingHelper;
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;

/**
 * @covers \Wikibase\Repo\Api\ApiHelperFactory
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ApiHelperFactoryTest extends \PHPUnit\Framework\TestCase {

	private function newApiHelperFactory() {
		$summaryFormatter = $this->createMock( SummaryFormatter::class );

		$editEntityFactory = $this->createMock( MediawikiEditEntityFactory::class );

		$serializerFactory = $this->createMock( SerializerFactory::class );

		$services = MediaWikiServices::getInstance();

		return new ApiHelperFactory(
			$this->createMock( EntityTitleStoreLookup::class ),
			$this->createMock( ExceptionLocalizer::class ),
			new InMemoryDataTypeLookup(),
			new HashSiteStore(),
			$summaryFormatter,
			$this->createMock( EntityRevisionLookup::class ),
			$editEntityFactory,
			$serializerFactory,
			$this->createMock( Serializer::class ),
			new ItemIdParser(),
			$services->getPermissionManager(),
			$services->getRevisionLookup(),
			$services->getTitleFactory()
		);
	}

	/**
	 * @return ApiBase
	 */
	private function newApiModule() {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );

		$result = $this->createMock( ApiResult::class );

		$api = $this->createMock( ApiBase::class );

		$api->method( 'getResult' )
			->willReturn( $result );

		$api->method( 'getLanguage' )
			->willReturn( $language );

		$api->method( 'isWriteMode' )->willReturn( true );

		$api->method( 'needsToken' )->willReturn( false );

		return $api;
	}

	public function testGetResultBuilder() {
		$api = $this->newApiModule();
		$factory = $this->newApiHelperFactory();

		$resultBuilder = $factory->getResultBuilder( $api );
		$this->assertInstanceOf( ResultBuilder::class, $resultBuilder );
	}

	public function testGetErrorReporter() {
		$api = $this->newApiModule();
		$factory = $this->newApiHelperFactory();

		$errorReporter = $factory->getErrorReporter( $api );
		$this->assertInstanceOf( ApiErrorReporter::class, $errorReporter );
	}

	public function testGetEntitySavingHelper() {
		$factory = $this->newApiHelperFactory();

		$helper = $factory->getEntitySavingHelper( $this->newApiModule() );
		$this->assertInstanceOf( EntitySavingHelper::class, $helper );
	}

	public function testGetEntityLoadingHelper() {
		$factory = $this->newApiHelperFactory();

		$helper = $factory->getEntityLoadingHelper( $this->newApiModule() );
		$this->assertInstanceOf( EntityLoadingHelper::class, $helper );
	}

}
