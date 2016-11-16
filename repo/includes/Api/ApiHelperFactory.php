<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use DataValues\Serializers\DataValueSerializer;
use Serializers\Serializer;
use SiteStore;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\EditEntityFactory;
use Wikibase\EntityFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
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
	 * @var EntityTitleStoreLookup
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

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var SiteLinkLookup|null
	 */
	private $siteLinkLookup;

	/**
	 * @var EntityFactory|null
	 */
	private $entityFactory;

	/**
	 * @var EntityStore|null
	 */
	private $entityStore;

	/**
	 * ApiHelperFactory constructor.
	 *
	 * @param EntityTitleStoreLookup $titleLookup
	 * @param ExceptionLocalizer $exceptionLocalizer
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param SiteStore $siteStore
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EditEntityFactory $editEntityFactory
	 * @param Serializer $entitySerializer
	 * @param EntityIdParser $idParser
	 * @param SiteLinkLookup|null $siteLinkLookup
	 * @param EntityFactory|null $entityFactory
	 * @param EntityStore|null $entityStore
	 */
	public function __construct(
		EntityTitleStoreLookup $titleLookup,
		ExceptionLocalizer $exceptionLocalizer,
		PropertyDataTypeLookup $dataTypeLookup,
		SiteStore $siteStore,
		SummaryFormatter $summaryFormatter,
		EntityRevisionLookup $entityRevisionLookup,
		EditEntityFactory $editEntityFactory,
		Serializer $entitySerializer,
		EntityIdParser $idParser,
		SiteLinkLookup $siteLinkLookup = null,
		EntityFactory $entityFactory = null,
		EntityStore $entityStore = null
	) {
		$this->titleLookup = $titleLookup;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->siteStore = $siteStore;
		$this->summaryFormatter = $summaryFormatter;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->editEntityFactory = $editEntityFactory;
		$this->entitySerializer = $entitySerializer;
		$this->idParser = $idParser;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->entityFactory = $entityFactory;
		$this->entityStore = $entityStore;
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
			$this->entitySerializer,
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
	 * @warning The resulting EntitySavingHelper may be stateful and should only
	 *          be used for a single API request.
	 *
	 * @param ApiBase $apiBase
	 *
	 * @return EntitySavingHelper a new EntitySavingHelper instance
	 */
	public function getEntitySavingHelper( ApiBase $apiBase ) {
		$helper = new EntitySavingHelper(
			$apiBase,
			$this->idParser,
			$this->entityRevisionLookup,
			$this->getErrorReporter( $apiBase ),
			$this->summaryFormatter,
			$this->editEntityFactory
		);

		if ( $this->siteLinkLookup ) {
			$helper->setSiteLinkLookup( $this->siteLinkLookup );
		}

		if ( $this->entityFactory ) {
			$helper->setEntityFactory( $this->entityFactory );
		}

		if ( $this->entityStore ) {
			$helper->setEntityStore( $this->entityStore );
		}

		return $helper;
	}

	/**
	 * Return an EntityLoadingHelper object for use in Api modules
	 *
	 * @warning The resulting EntityLoadingHelper may be stateful and should only
	 *          be used for a single API request.
	 *
	 * @param ApiBase $apiBase
	 *
	 * @return EntityLoadingHelper a new EntityLoadingHelper instance
	 */
	public function getEntityLoadingHelper( ApiBase $apiBase ) {
		$helper = new EntityLoadingHelper(
			$apiBase,
			$this->idParser,
			$this->entityRevisionLookup,
			$this->getErrorReporter( $apiBase )
		);

		if ( $this->siteLinkLookup ) {
			$helper->setSiteLinkLookup( $this->siteLinkLookup );
		}

		return $helper;
	}

}
