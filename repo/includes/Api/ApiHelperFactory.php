<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use DataValues\Serializers\DataValueSerializer;
use SiteStore;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\SummaryFormatter;

/**
 * A factory class for API helper objects.
 *
 * @note: This is a high level factory which should not be injected or passed around.
 * It should only be used when bootstrapping from a static context.
 *
 * @license GPL-2.0+
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
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	public function __construct(
		EntityTitleLookup $titleLookup,
		ExceptionLocalizer $exceptionLocalizer,
		PropertyDataTypeLookup $dataTypeLookup,
		SiteStore $siteStore,
		SummaryFormatter $summaryFormatter,
		EntityRevisionLookup $entityRevisionLookup,
		EditEntityFactory $editEntityFactory
	) {
		$this->titleLookup = $titleLookup;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->siteStore = $siteStore;
		$this->summaryFormatter = $summaryFormatter;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->editEntityFactory = $editEntityFactory;
	}

	/**
	 * Returns a ResultBuilder wrapping the ApiResult of the given API module.
	 *
	 * @param ApiBase $api
	 *
	 * @return ResultBuilder
	 */
	public function getResultBuilder( ApiBase $api ) {
		return new ResultBuilder(
			$api->getResult(),
			$this->titleLookup,
			$this->newSerializerFactory(),
			$this->siteStore,
			$this->dataTypeLookup,
			true // The mediawiki api should always be given metadata
		);
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
	 * @return SerializerFactory
	 */
	public function newSerializerFactory() {
		return new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);
	}

	/**
	 * Return an EntitySavingHelper object for use in Api modules
	 *
	 * @param ApiBase $apiBase
	 *
	 * @return EntitySavingHelper
	 */
	public function getEntitySavingHelper( ApiBase $apiBase ) {
		return new EntitySavingHelper(
			$apiBase,
			$this->getErrorReporter( $apiBase ),
			$this->summaryFormatter,
			$this->editEntityFactory
		);
	}

	/**
	 * Return an EntityLoadingHelper object for use in Api modules
	 *
	 * @param ApiBase $apiBase
	 *
	 * @return EntityLoadingHelper
	 */
	public function getEntityLoadingHelper( ApiBase $apiBase ) {
		return new EntityLoadingHelper(
			$this->entityRevisionLookup,
			$this->getErrorReporter( $apiBase )
		);
	}

}
