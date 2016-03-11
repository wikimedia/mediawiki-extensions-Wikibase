<?php

namespace Wikibase\Test\Repo\Api;

use HashSiteStore;
use Language;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Api\ApiHelperFactory
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ApiHelperFactoryTest extends \PHPUnit_Framework_TestCase {

	private function newApiHelperFactory() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$exceptionLocalizer = $this->getMock( 'Wikibase\Repo\Localizer\ExceptionLocalizer' );
		$dataTypeLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup' );
		$summaryFormatter = $this->getMockBuilder( 'Wikibase\SummaryFormatter' )
			->disableOriginalConstructor()->getMock();
		$entityRevisionLookup = $this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' );
		$editEntityFactory = $this->getMockBuilder( 'Wikibase\EditEntityFactory' )
			->disableOriginalConstructor()->getMock();

		return new ApiHelperFactory(
			$titleLookup,
			$exceptionLocalizer,
			$dataTypeLookup,
			new HashSiteStore(),
			$summaryFormatter,
			$entityRevisionLookup,
			$editEntityFactory
		);
	}

	private function newApiModule() {
		$language = Language::factory( 'en' );

		$result = $this->getMockBuilder( 'ApiResult' )
			->disableOriginalConstructor()
			->getMock();

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
		$this->assertInstanceOf( 'Wikibase\Repo\Api\ResultBuilder', $resultBuilder );
	}

	public function testGetErrorReporter() {
		$api = $this->newApiModule();
		$factory = $this->newApiHelperFactory();

		$errorReporter = $factory->getErrorReporter( $api );
		$this->assertInstanceOf( 'Wikibase\Repo\Api\ApiErrorReporter', $errorReporter );
	}

	public function testGetEntitySavingHelper() {
		$factory = $this->newApiHelperFactory();

		$helper = $factory->getEntitySavingHelper( $this->newApiModule() );
		$this->assertInstanceOf( 'Wikibase\Repo\Api\EntitySavingHelper', $helper );
	}

	public function testGetEntityLoadingHelper() {
		$factory = $this->newApiHelperFactory();

		$helper = $factory->getEntityLoadingHelper( $this->newApiModule() );
		$this->assertInstanceOf( 'Wikibase\Repo\Api\EntityLoadingHelper', $helper );
	}

}
