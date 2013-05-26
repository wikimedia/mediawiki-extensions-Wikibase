<?php

use DataTypes\DataTypeFactory;
use \Wikibase\Lib\EntityIdParser;
use \Wikibase\Lib\EntityIdFormatter;
use \Wikibase\EntityContent;
use \Wikibase\EntityContentFactory;
use \Wikibase\EntityId;
use \Wikibase\RdfSerializer;

/**
 * Special page to act as a data endpoint for the linked data web.
 * The web server should generally be configured to make this accessible via a canonical URL/URI,
 * such as <http://my.domain.org/data/Q12345>.
 *
 * Note that this is implemented as a special page and not a per-page action, so there is no need
 * for the web server to map ID prefixes to wiki namespaces.
 *
 * Also note that we are using the API's serialization facility to ensure a consistent external
 * representation of data entities. Using the ContentHandler to serialize the entity would expose
 * internal implementation details.
 *
 * @since 0.4
 *
 * @file 
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thomas Pellissier Tanon
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 */
class SpecialEntityData extends SpecialWikibasePage {

	/**
	 * @var int Cache dureation in seconds
	 */
	protected $maxAge = 0;

	/**
	 * @var EntityDataSerializationService
	 */
	protected $service;

	/**
	 * @var EntityIdParser
	 */
	protected $entityIdParser;

	/**
	 * @var EntityIdFormatter
	 */
	protected $entityIdFormatter;

	/**
	 * @var EntityContentFactory
	 */
	protected $entityContentFactory;

	/**
	 * @var string
	 */
	protected $defaultFormat;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'EntityData' );
	}

	/**
	 * Initialize members from global context.
	 * This is poor man's inverse dependency injection.
	 */
	protected function initDependencies() {
		// Initialize serialization service.
		// TODO: use reverse DI facility (global registry/factory)
		$repo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		$this->entityContentFactory = EntityContentFactory::singleton();
		$this->entityIdParser = $repo->getEntityIdParser();
		$this->entityIdFormatter = $repo->getIdFormatter();

		$this->service = new EntityDataSerializationService(
			$repo->getRdfBaseURI(),
			$this->getTitle()->getCanonicalURL() . '/',
			\Wikibase\StoreFactory::getStore()->getEntityLookup(),
			$repo->getDataTypeFactory(),
			$this->entityIdFormatter
		);

		$formats = \Wikibase\Settings::get( 'entityDataFormats' );
		$this->service->setFormatWhiteList( $formats );

		$this->defaultFormat = empty( $formats ) ? 'html' : $formats[0];
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

		$revision = 0;
		$format = '';

		$requestedSubPage = $subPage;

		// get format from $subPage or request param
		if ( preg_match( '#\.([-./\w]+)$#', $subPage, $m ) ) {
			$subPage = preg_replace( '#\.([-./\w]+)$#', '', $subPage );
			$format = $m[1];
		}

		$format = $this->getRequest()->getText( 'format', $format );

		//TODO: malformed revision IDs should trigger a code 400

		// get revision from $subPage or request param
		if ( preg_match( '#:(\w+)$#', $subPage, $m ) ) {
			$subPage = preg_replace( '#:(\w+)$#', '', $subPage );
			$revision = (int)$m[1];
		}

		$revision = $this->getRequest()->getInt( 'oldid', $revision );
		$revision = $this->getRequest()->getInt( 'revision', $revision );

		// get entity from remaining $subPage or request param
		$id = $subPage;
		$id = $this->getRequest()->getText( 'id', $id );

		// If there is no ID, show an HTML form
		// TODO: Don't do this if HTML is not acceptable according to HTTP headers.
		if ( $id === null || $id === '' ) {
			$this->showForm();
			return true;
		}

		try {
			$entityId = $this->entityIdParser->parse( $id );
		} catch ( \ValueParsers\ParseException $ex ) {
			throw new \HttpError( 400, wfMessage( 'wikibase-entitydata-bad-id' )->params( $id ) );
		}

		//XXX: allow for logged in users only?
		if ( $this->getRequest()->getText( 'action' ) === 'purge' ) {
			$this->purge( $entityId, $format, $revision );
			//XXX: Now what? Proceed to show the data?
		}

		if ( $format === null || $format === '' ) {
			// if no format is given, apply content negotiation and return.

			$headers = $this->getRequest()->getAllHeaders();
			if ( isset( $headers['ACCEPT'] ) ) {
				$parser = new \Wikibase\HttpAcceptParser();
				$accept = $parser->parseWeights( $headers['ACCEPT'] );
			} else {
				// anything goes
				$accept = array(
					$this->defaultFormat => 1,
					'*' => 0.1 // just to make extra sure
				);
			}

			$this->httpContentNegotiation( $accept, $entityId, $revision );
			return false;
		} else {
			$format = strtolower( $format );

			// if the format is HTML, redirect to the entity's wiki page
			if ( $format === 'html' || $format === 'htm' || $format === 'text/html' ) {
				$url = $this->getCanonicalUrl( $entityId, 'html', $revision );
				$this->getOutput()->redirect( $url, 303 );
				return false;
			}

			// normalize format name (note that HTML may not be known to the service)
			$canonicalFormat = $this->service->getFormatName( $format );

			if ( $canonicalFormat === null ) {
				throw new \HttpError( 415, wfMessage( 'wikibase-entitydata-unsupported-format' )->params( $format ) );
			}

			$format = $canonicalFormat;
		}

		// we should know the format now.
		assert( $format !== null && $format !== '' );

		if ( $requestedSubPage !== null && $requestedSubPage !== '' ) {
			// if subpage syntax is used, always enforce the canonical form
			$canonicalSubPage = $this->getSubPageName( $entityId, $format, $revision );

			if ( $requestedSubPage !== $canonicalSubPage ) {
				$url = $this->getCanonicalUrl( $entityId, $format, $revision );
				$this->getOutput()->redirect( $url, 301 );
				return false;
			}
		}

		$this->showData( $format, $entityId, $revision );
		return true;
	}

	/**
	 * Purges the entity data identified by the subPage parameter from any HTTP caches.
	 * Does nothing if $wgUseSquid is not set.
	 *
	 * @todo: how to test this?
	 *
	 * @param EntityId $id       The entity
	 * @param string   $format   The (normalized) format name, or ''
	 * @param int      $revision The revision ID (use 0 for current)
	 */
	protected function purge( EntityId $id, $format = '', $revision = 0 ) {
		global $wgUseSquid;

		if ( $wgUseSquid ) {
			//TODO: Purge all formats based on the ID, instead of just the one currently requested.
			//TODO: Also purge when an entity gets edited, using the new TitleSquidURLs hook.
			$subPage = $this->getSubPageName( $id, $format, $revision );

			$title = $this->getTitle( $subPage );

			$urls = array();
			$urls[] = $title->getInternalURL();

			$u = new SquidUpdate( $urls );
			$u->doUpdate();
		}
	}

	/**
	 * Returns the canonical subpage name used to address a given set
	 * of entity data.
	 *
	 * @param EntityId $id       The entity
	 * @param string   $format   The (normalized) format name, or ''
	 * @param int      $revision The revision ID (use 0 for current)
	 *
	 * @return string
	 */
	public function getSubPageName( EntityId $id, $format = '', $revision = 0 ) {
		$subPage = $this->entityIdFormatter->format( $id );

		//TODO: Use upper case everywhere. EntityIdFormatter should do the right thing.
		$subPage = strtoupper( $subPage );

		if ( $revision > 0 ) {
			$subPage .= ':' . $revision;
		}

		if ( $format !== null && $format !== '' ) {
			$ext = $this->service->getExtension( $format );

			if ( $ext === null ) {
				// if no extension is known, use the format name as the extension
				$ext = $format;
			}

			$subPage .= '.' . $ext;
		}

		return $subPage;
	}

	/**
	 * Applies HTTP content negotiation.
	 * If the negotiation is successfull, this method will set the appropriate redirect
	 * in the OutputPage object and return. Otherwise, an HttpError is thrown.
	 *
	 * @param string[] $accept an associative array mapping MIME types to preference weights,
	 *        for use with HttpAcceptNegotiator.
	 * @param EntityId $id The ID of the entity to show
	 * @param int      $revision The desired revision
	 *
	 * @throws HttpError
	 */
	protected function httpContentNegotiation( $accept, EntityId $id, $revision = 0 ) {
		$mimeTypes = $this->service->getSupportedMimeTypes();
		$mimeTypes[] = 'text/html'; // HTML is handled by the normal page URL

		$negotiator = new \Wikibase\HttpAcceptNegotiator( $mimeTypes );
		$format = $negotiator->getBestSupportedKey( $accept, null );

		if ( $format === null ) {
			$mimeTypes = implode( ', ', $this->service->getSupportedMimeTypes() );
			throw new \HttpError( 406, wfMessage( 'wikibase-entitydata-not-acceptable' )->params( $mimeTypes ) );
		}

		if ( $format === 'text/html' ) {
			$format = 'html';
		} else {
			$format = $this->service->getFormatName( $format );
			assert( $format !== null );
		}

		$url = $this->getCanonicalUrl( $id, $format, $revision );
		$this->getOutput()->redirect( $url, 303 );
		return;
	}

	/**
	 * Returns the canonical URL for the given set of entity data.
	 *
	 * @param EntityId $id       The entity
	 * @param string   $format   The (normalized) format name, or ''
	 * @param int      $revision The revision ID (use 0 for current)
	 *
	 * @return string
	 */
	public function getCanonicalUrl( EntityId $id, $format = '', $revision = 0 ) {
		if ( $format === 'html' ) {
			//\Wikibase\EntityContentFactory::singleton()
			$title = $this->entityContentFactory->getTitleForId( $id );
			$params = '';

			if ( $revision > 0 ) {
				// Ugh, internal knowledge. Doesn't title have a better way to do this?
				$params = 'oldid=' . $revision;
			}

			$url = $title->getFullURL( $params );
		} else {
			$subPage = $this->getSubPageName( $id, $format, $revision );
			$title = $this->getTitle( $subPage );
			$url = $title->getFullURL();
		}

		return $url;
	}

	/**
	 * Output entity data.
	 *
	 * @param string $format The name (mime type of file extension) of the format to use
	 * @param EntityId $id The entity ID
	 * @param int $revision The entity revision
	 *
	 * @throws HttpError
	 */
	public function showData( $format, EntityId $id, $revision ) {

		//TODO: handle IfModifiedSince!

		$prefixedId = $this->entityIdFormatter->format( $id );
		$entity = $this->entityContentFactory->getFromId( $id );

		if ( !$entity ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": entity not found: $prefixedId"  );
			throw new \HttpError( 404, wfMessage( 'wikibase-entitydata-not-found' )->params( $prefixedId ) );
		}

		$page = $entity->getWikiPage();

		if ( $revision > 0 ) {
			// get the desired revision
			$rev = Revision::newFromId( $revision );

			if ( $rev === null ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": revision not found: $revision"  );
				$entity = null;
			} elseif ( $rev->getPage() !== $page->getId() ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": revision $revision does not belong to page "
					. $page->getTitle()->getPrefixedDBkey() );
				$entity = null;
			} elseif ( !EntityContentFactory::singleton()->isEntityContentModel( $rev->getContentModel() ) ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": revision has bad model: "
					. $rev->getContentModel() );
				$entity = null;
			} else {
				$entity = $rev->getContent();
			}

			if ( !$entity ) {
				//TODO: more specific error message
				$msg = wfMessage( 'wikibase-entitydata-bad-revision' );
				throw new \HttpError( 404, $msg->params( $prefixedId, $revision ) );
			}
		} else {
			$rev = $page->getRevision();
		}

		list( $data, $contentType ) = $this->service->getSerializedData(
			$format, $entity->getEntity(), $rev
		);

		$this->outputData( $data, $contentType, $rev );
	}

	/**
	 * Shows an informative page to the user; Called when there is no entity to output.
	 */
	public function showForm() {
		//TODO: show input form with selector for format and field for ID. Add some explanation,
		//      point to meta-info like schema and license, and generally be a helpful data endpoint.
		$this->getOutput()->showErrorPage( 'wikibase-entitydata-title', 'wikibase-entitydata-text' );
	}

	/**
	 * Output the entity data and set the appropriate HTTP response headers.
	 *
	 * @param string $data the data to output
	 * @param string $contentType the data's mime type
	 * @param Revision $revision
	 */
	public function outputData( $data, $contentType, Revision $revision = null ) {
		// NOTE: similar code as in RawAction::onView, keep in sync.

		$this->getOutput()->disable(); // don't generate HTML

		$request = $this->getRequest();
		$response = $request->response();

		$maxage = $request->getInt( 'maxage', $this->maxAge );
		$smaxage = $request->getInt( 'smaxage', $this->maxAge );

		// XXX: do we want public caching even for data from old revisions?
		// Sanity: 0 to 31 days. // todo: Hard maximum could be configurable somehow.
		$maxage  = max( 0, min( 60 * 60 * 24 * 31, $maxage ) );
		$smaxage = max( 0, min( 60 * 60 * 24 * 31, $smaxage ) );

		$response->header( 'Content-Type: ' . $contentType . '; charset=UTF-8' );
		$response->header( 'Content-Length: ' . strlen( $data ) );

		if ( $revision ) {
			$response->header( 'Last-Modified: ' . wfTimestamp( TS_RFC2822, $revision->getTimestamp() ) );
		}

		//Set X-Frame-Options API results (bug 39180)
		global $wgApiFrameOptions;
		if ( $wgApiFrameOptions ) {
			$response->header( "X-Frame-Options: $wgApiFrameOptions" );
		}

		// allow the client to cache this
		$mode = 'public';
		$response->header( 'Cache-Control: ' . $mode . ', s-maxage=' . $smaxage . ', max-age=' . $maxage );

		$this->getOutput()->disable(); // don't generate HTML
		ob_clean(); // remove anything that might already be in the output buffer.

		print $data;

		// exit normally here, keeping all levels of output buffering.
	}

	/**
	 * Returns true iff RDF output is supported.
	 * @return bool
	 */
	public function isRdfSupported() {
		return EntityDataSerializationService::isRdfSupported();
	}
}
