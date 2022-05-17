<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionLookup;
use Serializers\Serializer;
use SiteLookup;
use TitleFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\Store\EntityByLinkedTitleLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;

/**
 * A factory class for API helper objects.
 *
 * @note: This is a high level factory which should not be injected or passed around.
 * It should only be used when bootstrapping from a static context.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ApiHelperFactory {

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleStoreLookup;

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
	 * @var MediawikiEditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var PermissionManager
	 */
	private $permissionManager;

	/** @var RevisionLookup */
	private $revisionLookup;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @var EntityByLinkedTitleLookup|null
	 */
	private $entityByLinkedTitleLookup;

	/**
	 * @var EntityFactory|null
	 */
	private $entityFactory;

	/**
	 * @var EntityStore|null
	 */
	private $entityStore;

	/**
	 * @param EntityTitleStoreLookup $entityTitleStoreLookup
	 * @param ExceptionLocalizer $exceptionLocalizer
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param SiteLookup $siteLookup
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param MediawikiEditEntityFactory $editEntityFactory
	 * @param SerializerFactory $serializerFactory
	 * @param Serializer $entitySerializer
	 * @param EntityIdParser $idParser
	 * @param PermissionManager $permissionManager
	 * @param RevisionLookup $revisionLookup,
	 * @param TitleFactory $titleFactory
	 * @param EntityByLinkedTitleLookup|null $entityByLinkedTitleLookup
	 * @param EntityFactory|null $entityFactory
	 * @param EntityStore|null $entityStore
	 */
	public function __construct(
		EntityTitleStoreLookup $entityTitleStoreLookup,
		ExceptionLocalizer $exceptionLocalizer,
		PropertyDataTypeLookup $dataTypeLookup,
		SiteLookup $siteLookup,
		SummaryFormatter $summaryFormatter,
		EntityRevisionLookup $entityRevisionLookup,
		MediawikiEditEntityFactory $editEntityFactory,
		SerializerFactory $serializerFactory,
		Serializer $entitySerializer,
		EntityIdParser $idParser,
		PermissionManager $permissionManager,
		RevisionLookup $revisionLookup,
		TitleFactory $titleFactory,
		EntityByLinkedTitleLookup $entityByLinkedTitleLookup = null,
		EntityFactory $entityFactory = null,
		EntityStore $entityStore = null
	) {
		$this->entityTitleStoreLookup = $entityTitleStoreLookup;
		$this->exceptionLocalizer = $exceptionLocalizer;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->siteLookup = $siteLookup;
		$this->summaryFormatter = $summaryFormatter;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->editEntityFactory = $editEntityFactory;
		$this->serializerFactory = $serializerFactory;
		$this->entitySerializer = $entitySerializer;
		$this->idParser = $idParser;
		$this->permissionManager = $permissionManager;
		$this->revisionLookup = $revisionLookup;
		$this->titleFactory = $titleFactory;
		$this->entityByLinkedTitleLookup = $entityByLinkedTitleLookup;
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
			$this->entityTitleStoreLookup,
			$this->serializerFactory,
			$this->entitySerializer,
			$this->siteLookup,
			$this->dataTypeLookup,
			$this->idParser,
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
	 * Return an EntitySavingHelper object for use in Api modules
	 *
	 * @warning The resulting EntitySavingHelper may be stateful and should only
	 *          be used for a single API request.
	 *
	 * @param ApiBase $apiBase
	 *
	 * @return EntitySavingHelper
	 */
	public function getEntitySavingHelper( ApiBase $apiBase ) {
		$helper = new EntitySavingHelper(
			$apiBase->isWriteMode(),
			$apiBase->needsToken(),
			$this->revisionLookup,
			$this->titleFactory,
			$this->idParser,
			$this->entityRevisionLookup,
			$this->entityTitleStoreLookup,
			$this->getErrorReporter( $apiBase ),
			$this->summaryFormatter,
			$this->editEntityFactory,
			$this->permissionManager
		);

		if ( $this->entityByLinkedTitleLookup ) {
			$helper->setEntityByLinkedTitleLookup( $this->entityByLinkedTitleLookup );
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
	 * @return EntityLoadingHelper
	 */
	public function getEntityLoadingHelper( ApiBase $apiBase ) {
		$helper = new EntityLoadingHelper(
			$this->revisionLookup,
			$this->titleFactory,
			$this->idParser,
			$this->entityRevisionLookup,
			$this->entityTitleStoreLookup,
			$this->getErrorReporter( $apiBase )
		);

		if ( $this->entityByLinkedTitleLookup ) {
			$helper->setEntityByLinkedTitleLookup( $this->entityByLinkedTitleLookup );
		}

		return $helper;
	}

}
