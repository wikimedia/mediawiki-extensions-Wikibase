<?php

namespace Wikibase\Repo\LinkedData;

use HttpError;
use OutputPage;
use SquidUpdate;
use WebRequest;
use WebResponse;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\EntityTitleLookup;
use Wikibase\StorageException;

/**
 * Request handler implementing a linked data interface for Wikibase entities.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thomas Pellissier Tanon
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 */
class EntityDataRequestHandler {

	/**
	 * @var int Cache duration in seconds
	 */
	private $maxAge = 0;

	/**
	 * @var EntityDataSerializationService
	 */
	private $serializationService;

	/**
	 * @var EntityDataUriManager
	 */
	private $uriManager;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var string
	 */
	private $defaultFormat;

	/**
	 * @var bool
	 */
	private $useSquids;

	/**
	 * @var string|null
	 */
	private $frameOptionsHeader;

	/**
	 * @since 0.4
	 *
	 * @param EntityDataUriManager           $uriManager
	 * @param EntityTitleLookup              $entityTitleLookup
	 * @param EntityIdParser                 $entityIdParser
	 * @param EntityRevisionLookup           $entityRevisionLookup
	 * @param EntityDataSerializationService $serializationService
	 * @param string                         $defaultFormat
	 * @param int                            $maxAge number of seconds to cache entity data
	 * @param bool                           $useSquids do we have web caches configured?
	 * @param string|null                    $frameOptionsHeader for X-Frame-Options
	 */
	public function __construct(
		EntityDataUriManager $uriManager,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $entityIdParser,
		EntityRevisionLookup $entityRevisionLookup,
		EntityDataSerializationService $serializationService,
		$defaultFormat,
		$maxAge,
		$useSquids,
		$frameOptionsHeader
	) {
		$this->uriManager = $uriManager;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityIdParser = $entityIdParser;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->serializationService = $serializationService;
		$this->defaultFormat = $defaultFormat;
		$this->maxAge = $maxAge;
		$this->useSquids = $useSquids;
		$this->frameOptionsHeader = $frameOptionsHeader;
	}

	/**
	 * @param int $maxAge
	 */
	public function setMaxAge( $maxAge ) {
		$this->maxAge = $maxAge;
	}

	/**
	 * @return int
	 */
	public function getMaxAge() {
		return $this->maxAge;
	}

	/**
	 * Checks whether the request is complete, i.e. whether it contains all information needed
	 * to reply with entity data.
	 *
	 * This does not check whether the request is valid and will actually produce a successful
	 * response.
	 *
	 * @since 0.4
	 *
	 * @param string|null $doc Document name, e.g. Q5 or Q5.json or Q5:33.xml
	 * @param WebRequest $request
	 *
	 * @return bool
	 * @throws HttpError
	 */
	public function canHandleRequest( $doc, WebRequest $request ) {
		if ( $doc === '' || $doc === null ) {
			if ( $request->getText( 'id', '' ) === '' ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Main method for handling requests.
	 *
	 * @since 0.4
	 *
	 * @param string $doc Document name, e.g. Q5 or Q5.json or Q5:33.xml
	 * @param WebRequest $request The request parameters. Known parameters are:
	 *        - id: the entity ID
	 *        - format: the format
	 *        - oldid|revision: the revision ID
	 *        - action=purge: to purge cached data from (web) caches
	 * @param OutputPage $output
	 *
	 * @note: Instead of an output page, a WebResponse could be sufficient, but
	 *        redirect logic is currently implemented in OutputPage.
	 *
	 * @throws HttpError
	 */
	public function handleRequest( $doc, WebRequest $request, OutputPage $output ) {
		$revision = 0;

		list( $id, $format ) = $this->uriManager->parseDocName( $doc );

		// get entity id and format from request parameter
		$format = $request->getText( 'format', $format );
		$id = $request->getText( 'id', $id );
		$revision = $request->getInt( 'oldid', $revision );
		$revision = $request->getInt( 'revision', $revision );
		//TODO: malformed revision IDs should trigger a code 400

		// If there is no ID, fail
		if ( $id === null || $id === '' ) {
			//TODO: different error message?
			throw new \HttpError( 400, wfMessage( 'wikibase-entitydata-bad-id' )->params( $id ) );
		}

		try {
			$entityId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $ex ) {
			throw new \HttpError( 400, wfMessage( 'wikibase-entitydata-bad-id' )->params( $id ) );
		}

		//XXX: allow for logged in users only?
		if ( $request->getText( 'action' ) === 'purge' ) {
			$this->purgeWebCache( $entityId );
			//XXX: Now what? Proceed to show the data?
		}

		if ( $format === null || $format === '' ) {
			// if no format is given, apply content negotiation and return.

			$this->httpContentNegotiation( $request, $output, $entityId, $revision );
			return;
		} else {
			//NOTE: will trigger a 415 if the format is not supported
			$format = $this->getCanonicalFormat( $format );
		}

		// we should know the format now.
		assert( $format !== null && $format !== '' );

		if ( $doc !== null && $doc !== '' ) {
			// if subpage syntax is used, always enforce the canonical form
			$canonicalDoc = $this->uriManager->getDocName( $entityId, $format );

			if ( $doc !== $canonicalDoc ) {
				$url = $this->uriManager->getDocUrl( $entityId, $format, $revision );
				$output->redirect( $url, 301 );
				return;
			}
		}

		// if the format is HTML, redirect to the entity's wiki page
		if ( $format === 'html' ) {
			$url = $this->uriManager->getDocUrl( $entityId, 'html', $revision );
			$output->redirect( $url, 303 );
			return;
		}

		$this->showData( $request, $output, $format, $entityId, $revision );
	}

	/**
	 * Returns the canonical format name for the given format.
	 *
	 * @param string $format
	 *
	 * @return string
	 * @throws \HttpError code 415 if the format is not supported.
	 *
	 */
	public function getCanonicalFormat( $format ) {
		$format = strtolower( $format );

		// we always support html, it's handled by the entity's wiki page.
		if ( $format === 'html' || $format === 'htm' || $format === 'text/html' ) {
			return 'html';
		}

		// normalize format name (note that HTML may not be known to the service)
		$canonicalFormat = $this->serializationService->getFormatName( $format );

		if ( $canonicalFormat === null ) {
			throw new \HttpError( 415, wfMessage( 'wikibase-entitydata-unsupported-format' )->params( $format ) );
		}

		return $canonicalFormat;
	}

	/**
	 * Purges the entity data identified by the doc parameter from any HTTP caches.
	 * Does nothing if $wgUseSquid is not set.
	 *
	 * @param EntityId $id       The entity
	 */
	public function purgeWebCache( EntityId $id ) {
		$urls = $this->uriManager->getCacheableUrls( $id );

		//TODO: use a factory or service, so we can mock & test this
		$update = new SquidUpdate( $urls );
		$update->doUpdate();
	}

	/**
	 * Applies HTTP content negotiation.
	 * If the negotiation is successfull, this method will set the appropriate redirect
	 * in the OutputPage object and return. Otherwise, an HttpError is thrown.
	 *
	 * @param WebRequest $request
	 * @param OutputPage $output
	 * @param EntityId $id The ID of the entity to show
	 * @param int      $revision The desired revision
	 *
	 * @throws HttpError
	 */
	public function httpContentNegotiation( WebRequest $request, OutputPage $output, EntityId $id, $revision = 0 ) {
		$headers = $request->getAllHeaders();
		if ( isset( $headers['ACCEPT'] ) ) {
			$parser = new HttpAcceptParser();
			$accept = $parser->parseWeights( $headers['ACCEPT'] );
		} else {
			// anything goes
			$accept = array(
				'*' => 0.1 // just to make extra sure
			);

			$defaultFormat = $this->serializationService->getFormatName( $this->defaultFormat );
			$defaultMime = $this->serializationService->getMimeType( $defaultFormat );

			// prefer the default
			if ( $defaultMime != null ) {
				$accept[$defaultMime] = 1;
			}
		}

		$mimeTypes = $this->serializationService->getSupportedMimeTypes();
		$mimeTypes[] = 'text/html'; // HTML is handled by the normal page URL

		$negotiator = new HttpAcceptNegotiator( $mimeTypes );
		$format = $negotiator->getBestSupportedKey( $accept, null );

		if ( $format === null ) {
			$mimeTypes = implode( ', ', $this->serializationService->getSupportedMimeTypes() );
			throw new \HttpError( 406, wfMessage( 'wikibase-entitydata-not-acceptable' )->params( $mimeTypes ) );
		}

		$format = $this->getCanonicalFormat( $format );

		$url = $this->uriManager->getDocUrl( $id, $format, $revision );
		$output->redirect( $url, 303 );
	}

	/**
	 * Output entity data.
	 *
	 * @param WebRequest $request
	 * @param OutputPage $output
	 * @param string $format The name (mime type of file extension) of the format to use
	 * @param EntityId $id The entity ID
	 * @param int $revision The entity revision
	 *
	 * @throws HttpError
	 */
	public function showData( WebRequest $request, OutputPage $output, $format, EntityId $id, $revision ) {

		$prefixedId = $id->getSerialization();

		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $id, $revision );

			if ( $entityRevision === null ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": entity not found: $prefixedId"  );
				throw new \HttpError( 404, wfMessage( 'wikibase-entitydata-not-found' )->params( $prefixedId ) );
			}
		} catch ( StorageException $ex ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": could not load: $prefixedId: $ex revision $revision"  );
			$msg = wfMessage( 'wikibase-entitydata-bad-revision' );
			throw new \HttpError( 404, $msg->params( $prefixedId, $revision ) );
		}

		// handle If-Modified-Since
		$imsHeader = $request->getHeader( 'IF-MODIFIED-SINCE' );
		if ( $imsHeader !== false ) {
			$ims = wfTimestamp( TS_MW, $imsHeader );

			if ( $entityRevision->getTimestamp() <= $ims ) {
				$response = $output->getRequest()->response();
				$response->header( 'Status: 304', true, 304 );
				return;
			}
		}

		list( $data, $contentType ) = $this->serializationService->getSerializedData(
			$format, $entityRevision
		);

		$output->disable();
		$this->outputData(
			$request,
			$output->getRequest()->response(),
			$data, $contentType,
			$entityRevision->getTimestamp()
		);
	}

	/**
	 * Output the entity data and set the appropriate HTTP response headers.
	 *
	 * @param WebRequest  $request
	 * @param WebResponse $response
	 * @param string      $data        the data to output
	 * @param string      $contentType the data's mime type
	 * @param string      $lastModified
	 */
	public function outputData( WebRequest $request, WebResponse $response, $data, $contentType, $lastModified ) {
		// NOTE: similar code as in RawAction::onView, keep in sync.

		//FIXME: do not cache if revision was requested explicitly!
		$maxage = $request->getInt( 'maxage', $this->maxAge );
		$smaxage = $request->getInt( 'smaxage', $this->maxAge );

		// XXX: do we want public caching even for data from old revisions?
		// Sanity: 0 to 31 days. // todo: Hard maximum could be configurable somehow.
		$maxage  = max( 0, min( 60 * 60 * 24 * 31, $maxage ) );
		$smaxage = max( 0, min( 60 * 60 * 24 * 31, $smaxage ) );

		$response->header( 'Content-Type: ' . $contentType . '; charset=UTF-8' );

		if ( $lastModified ) {
			$response->header( 'Last-Modified: ' . wfTimestamp( TS_RFC2822, $lastModified ) );
		}

		//Set X-Frame-Options API results (bug 39180)
		if ( $this->frameOptionsHeader !== null && $this->frameOptionsHeader !== '' ) {
			$response->header( "X-Frame-Options: $this->frameOptionsHeader" );
		}

		// allow the client to cache this
		$mode = 'public';
		$response->header( 'Cache-Control: ' . $mode . ', s-maxage=' . $smaxage . ', max-age=' . $maxage );

		ob_clean(); // remove anything that might already be in the output buffer.

		print $data;

		// exit normally here, keeping all levels of output buffering.
	}
}
