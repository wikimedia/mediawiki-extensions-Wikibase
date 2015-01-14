<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\EntityFactory;
use Wikibase\Lib\Localizer\ExceptionLocalizer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * A factory class for API helper objects.
 *
 * @note: This is a high level factory which should not be injected or passed around.
 * It should only be used when bootstrapping from a static context.
 *
 * @todo: Factor functionality out of ApiWikibase into separate helper classes, and
 * make them available via ApiHelperFactory.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ApiHelperFactory {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var ExceptionLocalizer
	 */
	private $exceptionLocalizer;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var EntityFactory
	 */
	private $entityFactory;

	public function __construct(
		EntityTitleLookup $titleLookup,
		ExceptionLocalizer $exceptionLocalizer,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityFactory $entityFactory
	) {

		$this->titleLookup = $titleLookup;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityFactory = $entityFactory;
	}

	/**
	 * Returns a ResultBuilder wrapping the ApiResult of the given API module.
	 *
	 * @param ApiBase $api
	 * @param SerializationOptions $defaultOptions
	 *
	 * @return ResultBuilder
	 */
	public function getResultBuilder( ApiBase $api, SerializationOptions $defaultOptions = null ) {
		return new ResultBuilder(
			$api->getResult(),
			$this->titleLookup,
			$this->getSerializerFactory( $defaultOptions ) );
	}

	/**
	 * Returns an ApiErrorReporter suitable for reporting errors from the given API module.
	 *
	 * @param ApiBase $api
	 *
	 * @return ApiErrorReporter
	 */
	public function getErrorReporter( ApiBase $api ) {
		return new ApiErrorReporter(
			$api,
			$this->exceptionLocalizer,
			$api->getLanguage()
		);
	}

	/**
	 * Returns a serializer factory to be used when constructing API results.
	 *
	 * @param SerializationOptions $defaultOptions
	 *
	 * @return SerializerFactory
	 */
	public function getSerializerFactory( SerializationOptions $defaultOptions = null ) {
		return new SerializerFactory(
			$defaultOptions,
			$this->dataTypeLookup,
			$this->entityFactory
		);
	}

}
 