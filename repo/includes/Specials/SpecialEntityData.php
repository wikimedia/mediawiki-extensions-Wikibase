<?php

namespace Wikibase\Repo\Specials;

use HttpError;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataRequestHandler;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikibase\Repo\WikibaseRepo;

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

	/**
	 * @var EntityDataRequestHandler|null
	 */
	private $requestHandler = null;

	/**
	 * @var EntityDataFormatProvider|null
	 */
	private $entityDataFormatProvider = null;

	public function __construct() {
		parent::__construct( 'EntityData' );

		$this->entityDataFormatProvider = new EntityDataFormatProvider();
	}

	/**
	 * Sets the request handler to be used by the special page.
	 * May be used when a particular instance of EntityDataRequestHandler is already
	 * known, e.g. during testing.
	 *
	 * If no request handler is set using this method, a default handler is created
	 * on demand by initDependencies().
	 *
	 * @param EntityDataRequestHandler $requestHandler
	 */
	public function setRequestHandler( EntityDataRequestHandler $requestHandler ) {
		$this->requestHandler = $requestHandler;
	}

	/**
	 * Initialize any un-initialized members from global context.
	 * In particular, this initializes $this->requestHandler
	 *
	 * This is called by
	 */
	protected function initDependencies() {
		if ( $this->requestHandler === null ) {
			$this->requestHandler = $this->newDefaultRequestHandler();
		}
	}

	/**
	 * Creates a EntityDataRequestHandler based on global defaults.
	 *
	 * @return EntityDataRequestHandler
	 */
	private function newDefaultRequestHandler() {
		global $wgUseSquid, $wgApiFrameOptions;

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$entityRevisionLookup = $wikibaseRepo->getEntityRevisionLookup();
		$entityRedirectLookup = $wikibaseRepo->getStore()->getEntityRedirectLookup();
		$titleLookup = $wikibaseRepo->getEntityTitleLookup();
		$entityIdParser = $wikibaseRepo->getEntityIdParser();

		$serializationService = new EntityDataSerializationService(
			$wikibaseRepo->getStore()->getEntityLookup(),
			$titleLookup,
			$wikibaseRepo->getPropertyDataTypeLookup(),
			$wikibaseRepo->getValueSnakRdfBuilderFactory(),
			$wikibaseRepo->getEntityRdfBuilderFactory(),
			$wikibaseRepo->getSiteLookup()->getSites(),
			$this->entityDataFormatProvider,
			$wikibaseRepo->getCompactBaseDataModelSerializerFactory(),
			$wikibaseRepo->getCompactEntitySerializer(),
			$wikibaseRepo->getSiteLookup(),
			$wikibaseRepo->getRdfVocabulary()
		);

		$maxAge = $wikibaseRepo->getSettings()->getSetting( 'dataSquidMaxage' );
		$formats = $wikibaseRepo->getSettings()->getSetting( 'entityDataFormats' );
		$this->entityDataFormatProvider->setFormatWhiteList( $formats );

		$defaultFormat = empty( $formats ) ? 'html' : $formats[0];

		// build a mapping of formats to file extensions and include HTML
		$supportedExtensions = [];
		$supportedExtensions['html'] = 'html';
		foreach ( $this->entityDataFormatProvider->getSupportedFormats() as $format ) {
			$ext = $this->entityDataFormatProvider->getExtension( $format );

			if ( $ext !== null ) {
				$supportedExtensions[$format] = $ext;
			}
		}

		$uriManager = new EntityDataUriManager(
			$this->getPageTitle(),
			$supportedExtensions,
			$titleLookup
		);

		$disabledRdfExportEntityTypes = $wikibaseRepo->getSettings()->getSetting( 'disabledRdfExportEntityTypes' );

		return new EntityDataRequestHandler(
			$uriManager,
			$titleLookup,
			$entityIdParser,
			$entityRevisionLookup,
			$entityRedirectLookup,
			$serializationService,
			$this->entityDataFormatProvider,
			$disabledRdfExportEntityTypes,
			$defaultFormat,
			$maxAge,
			$wgUseSquid,
			$wgApiFrameOptions
		);
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 *
	 * @throws HttpError
	 */
	public function execute( $subPage ) {
		$this->initDependencies();

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

	/**
	 * @param EntityDataFormatProvider $entityDataFormatProvider
	 *
	 * TODO: Inject them
	 */
	public function setEntityDataFormatProvider(
		EntityDataFormatProvider $entityDataFormatProvider
	) {
		$this->entityDataFormatProvider = $entityDataFormatProvider;
	}

}
