<?php

namespace Wikibase\View\Termbox\Renderer;

use MediaWiki\Http\HttpRequestFactory;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TermboxRemoteRenderer implements TermboxRenderer {

	private $requestFactory;
	private $ssrServerUrl;

	public function __construct( HttpRequestFactory $requestFactory, $ssrServerUrl ) {
		$this->requestFactory = $requestFactory;
		$this->ssrServerUrl = $ssrServerUrl;
	}

	/**
	 * @inheritDoc
	 */
	public function getContent( EntityId $entityId, $language ) {
		$request = $this->requestFactory->create(
			$this->formatUrl( $entityId, $language ),
			[ /* TODO attach required data */ ]
		);
		$request->execute();

		return $request->getContent();
	}

	private function formatUrl( EntityId $entityId, $language ) {
		return $this->ssrServerUrl . '?' .
			http_build_query( $this->getRequestParams( $entityId, $language ) );
	}

	private function getRequestParams( EntityId $entityId, $language ) {
		return [
			'entity' => $entityId->getSerialization(),
			'language' => $language
		];
	}

}
