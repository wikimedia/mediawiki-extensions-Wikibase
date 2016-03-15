<?php

namespace Wikibase\Test\Repo\Api;

use ApiBase;
use ApiResult;
use HashSiteStore;
use Language;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\Api\EntityLoadingHelper;
use Wikibase\Repo\Api\EntitySavingHelper;
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\SummaryFormatter;

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
		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$exceptionLocalizer = $this->getMock( ExceptionLocalizer::class );
		$dataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$summaryFormatter = $this->getMockBuilder( SummaryFormatter::class )
			->disableOriginalConstructor()->getMock();
		$entityRevisionLookup = $this->getMock( EntityRevisionLookup::class );
		$editEntityFactory = $this->getMockBuilder( EditEntityFactory::class )
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

	/**
	 * @return ApiBase
	 */
	private function newApiModule() {
		$language = Language::factory( 'en' );

		$result = $this->getMockBuilder( ApiResult::class )
			->disableOriginalConstructor()
			->getMock();

		$api = $this->getMockBuilder( ApiBase::class )
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
