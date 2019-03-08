<?php

namespace Wikibase\Repo\Api;

use FormatJson;
use MWHttpRequest;
use Wikibase\Lib\Interactors\TermSearchResult;

class RemoteApiSearchHelper implements EntitySearchHelper {

	private $repoApiUrl;

	/**
	 * @param string $repoApiUrl
	 */
	public function __construct( $repoApiUrl ) {
		$this->repoApiUrl = $repoApiUrl;
	}

	/**
	 * Get entities matching the search term.
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 *
	 * @return TermSearchResult[] Key: string Serialized EntityId
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage
	) {
		$params = [
			'action' => 'wbsearchentities',
			'type' => $entityType,
			'language' => $languageCode,
			'text' => $text,
			'strictlanguage' => (int)$strictLanguage,
			'limit' => $limit
		];
		$data = $this->request( $params );
		//TODO munge data back into TermSearchResult
	}


	private function request ( array $params ) {
		$url = wfAppendQuery( $this->repoApiUrl, $params );
		$req = MWHttpRequest::factory(
			$url,
			[],
			__METHOD__
		);

		$status = $req->execute();
		if ( !$status->isOK() ) {
			return [];
		}

		$json = $req->getContent();
		$data = FormatJson::decode( $json, true );
		if ( !$data || !empty( $data['error'] ) ) {
			return [];
		}

		return $data;
	}
}
