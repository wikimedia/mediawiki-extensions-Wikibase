<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\EntityFactory;
use Wikibase\Lib\Localizer\ExceptionLocalizer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\SummaryFormatter;

/**
 * A factory class for API helper objects.
 *
 * @note: This is a high level factory which should not be injected or passed around.
 * It should only be used when bootstrapping from a static context.
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

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var EntityPermissionChecker
	 */
	private $entityPermissionChecker;

	/**
	 * @var EditFilterHookRunner
	 */
	private $editFilterHookRunner;

	public function __construct(
		EntityTitleLookup $titleLookup,
		ExceptionLocalizer $exceptionLocalizer,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityFactory $entityFactory,
		SummaryFormatter $summaryFormatter,
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $entityPermissionChecker,
		EditFilterHookRunner $editFilterHookRunner
	) {
		$this->titleLookup = $titleLookup;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityFactory = $entityFactory;
		$this->summaryFormatter = $summaryFormatter;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->entityPermissionChecker = $entityPermissionChecker;
		$this->editFilterHookRunner = $editFilterHookRunner;
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

	/**
	 * Return an EntitySaveHelper object for use in Api modules
	 *
	 * @param ApiBase $apiBase
	 *
	 * @return EntitySaveHelper
	 */
	public function getEntitySaveHelper( ApiBase $apiBase ) {
		return new EntitySaveHelper(
			$apiBase,
			$this->getErrorReporter( $apiBase ),
			$this->summaryFormatter,
			$this->titleLookup,
			$this->entityRevisionLookup,
			$this->entityStore,
			$this->entityPermissionChecker,
			$this->editFilterHookRunner
		);
	}

	/**
	 * Return an EntityLoadHelper object for use in Api modules
	 *
	 * @param ApiBase $apiBase
	 *
	 * @return EntityLoadHelper
	 */
	public function getEntityLoadHelper( ApiBase $apiBase ) {
		return new EntityLoadHelper(
			$this->entityRevisionLookup,
			$this->getErrorReporter( $apiBase )
		);
	}

}
