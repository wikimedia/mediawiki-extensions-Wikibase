<?php

namespace Wikibase\Repo\LinkedData;

use HtmlCacheUpdater;
use HttpError;
use OutputPage;
use Psr\Log\LoggerInterface;
use WebRequest;
use WebResponse;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RedirectRevision;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Rdf\UnknownFlavorException;
use Wikimedia\Http\HttpAcceptNegotiator;
use Wikimedia\Http\HttpAcceptParser;

/**
 * Request handler implementing a linked data interface for Wikibase entities.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thomas Pellissier Tanon
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 */
class EntityDataRequestHandler {

	/**
	 * Allowed smallest and biggest number of seconds for the "max-age=..." and "s-maxage=..." cache
	 * control parameters.
	 *
	 * @todo Hard maximum could be configurable somehow.
	 */
	private const MINIMUM_MAX_AGE = 0;
	private const MAXIMUM_MAX_AGE = 2678400; // 31 days

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
	 * @var EntityDataFormatProvider
	 */
	private $entityDataFormatProvider;

	/**
	 * @var HtmlCacheUpdater
	 */
	private $htmlCacheUpdater;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

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
	private $useCdn;

	/**
	 * @var string|null
	 */
	private $frameOptionsHeader;

	/**
	 * @var string[]
	 */
	private $entityTypesWithoutRdfOutput;

	/**
	 * @var SubEntityTypesMapper
	 */
	private $subEntityTypesMap;

	/**
	 * @param EntityDataUriManager $uriManager
	 * @param HtmlCacheUpdater $htmlCacheUpdater
	 * @param EntityIdParser $entityIdParser
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityRedirectLookup $entityRedirectLookup
	 * @param EntityDataSerializationService $serializationService
	 * @param EntityDataFormatProvider $entityDataFormatProvider
	 * @param LoggerInterface $logger
	 * @param string[] $entityTypesWithoutRdfOutput
	 * @param string $defaultFormat The format as a file extension or MIME type.
	 * @param int $maxAge number of seconds to cache entity data
	 * @param bool $useCdn do we have web caches configured?
	 * @param string|null $frameOptionsHeader for X-Frame-Options
	 * @param SubEntityTypesMapper $subEntityTypesMap
	 */
	public function __construct(
		EntityDataUriManager $uriManager,
		HtmlCacheUpdater $htmlCacheUpdater,
		EntityIdParser $entityIdParser,
		EntityRevisionLookup $entityRevisionLookup,
		EntityRedirectLookup $entityRedirectLookup,
		EntityDataSerializationService $serializationService,
		EntityDataFormatProvider $entityDataFormatProvider,
		LoggerInterface $logger,
		array $entityTypesWithoutRdfOutput,
		$defaultFormat,
		$maxAge,
		$useCdn,
		$frameOptionsHeader,
		SubEntityTypesMapper $subEntityTypesMap
	) {
		$this->uriManager = $uriManager;
		$this->htmlCacheUpdater = $htmlCacheUpdater;
		$this->entityIdParser = $entityIdParser;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityRedirectLookup = $entityRedirectLookup;
		$this->serializationService = $serializationService;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->logger = $logger;
		$this->entityTypesWithoutRdfOutput = $entityTypesWithoutRdfOutput;
		$this->defaultFormat = $defaultFormat;
		$this->maxAge = $maxAge;
		$this->useCdn = $useCdn;
		$this->frameOptionsHeader = $frameOptionsHeader;
		$this->subEntityTypesMap = $subEntityTypesMap;
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
	 * @note Instead of an output page, a WebResponse could be sufficient, but
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

		if ( $this->entityDataFormatProvider->isRdfFormat( $format ) &&
			in_array( $entityId->getEntityType(), $this->entityTypesWithoutRdfOutput )
		) {
			throw new HttpError( 406, $output->msg( 'wikibase-entitydata-rdf-not-available', $entityId->getEntityType() ) );
		}

		//XXX: allow for logged in users only?
		if ( $request->getText( 'action' ) === 'purge' ) {
			$this->purgeWebCache( $entityId, $revision );
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
				if ( $url === null ) {
					throw new HttpError( 400, $output->msg( 'wikibase-entitydata-bad-id', $id ) );
				}
				$output->redirect( $url, 301 );
				return;
			}
		}

		// if the format is HTML, redirect to the entity's wiki page
		if ( $format === 'html' ) {
			$url = $this->uriManager->getDocUrl( $entityId, 'html', $revision );
			if ( $url === null ) {
				throw new HttpError( 400, $output->msg( 'wikibase-entitydata-bad-id', $id ) );
			}
			$output->redirect( $url, 303 );
			return;
		}

		// if redirection was force, redirect
		if ( $redirectMode === 'force' ) {
			$url = $this->uriManager->getDocUrl( $entityId, $format, $revision );
			if ( $url === null ) {
				throw new HttpError( 400, $output->msg( 'wikibase-entitydata-bad-id', $id ) );
			}
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
	 * Does nothing if $wgUseCdn is not set.
	 *
	 * @param EntityId $id The entity ID for which to purge all data.
	 * @param int $revision The revision ID (0 for current/unspecified)
	 */
	public function purgeWebCache( EntityId $id, int $revision ) {
		$urls = $this->uriManager->getPotentiallyCachedUrls( $id, $revision );
		if ( $urls !== [] ) {
			$this->htmlCacheUpdater->purgeUrls( $urls );
		}
	}

	/**
	 * Applies HTTP content negotiation.
	 * If the negotiation is successful, this method will set the appropriate redirect
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
				'*' => 0.1, // just to make extra sure
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
		if ( $url === null ) {
			throw new HttpError( 400, $output->msg( 'wikibase-entitydata-bad-id', $id->getSerialization() ) );
		}
		$output->redirect( $url, 303 );
	}

	/**
	 * Loads the requested Entity. Redirects are resolved if no specific revision
	 * is requested or they are explicitly allowed by $allowRedirects.
	 *
	 * @param EntityId $id
	 * @param int $revision The revision ID (use 0 for the current revision).
	 * @param bool $allowRedirects Can we fetch redirects when revision is set?
	 *
	 * @return array list( EntityRevision, RedirectRevision|null )
	 * @throws HttpError
	 */
	private function getEntityRevision( EntityId $id, $revision, $allowRedirects = false ) {
		$prefixedId = $id->getSerialization();
		$redirectRevision = null;

		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $id, $revision );

			if ( $entityRevision === null ) {
				$this->logger->debug(
					'{method}: entity not found: {prefixedId}',
					[
						'method' => __METHOD__,
						'prefixedId' => $prefixedId,
					]
				);

				$msg = wfMessage( 'wikibase-entitydata-not-found', $prefixedId );
				throw new HttpError( 404, $msg );
			}
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			$this->validateRedirectability( $id, $ex->getRedirectTargetId() );

			$redirectRevision = new RedirectRevision(
				new EntityRedirect( $id, $ex->getRedirectTargetId() ),
				$ex->getRevisionId(), $ex->getRevisionTimestamp()
			);

			if ( $revision === 0 || $allowRedirects ) {
				// If no specific revision is requested or redirects are explicitly allowed, resolve the redirect.
				list( $entityRevision, ) = $this->getEntityRevision( $ex->getRedirectTargetId(), 0 );
			} else {
				// The requested revision is a redirect
				$this->logger->debug(
					'{method}: revision {revision} of {prefixedId} is a redirect: {exMsg}',
					[
						'method' => __METHOD__,
						'revision' => $revision,
						'prefixedId' => $prefixedId,
						'exMsg' => strval( $ex ),
					]
				);

				$msg = wfMessage( 'wikibase-entitydata-bad-revision', $prefixedId, $revision );
				throw new HttpError( 400, $msg );
			}
		} catch ( BadRevisionException $ex ) {
			$this->logger->debug(
				'{method}: could not load revision {revision} or {prefixedId}: {exMsg}',
				[
					'method' => __METHOD__,
					'revision' => $revision,
					'prefixedId' => $prefixedId,
					'exMsg' => strval( $ex ),
				]
			);

			$msg = wfMessage( 'wikibase-entitydata-bad-revision', $prefixedId, $revision );
			throw new HttpError( 404, $msg );
		} catch ( StorageException $ex ) {
			$this->logger->debug(
				'{method}: failed to load {prefixedId}: {exMsg} (revision {revision})',
				[
					'method' => __METHOD__,
					'prefixedId' => $prefixedId,
					'exMsg' => strval( $ex ),
					'revision' => $revision,
				]
			);

			$msg = wfMessage( 'wikibase-entitydata-storage-error', $prefixedId, $revision );
			throw new HttpError( 500, $msg );
		}

		return [ $entityRevision, $redirectRevision ];
	}

	private function validateRedirectability( EntityId $id, EntityId $redirectTargetId ): void {
		if ( $this->subEntityTypesMap->getParentEntityType( $id->getEntityType() ) === $redirectTargetId->getEntityType() ) {
			throw new HttpError(
				404,
				wfMessage(
					'wikibase-entitydata-unresolvable-sub-entity-redirect',
					$id->getSerialization(),
					$redirectTargetId->getSerialization()
				)
			);
		}
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
			$this->logger->debug(
				'{method}: failed to load incoming redirects of {prefixedId}: {exMsg}',
				[
					'method' => __METHOD__,
					'prefixedId' => $prefixedId,
					'exMsg' => strval( $ex ),
				]
			);

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
		$flavor = $request->getRawVal( 'flavor' );

		/** @var EntityRevision $entityRevision */
		/** @var RedirectRevision $followedRedirectRevision */
		// If flavor is "dump", we allow fetching redirects by revision, since we won't
		// be dumping the content of the target revision.
		list( $entityRevision, $followedRedirectRevision ) = $this->getEntityRevision( $id, $revision, $flavor === 'dump' );

		// handle If-Modified-Since
		$imsHeader = $request->getHeader( 'IF-MODIFIED-SINCE' );
		if ( $imsHeader !== false ) {
			$ims = wfTimestamp( TS_MW, $imsHeader );

			if ( $entityRevision->getTimestamp() <= $ims ) {
				$response = $output->getRequest()->response();
				$response->header( 'Status: 304', true, 304 );
				$output->setArticleBodyOnly( true );
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

		try {
			list( $data, $contentType ) = $this->serializationService->getSerializedData(
				$format,
				$entityRevision,
				$followedRedirectRevision,
				$incomingRedirects,
				$flavor
			);
		} catch ( UnknownFlavorException $e ) {
			$knownFlavors = $e->getKnownFlavors();
			throw new HttpError(
				400,
				$output->msg( 'wikibase-entitydata-bad-flavor' )
					->plaintextParams( $e->getUnknownFlavor() )
					->numParams( count( $knownFlavors ) )
					->params( implode( '|', $knownFlavors ) )
			);
		}

		$output->disable();
		$this->outputData(
			$request,
			$id,
			$revision,
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
	 * @param EntityId    $requestId       the original entity ID of the request
	 * @param int         $requestRevision the original revision ID of the request (0 for latest)
	 * @param WebResponse $response
	 * @param string      $data        the data to output
	 * @param string      $contentType the data's mime type
	 * @param string      $lastModified
	 */
	public function outputData(
		WebRequest $request,
		EntityId $requestId,
		int $requestRevision,
		WebResponse $response,
		string $data,
		string $contentType,
		string $lastModified
	) {
		// NOTE: similar code as in RawAction::onView, keep in sync.

		$maxAge = $request->getInt( 'maxage', $this->maxAge );
		$sMaxAge = $request->getInt( 'smaxage', $this->maxAge );

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

		$cacheableUrls = $this->uriManager->getCacheableUrls( $requestId, $requestRevision );
		if ( in_array( $request->getFullRequestURL(), $cacheableUrls ) ) {
			$response->header( 'Cache-Control: public, s-maxage=' . $sMaxAge . ', max-age=' . $maxAge );
		} else {
			$response->header( 'Cache-Control: private, no-cache, s-maxage=0' );
		}

		print $data;

		// exit normally here, keeping all levels of output buffering.
	}

}
