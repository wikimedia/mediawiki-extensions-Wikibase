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

		return new ApiHelperFactory(
			$titleLookup,
			$exceptionLocalizer,
			$dataTypeLookup,
			$entityFactory
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

}
