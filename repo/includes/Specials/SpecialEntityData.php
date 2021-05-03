<?php

namespace Wikibase\Repo\Specials;

use HtmlCacheUpdater;
use HttpError;
use Psr\Log\LoggerInterface;
use Serializers\Serializer;
use SiteLookup;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataRequestHandler;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\Store;

/**
 * Special page to act as a data endpoint for the linked data web.
 * The web server should generally be configured to make this accessible via a canonical URL/URI,
 * such as <http://my.domain.org/entity/Q12345>.
 *
 * Note that this is implemented as a special page and not a per-page action, so there is no need
 * for the web server to map ID prefixes to wiki namespaces.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thomas Pellissier Tanon
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 */
class SpecialEntityData extends SpecialWikibasePage {

	public const SPECIAL_PAGE_NAME = 'EntityData';

	/**
	 * @var EntityDataRequestHandler
	 */
	private $requestHandler;

	/**
	 * @var EntityDataFormatProvider
	 */
	private $entityDataFormatProvider;

	public function __construct(
		EntityDataRequestHandler $requestHandler,
		EntityDataFormatProvider $entityDataFormatProvider
	) {
		parent::__construct( self::SPECIAL_PAGE_NAME );

		$this->requestHandler = $requestHandler;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
	}

	public static function factory(
		HtmlCacheUpdater $htmlCacheUpdater,
		SiteLookup $siteLookup,
		SerializerFactory $compactBaseDataModelSerializerFactory,
		Serializer $compactEntitySerializer,
		EntityContentFactory $entityContentFactory,
		EntityDataFormatProvider $entityDataFormatProvider,
		EntityDataUriManager $entityDataUriManager,
		EntityIdParser $entityIdParser,
		EntityLookup $entityLookup,
		EntityRdfBuilderFactory $entityRdfBuilderFactory,
		EntityRevisionLookup $entityRevisionLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		LoggerInterface $logger,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		RdfVocabulary $rdfVocabulary,
		SettingsArray $repoSettings,
		Store $store,
		array $subEntityTypesMap,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory
	): self {
		global $wgUseCdn, $wgApiFrameOptions;

		// TODO move EntityRedirectLookup to service container and inject it directly
		$entityRedirectLookup = $store->getEntityRedirectLookup();

		$serializationService = new EntityDataSerializationService(
			$entityLookup,
			$entityTitleLookup,
			$entityContentFactory,
			$propertyDataTypeLookup,
			$valueSnakRdfBuilderFactory,
			$entityRdfBuilderFactory,
			$entityDataFormatProvider,
			$compactBaseDataModelSerializerFactory,
			$compactEntitySerializer,
			$siteLookup,
			$rdfVocabulary
		);

		$maxAge = $repoSettings->getSetting( 'dataCdnMaxAge' );
		$formats = $entityDataFormatProvider->getAllowedFormats();

		$defaultFormat = empty( $formats ) ? 'html' : $formats[0];

		$entityDataRequestHandler = new EntityDataRequestHandler(
			$entityDataUriManager,
			$htmlCacheUpdater,
			$entityIdParser,
			$entityRevisionLookup,
			$entityRedirectLookup,
			$serializationService,
			$entityDataFormatProvider,
			$logger,
			$repoSettings->getSetting( 'entityTypesWithoutRdfOutput' ),
			$defaultFormat,
			$maxAge,
			$wgUseCdn,
			$wgApiFrameOptions,
			$subEntityTypesMap
		);

		return new self( $entityDataRequestHandler, $entityDataFormatProvider );
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 *
	 * @throws HttpError
	 */
	public function execute( $subPage ) {
		// If there is no ID, show an HTML form
		// TODO: Don't do this if HTML is not acceptable according to HTTP headers.
		if ( !$this->requestHandler->canHandleRequest( $subPage, $this->getRequest() ) ) {
			$this->showForm();
			return;
		}

		$this->requestHandler->handleRequest( $subPage, $this->getRequest(), $this->getOutput() );
	}

	/**
	 * Shows an informative page to the user; Called when there is no entity to output.
	 */
	public function showForm() {
		//TODO: show input form with selector for format and field for ID. Add some explanation,
		//      point to meta-info like schema and license, and generally be a helpful data endpoint.
		$supportedFormats = $this->entityDataFormatProvider->getSupportedExtensions();
		$supportedFormats[] = 'html';
		$this->getOutput()->showErrorPage(
			'wikibase-entitydata-title',
			'wikibase-entitydata-text',
			[ $this->getOutput()->getLanguage()->commaList( $supportedFormats ) ]
		);
	}

}
