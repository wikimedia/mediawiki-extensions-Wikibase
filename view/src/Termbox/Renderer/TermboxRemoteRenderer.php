<?php

namespace Wikibase\View\Termbox\Renderer;

use Exception;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use MediaWiki\Http\HttpRequestFactory;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * @license GPL-2.0-or-later
 */
class TermboxRemoteRenderer implements TermboxRenderer {

	/** @var HttpRequestFactory */
	private $requestFactory;
	/** @var string|null */
	private $ssrServerUrl;
	/** @var LoggerInterface */
	private $logger;
	/** @var StatsdDataFactoryInterface */
	private $stats;

	/** @var int|float */
	private $ssrServerTimeout;
	public const HTTP_STATUS_OK = 200;

	public function __construct(
		HttpRequestFactory $requestFactory,
		?string $ssrServerUrl,
		$ssrServerTimeout,
		LoggerInterface $logger,
		StatsdDataFactoryInterface $stats

	) {
		$this->requestFactory = $requestFactory;
		$this->ssrServerUrl = $ssrServerUrl;
		$this->ssrServerTimeout = $ssrServerTimeout;
		$this->logger = $logger;
		$this->stats = $stats;
	}

	/**
	 * @inheritDoc
	 */
	public function getContent( EntityId $entityId, $revision, $language, $editLink, TermLanguageFallbackChain $preferredLanguages ) {
		try {
			$request = $this->requestFactory->create(
				$this->formatUrl( $entityId, $revision, $language, $editLink, $preferredLanguages ),
				[ 'timeout' => $this->ssrServerTimeout ],
				__METHOD__
			);
			$request->execute();
		} catch ( TermboxRenderingException $e ) {
			// rethrow without repeated reporting
			throw $e;
		} catch ( Exception $e ) {
			$this->reportFailureOfRequest( $e->getMessage(), $e );
			throw new TermboxRenderingException( 'Encountered request problem', null, $e );
		}

		$status = $request->getStatus();

		if ( $status !== self::HTTP_STATUS_OK ) {

			if ( $status === 0 ) {
				$this->reportFailureOfRequest( 'Request failed with status 0. Usually this means network failure or timeout' );
			} else {
				$this->logger->notice(
					'{class}: encountered a bad response from the remote renderer',
					[
						'class' => __CLASS__,
						'status' => $status,
						'content' => $request->getContent(),
						'headers' => $request->getResponseHeaders(),
					]
				);
				$this->stats->increment( 'wikibase.view.TermboxRemoteRenderer.unsuccessfulResponse' );
			}

			throw new TermboxRenderingException( 'Encountered bad response: ' . $status );
		}

		return $request->getContent();
	}

	private function reportFailureOfRequest( $message, Exception $exception = null ) {
		$context = [
			'errormessage' => $message,
			'class' => __CLASS__,
		];
		if ( $exception !== null ) {
			$context[ 'exception' ] = $exception;
		}
		$this->logger->error( '{class}: Problem requesting from the remote server', $context );
		$this->stats->increment( 'wikibase.view.TermboxRemoteRenderer.requestError' );
	}

	private function formatUrl( EntityId $entityId, $revision, $language, $editLink, TermLanguageFallbackChain $preferredLanguages ) {
		if ( !$this->ssrServerUrl ) {
			throw new TermboxNoRemoteRendererException( 'Termbox SSR server URL not configured' );
		}
		return $this->ssrServerUrl . '?' .
			http_build_query( $this->getRequestParams( $entityId, $revision, $language, $editLink, $preferredLanguages ) );
	}

	private function getRequestParams(
		EntityId $entityId,
		$revision,
		$language,
		$editLink,
		TermLanguageFallbackChain $preferredLanguages
	) {
		return [
			'entity' => $entityId->getSerialization(),
			'revision' => $revision,
			'language' => $language,
			'editLink' => $editLink,
			'preferredLanguages' => implode( '|', $this->getLanguageCodes( $preferredLanguages ) ),
		];
	}

	private function getLanguageCodes( TermLanguageFallbackChain $preferredLanguages ) {
		return array_map( function ( LanguageWithConversion $language ) {
			return $language->getLanguageCode();
		}, $preferredLanguages->getFallbackChain() );
	}

}
