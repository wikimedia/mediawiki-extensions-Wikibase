<?php

namespace Wikibase\Repo\LinkedData;

use HttpError;
use OutputPage;
use CdnCacheUpdate;
use WebRequest;
use WebResponse;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\RedirectRevision;
use Wikimedia\Http\HttpAcceptNegotiator;
use Wikimedia\Http\HttpAcceptParser;

/**
 * Request handler implementing a linked data interface for Wikibase entities.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thomas Pellissier Tanon
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 */
class EntityDataRequestHandler {

	/**
	 * Allowed smallest and bigest number of seconds for the "max-age=..." and "s-maxage=..." cache
	 * control parameters.
	 *
	 * @todo Hard maximum could be configurable somehow.
	 */
	const MINIMUM_MAX_AGE = 0;
	const MAXIMUM_MAX_AGE = 2678400; // 31 days

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
	 * @var EntityRedirectLookup
	 */
	private $entityRedirectLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityDataFormatProvider
	 */
	private $entityDataFormatProvider;

	/**
	 * @var string
	 */
	private $defaultFormat;

	/**
	 * @var int Number of seconds to cache entity data.
	 */
	private $maxAge;

	/**
	 * @var bool
	 */
	private $useSquids;

	/**
	 * @var string|null
	 */
	private $frameOptionsHeader;

	/**
	 * @var string[]
	 */
	private $disabledRdfExportEntityTypes;

	/**
	 * @param EntityDataUriManager $uriManager
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdParser $entityIdParser
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityRedirectLookup $entityRedirectLookup
	 * @param EntityDataSerializationService $serializationService
	 * @param EntityDataFormatProvider $entityDataFormatProvider
	 * @param string $defaultFormat The format as a file extension or MIME type.
	 * @param int $maxAge number of seconds to cache entity data
	 * @param bool $useSquids do we have web caches configured?
	 * @param string|null $frameOptionsHeader for X-Frame-Options
	 * @param string[] $disabledRdfExportEntityTypes List of entity types
	 */
	public function __construct(
		EntityDataUriManager $uriManager,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $entityIdParser,
		EntityRevisionLookup $entityRevisionLookup,
		EntityRedirectLookup $entityRedirectLookup,
		EntityDataSerializationService $serializationService,
		EntityDataFormatProvider $entityDataFormatProvider,
		array $disabledRdfExportEntityTypes,
		$defaultFormat,
		$maxAge,
		$useSquids,
		$frameOptionsHeader
	) {
		$this->uriManager = $uriManager;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityIdParser = $entityIdParser;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityRedirectLookup = $entityRedirectLookup;
		$this->serializationService = $serializationService;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->defaultFormat = $defaultFormat;
		$this->maxAge = $maxAge;
		$this->useSquids = $useSquids;
		$this->frameOptionsHeader = $frameOptionsHeader;
		$this->disabledRdfExportEntityTypes = $disabledRdfExportEntityTypes;
	}

	/**
	 * Checks whether the request is complete, i.e. whether it contains all information needed
	 * to reply with entity data.
	 *
	 * This does not check whether the request is valid and will actually produce a successful
	 * response.
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
		// No matter what: The response is always public
		$output->getRequest()->response()->header( 'Access-Control-Allow-Origin: *' );

		$revision = 0;

		list( $id, $format ) = $this->uriManager->parseDocName( $doc );

		// get entity id and format from request parameter
		$format = $request->getText( 'format', $format );
		$id = $request->getText( 'id', $id );
		$revision = $request->getInt( 'oldid', $revision );
		$revision = $request->getInt( 'revision', $revision );
		$redirectMode = $request->getText( 'redirect' );
		//TODO: malformed revision IDs should trigger a code 400

		// If there is no ID, fail
		if ( $id === null || $id === '' ) {
			//TODO: different error message?
			throw new HttpError( 400, $output->msg( 'wikibase-entitydata-bad-id', $id ) );
		}

		try {
			$entityId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $ex ) {
			throw new HttpError( 400, $output->msg( 'wikibase-entitydata-bad-id', $id ) );
		}

		if ( $format === 'rdf' || $format === 'ttl' ) {
			if ( in_array( $entityId->getEntityType(), $this->disabledRdfExportEntityTypes ) ) {
				throw new HttpError(
					400,
					$output->msg( 'wikibase-entitydata-rdf-disabled', $entityId->getEntityType() )
				);
			}
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
		}

		//NOTE: will trigger a 415 if the format is not supported
		$format = $this->getCanonicalFormat( $format );

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

		// if redirection was force, redirect
		if ( $redirectMode === 'force' ) {
			$url = $this->uriManager->getDocUrl( $entityId, $format, $revision );
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
	 * @throws HttpError code 415 if the format is not supported.
	 */
	public function getCanonicalFormat( $format ) {
		$format = strtolower( $format );

		// we always support html, it's handled by the entity's wiki page.
		if ( $format === 'html' || $format === 'htm' || $format === 'text/html' ) {
			return 'html';
		}

		// normalize format name (note that HTML may not be known to the service)
		$canonicalFormat = $this->entityDataFormatProvider->getFormatName( $format );

		if ( $canonicalFormat === null ) {
			$msg = wfMessage( 'wikibase-entitydata-unsupported-format', $format );
			throw new HttpError( 415, $msg );
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
		$update = new CdnCacheUpdate( $urls );
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
			$accept = [
				'*' => 0.1 // just to make extra sure
			];

			$defaultFormat = $this->entityDataFormatProvider->getFormatName( $this->defaultFormat );
			$defaultMime = $this->entityDataFormatProvider->getMimeType( $defaultFormat );

			// prefer the default
			if ( $defaultMime != null ) {
				$accept[$defaultMime] = 1;
			}
		}

		$mimeTypes = $this->entityDataFormatProvider->getSupportedMimeTypes();
		$mimeTypes[] = 'text/html'; // HTML is handled by the normal page URL

		$negotiator = new HttpAcceptNegotiator( $mimeTypes );
		$format = $negotiator->getBestSupportedKey( $accept, null );

		if ( $format === null ) {
			$mimeTypes = implode( ', ', $this->entityDataFormatProvider->getSupportedMimeTypes() );
			$msg = $output->msg( 'wikibase-entitydata-not-acceptable', $mimeTypes );
			throw new HttpError( 406, $msg );
		}

		$format = $this->getCanonicalFormat( $format );

		$url = $this->uriManager->getDocUrl( $id, $format, $revision );
		$output->redirect( $url, 303 );
	}

	/**
	 * Loads the requested Entity. Redirects are resolved if no specific revision
	 * is requested.
	 *
	 * @param EntityId $id
	 * @param int $revision The revision ID (use 0 for the current revision).
	 *
	 * @return array list( EntityRevision, RedirectRevision|null )
	 * @throws HttpError
	 */
	private function getEntityRevision( EntityId $id, $revision ) {
		$prefixedId = $id->getSerialization();
		$redirectRevision = null;

		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $id, $revision );

			if ( $entityRevision === null ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": entity not found: $prefixedId" );
				$msg = wfMessage( 'wikibase-entitydata-not-found', $prefixedId );
				throw new HttpError( 404, $msg );
			}
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			$redirectRevision = new RedirectRevision(
				new EntityRedirect( $id, $ex->getRedirectTargetId() ),
				$ex->getRevisionId(), $ex->getRevisionTimestamp()
			);

			if ( $revision === 0 ) {
				// If no specific revision is requested, resolve the redirect.
				list( $entityRevision, ) = $this->getEntityRevision( $ex->getRedirectTargetId(), $revision );
			} else {
				// The requested revision is a redirect
				wfDebugLog( __CLASS__, __FUNCTION__ . ": revision $revision of $prefixedId is a redirect: $ex" );
				$msg = wfMessage( 'wikibase-entitydata-bad-revision', $prefixedId, $revision );
				throw new HttpError( 400, $msg );
			}
		} catch ( BadRevisionException $ex ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": could not load revision $revision or $prefixedId: $ex" );
			$msg = wfMessage( 'wikibase-entitydata-bad-revision', $prefixedId, $revision );
			throw new HttpError( 404, $msg );
		} catch ( StorageException $ex ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": failed to load $prefixedId: $ex (revision $revision)" );
			$msg = wfMessage( 'wikibase-entitydata-storage-error', $prefixedId, $revision );
			throw new HttpError( 500, $msg );
		}

		return [ $entityRevision, $redirectRevision ];
	}

	/**
	 * Loads incoming redirects referring to the given entity ID.
	 *
	 * @param EntityId $id
	 *
	 * @return EntityId[]
	 * @throws HttpError
	 */
	private function getIncomingRedirects( EntityId $id ) {
		try {
			return $this->entityRedirectLookup->getRedirectIds( $id );
		} catch ( EntityRedirectLookupException $ex ) {
			$prefixedId = $id->getSerialization();
			wfDebugLog( __CLASS__, __FUNCTION__ . ": failed to load incoming redirects of $prefixedId: $ex" );
			return [];
		}
	}

	/**
	 * Output entity data.
	 *
	 * @param WebRequest $request
	 * @param OutputPage $output
	 * @param string $format The name (mime type of file extension) of the format to use
	 * @param EntityId $id The entity ID
	 * @param int $revision The revision ID (use 0 for the current revision).
	 *
	 * @throws HttpError
	 */
	public function showData( WebRequest $request, OutputPage $output, $format, EntityId $id, $revision ) {

		$flavor = $request->getVal( "flavor" );

		/** @var EntityRevision $entityRevision */
		/** @var RedirectRevision $followedRedirectRevision */
		list( $entityRevision, $followedRedirectRevision ) = $this->getEntityRevision( $id, $revision );

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

		if ( $flavor === 'dump' || $revision > 0 ) {
			// In dump mode and when fetching a specific revision, don't include incoming redirects.
			$incomingRedirects = [];
		} else {
			// Get the incoming redirects of the entity (if we followed a redirect, use the target id).
			$incomingRedirects = $this->getIncomingRedirects( $entityRevision->getEntity()->getId() );
		}

		list( $data, $contentType ) = $this->serializationService->getSerializedData(
			$format,
			$entityRevision,
			$followedRedirectRevision,
			$incomingRedirects,
			$flavor
		);

		$output->disable();
		$this->outputData(
			$request,
			$output->getRequest()->response(),
			$data,
			$contentType,
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
		$maxAge = $request->getInt( 'maxage', $this->maxAge );
		$sMaxAge = $request->getInt( 'smaxage', $this->maxAge );

		// XXX: do we want public caching even for data from old revisions?
		$maxAge  = max( self::MINIMUM_MAX_AGE, min( self::MAXIMUM_MAX_AGE, $maxAge ) );
		$sMaxAge = max( self::MINIMUM_MAX_AGE, min( self::MAXIMUM_MAX_AGE, $sMaxAge ) );

		$response->header( 'Content-Type: ' . $contentType . '; charset=UTF-8' );

		if ( $lastModified ) {
			$response->header( 'Last-Modified: ' . wfTimestamp( TS_RFC2822, $lastModified ) );
		}

		//Set X-Frame-Options API results (bug T41180)
		if ( $this->frameOptionsHeader !== null && $this->frameOptionsHeader !== '' ) {
			$response->header( "X-Frame-Options: $this->frameOptionsHeader" );
		}

		// allow the client to cache this
		$mode = 'public';
		$response->header( 'Cache-Control: ' . $mode . ', s-maxage=' . $sMaxAge . ', max-age=' . $maxAge );

		ob_clean(); // remove anything that might already be in the output buffer.

		print $data;

		// exit normally here, keeping all levels of output buffering.
	}

}
