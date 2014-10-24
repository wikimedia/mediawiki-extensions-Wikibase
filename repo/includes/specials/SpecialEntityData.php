<?php

namespace Wikibase\Repo\Specials;

use HttpError;
use Wikibase\EntityFactory;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
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
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thomas Pellissier Tanon
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 */
class SpecialEntityData extends SpecialWikibasePage {

	/**
	 * @var EntityDataRequestHandler
	 */
	protected $requestHandler = null;

	/**
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'EntityData' );
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

		$repo = WikibaseRepo::getDefaultInstance();

		$entityRevisionLookup = $repo->getEntityRevisionLookup();
		$titleLookup = $repo->getEntityTitleLookup();
		$entityIdParser = $repo->getEntityIdParser();

		$serializationOptions = new SerializationOptions();
		$serializerFactory = new SerializerFactory(
			$serializationOptions,
			$repo->getPropertyDataTypeLookup(),
			EntityFactory::singleton()
		);

		$serializationService = new EntityDataSerializationService(
			$repo->getSettings()->getSetting( 'conceptBaseUri' ),
			$this->getPageTitle()->getCanonicalURL() . '/',
			$repo->getStore()->getEntityLookup(),
			$titleLookup,
			$serializerFactory,
			$repo->getSiteStore()->getSites()
		);

		$maxAge = $repo->getSettings()->getSetting( 'dataSquidMaxage' );
		$formats = $repo->getSettings()->getSetting( 'entityDataFormats' );
		$serializationService->setFormatWhiteList( $formats );

		$defaultFormat = empty( $formats ) ? 'html' : $formats[0];

		// build a mapping of formats to file extensions and include HTML
		$supportedExtensions = array();
		$supportedExtensions['html'] = 'html';
		foreach ( $serializationService->getSupportedFormats() as $format ) {
			$ext = $serializationService->getExtension( $format );

			if ( $ext !== null ) {
				$supportedExtensions[$format] = $ext;
			}
		}

		$uriManager = new EntityDataUriManager(
			$this->getPageTitle(),
			$supportedExtensions,
			$titleLookup
		);

		return new EntityDataRequestHandler(
			$uriManager,
			$titleLookup,
			$entityIdParser,
			$entityRevisionLookup,
			$serializationService,
			$defaultFormat,
			$maxAge,
			$wgUseSquid,
			$wgApiFrameOptions
		);
	}

	/**
	 * Main method.
	 *
	 * @since 0.4
	 *
	 * @param string|null $subPage
	 *
	 * @throws HttpError
	 * @return bool
	 */
	public function execute( $subPage ) {
		$this->initDependencies();

		// If there is no ID, show an HTML form
		// TODO: Don't do this if HTML is not acceptable according to HTTP headers.
		if ( !$this->requestHandler->canHandleRequest( $subPage, $this->getRequest() ) ) {
			$this->showForm();
			return true;
		}

		$this->requestHandler->handleRequest( $subPage, $this->getRequest(), $this->getOutput() );

		return true;
	}

	/**
	 * Shows an informative page to the user; Called when there is no entity to output.
	 */
	public function showForm() {
		//TODO: show input form with selector for format and field for ID. Add some explanation,
		//      point to meta-info like schema and license, and generally be a helpful data endpoint.
		$this->getOutput()->showErrorPage( 'wikibase-entitydata-title', 'wikibase-entitydata-text' );
	}

}
