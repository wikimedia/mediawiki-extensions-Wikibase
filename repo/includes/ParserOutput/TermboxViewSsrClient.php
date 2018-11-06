<?php

namespace Wikibase\Repo\ParserOutput;

use MediaWiki\Http\HttpRequestFactory;
use Wikibase\DataModel\Entity\EntityId;

/**
 * TODO I'm in the wrong folder
 *
 * @license GPL-2.0-or-later
 */
class TermboxViewSsrClient {

	private $requestFactory;
	private $ssrServerUrl;

	public function __construct( HttpRequestFactory $requestFactory, $ssrServerUrl ) {
		$this->requestFactory = $requestFactory;
		$this->ssrServerUrl = $ssrServerUrl;
	}

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
