<?php

namespace Wikibase\View\Termbox\Renderer;

use Exception;
use MediaWiki\Http\HttpRequestFactory;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikimedia\Stats\StatsFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermboxRemoteRenderer implements TermboxRenderer {

	public const HTTP_STATUS_OK = 200;

	/** @var HttpRequestFactory */
	private $requestFactory;

	/** @var string|null */
	private $ssrServerUrl;

	/** @var int|float */
	private $ssrServerTimeout;

	/** @var LoggerInterface */
	private $logger;

	/** @var StatsFactory */
	private $statsFactory;

	/**
	 * @param HttpRequestFactory $requestFactory
	 * @param string|null $ssrServerUrl
	 * @param int|float $ssrServerTimeout
	 * @param LoggerInterface $logger
	 * @param StatsFactory $statsFactory
	 */
	public function __construct(
		HttpRequestFactory $requestFactory,
		?string $ssrServerUrl,
		$ssrServerTimeout,
		LoggerInterface $logger,
		StatsFactory $statsFactory
	) {
		$this->requestFactory = $requestFactory;
		$this->ssrServerUrl = $ssrServerUrl;
		$this->ssrServerTimeout = $ssrServerTimeout;
		$this->logger = $logger;
		$this->statsFactory = $statsFactory->withComponent( 'WikibaseRepo' );
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
			throw new TermboxRenderingException( 'Encountered request problem', 0, $e );
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
				$this->statsFactory->getCounter( 'termbox_remote_renderer_unsuccessful_response_total' )
					->copyToStatsdAt( 'wikibase.view.TermboxRemoteRenderer.unsuccessfulResponse' )
					->increment();
			}

			throw new TermboxRenderingException( 'Encountered bad response: ' . $status );
		}

		return $request->getContent();
	}

	private function reportFailureOfRequest( string $message, ?Exception $exception = null ) {
		$context = [
			'errormessage' => $message,
			'class' => __CLASS__,
		];
		if ( $exception !== null ) {
			$context[ 'exception' ] = $exception;
		}
		$this->logger->error( '{class}: Problem requesting from the remote server', $context );
		$this->statsFactory->getCounter( 'termbox_remote_renderer_request_error_total' )
			->copyToStatsdAt( 'wikibase.view.TermboxRemoteRenderer.requestError' )
			->increment();
	}

	private function formatUrl(
		EntityId $entityId,
		int $revision,
		string $language,
		string $editLink,
		TermLanguageFallbackChain $preferredLanguages
	): string {
		if ( !$this->ssrServerUrl ) {
			throw new TermboxNoRemoteRendererException( 'Termbox SSR server URL not configured' );
		}
		return $this->ssrServerUrl . '?' .
			http_build_query( $this->getRequestParams( $entityId, $revision, $language, $editLink, $preferredLanguages ) );
	}

	private function getRequestParams(
		EntityId $entityId,
		int $revision,
		string $language,
		string $editLink,
		TermLanguageFallbackChain $preferredLanguages
	): array {
		return [
			'entity' => $entityId->getSerialization(),
			'revision' => $revision,
			'language' => $language,
			'editLink' => $editLink,
			'preferredLanguages' => implode( '|', $this->getLanguageCodes( $preferredLanguages ) ),
		];
	}

	private function getLanguageCodes( TermLanguageFallbackChain $preferredLanguages ): array {
		return array_map( function ( LanguageWithConversion $language ) {
			return $language->getLanguageCode();
		}, $preferredLanguages->getFallbackChain() );
	}

}
