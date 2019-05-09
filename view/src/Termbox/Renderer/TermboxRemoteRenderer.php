<?php

namespace Wikibase\View\Termbox\Renderer;

use Exception;
use MediaWiki\Http\HttpRequestFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;

/**
 * @license GPL-2.0-or-later
 */
class TermboxRemoteRenderer implements TermboxRenderer {

	private $requestFactory;
	private $useSSR;
	private $ssrServerUrl;
	private $ssrServerTimeout;

	/* public */ const HTTP_STATUS_OK = 200;

	public function __construct(
		HttpRequestFactory $requestFactory,
		$useSSR,
		$ssrServerUrl,
		$ssrServerTimeout
	) {
		$this->requestFactory = $requestFactory;
		$this->useSSR = $useSSR;
		$this->ssrServerUrl = $ssrServerUrl;
		$this->ssrServerTimeout = $ssrServerTimeout;
	}

	/**
	 * @inheritDoc
	 */
	public function getContent( EntityId $entityId, $revision, $language, $editLink, LanguageFallbackChain $preferredLanguages ) {
		if ( !$this->useSSR ) {
			return '';
		}

		try {
			$request = $this->requestFactory->create(
				$this->formatUrl( $entityId, $revision, $language, $editLink, $preferredLanguages ),
				[ 'timeout' => $this->ssrServerTimeout ]
			);
			$request->execute();
		} catch ( Exception $e ) {
			throw new TermboxRenderingException( 'Encountered request problem', null, $e );
		}

		$status = $request->getStatus();
		if ( $status !== self::HTTP_STATUS_OK ) {
			throw new TermboxRenderingException( 'Encountered bad response: ' . $status );
		}

		return $request->getContent();
	}

	private function formatUrl( EntityId $entityId, $revision, $language, $editLink, LanguageFallbackChain $preferredLanguages ) {
		return $this->ssrServerUrl . '?' .
			http_build_query( $this->getRequestParams( $entityId, $revision, $language, $editLink, $preferredLanguages ) );
	}

	private function getRequestParams( EntityId $entityId, $revision, $language, $editLink, LanguageFallbackChain $preferredLanguages ) {
		return [
			'entity' => $entityId->getSerialization(),
			'revision' => $revision,
			'language' => $language,
			'editLink' => $editLink,
			'preferredLanguages' => implode( '|', $this->getLanguageCodes( $preferredLanguages ) ),
		];
	}

	private function getLanguageCodes( LanguageFallbackChain $preferredLanguages ) {
		return array_map( function ( LanguageWithConversion $language ) {
			return $language->getLanguageCode();
		}, $preferredLanguages->getFallbackChain() );
	}

}
