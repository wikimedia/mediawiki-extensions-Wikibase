<?php

namespace Wikibase\Test\Api;

use Language;
use Wikibase\Api\ApiHelperFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\ApiHelperFactory
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ApiHelperFactoryTest extends \PHPUnit_Framework_TestCase {

	private function newApiHelperFactory() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$exceptionLocalizer = $this->getMock( 'Wikibase\Lib\Localizer\ExceptionLocalizer' );
		$dataTypeLookup = $this->getMock( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup' );
		$entityFactory = WikibaseRepo::getDefaultInstance()->getEntityFactory();
		$summaryFormatter = $this->getMockBuilder( 'Wikibase\SummaryFormatter' )
			->disableOriginalConstructor()->getMock();
		$entityRevisionLookup = $this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' );
		$entityStore = $this->getMock( 'Wikibase\Lib\Store\EntityStore' );
		$entityPermissionChecker = $this->getMock( 'Wikibase\Repo\Store\EntityPermissionChecker' );
		$editFilterHookRunner = $this->getMockBuilder( 'Wikibase\Repo\Hooks\EditFilterHookRunner' )
			->disableOriginalConstructor()->getMock();

		return new ApiHelperFactory(
			$titleLookup,
			$exceptionLocalizer,
			$dataTypeLookup,
			$entityFactory,
			$summaryFormatter,
			$entityRevisionLookup,
			$entityStore,
			$entityPermissionChecker,
			$editFilterHookRunner
		);
	}

	private function newApiModule() {
		$language = Language::factory( 'en' );

		$result = $this->getMockBuilder( 'ApiResult' )
			->disableOriginalConstructor()
			->getMock();

		$result->expects( $this->any() )
			->method( 'getIsRawMode' )
			->will( $this->returnValue( false ) );

		$api = $this->getMockBuilder( 'ApiBase' )
			->disableOriginalConstructor()
			->getMock();

		$api->expects( $this->any() )
			->method( 'getResult' )
			->will( $this->returnValue( $result ) );

		$api->expects( $this->any() )
			->method( 'getLanguage' )
			->will( $this->returnValue( $language ) );

		return $api;
	}

	public function testGetResultBuilder() {
		$api = $this->newApiModule();
		$factory = $this->newApiHelperFactory();

		$resultBuilder = $factory->getResultBuilder( $api );
		$this->assertInstanceOf( 'Wikibase\Api\ResultBuilder', $resultBuilder );
	}

	public function testGetErrorReporter() {
		$api = $this->newApiModule();
		$factory = $this->newApiHelperFactory();

		$errorReporter = $factory->getErrorReporter( $api );
		$this->assertInstanceOf( 'Wikibase\Api\ApiErrorReporter', $errorReporter );
	}

	public function testGetSerializerFactory() {
		$factory = $this->newApiHelperFactory();

		$serializerFactory = $factory->getSerializerFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\Serializers\SerializerFactory', $serializerFactory );
	}

	public function testGetEntitySaveHelper() {
		$factory = $this->newApiHelperFactory();

		$helper = $factory->getEntitySaveHelper( $this->newApiModule() );
		$this->assertInstanceOf( 'Wikibase\Api\EntitySaveHelper', $helper );
	}

	public function testGetEntityLoadHelper() {
		$factory = $this->newApiHelperFactory();

		$helper = $factory->getEntityLoadHelper( $this->newApiModule() );
		$this->assertInstanceOf( 'Wikibase\Api\EntityLoadHelper', $helper );
	}

}
