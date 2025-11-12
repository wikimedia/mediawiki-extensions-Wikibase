<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity;

use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\SettingsArray;
use Wikibase\DataModel\Term\Term;

/**
 * Decorator that adds remote (federated) entity results on top of local results.
 * Type-agnostic: augments whatever $entityType the caller requests.
 */
class RemoteEntitySearchHelper implements EntitySearchHelper {

	private EntitySearchHelper $localSearchHelper;
	private RemoteEntitySearchClient $remoteEntitySearchClient;
	private SettingsArray $settings;

	public function __construct(
		EntitySearchHelper $localSearchHelper,
		RemoteEntitySearchClient $remoteEntitySearchClient,
		SettingsArray $settings
	) {
		$this->localSearchHelper = $localSearchHelper;
		$this->remoteEntitySearchClient = $remoteEntitySearchClient;
		$this->settings = $settings;
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 * @param string|null $profileContext
	 * @return TermSearchResult[]
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage,
		?string $profileContext
	) {
		// 1) Local results
		$localResults = $this->localSearchHelper->getRankedSearchResults(
			$text,
			$languageCode,
			$entityType,
			$limit,
			$strictLanguage,
			$profileContext
		);

		// 2) If federation off → return local only
		if ( !$this->isRemoteSearchEnabled() ) {
			return $localResults;
		}

		// 3) Remote results (same type as requested)
		$remoteParams = [
			'search'         => $text,
			'language'       => $languageCode,
			'type'           => $entityType,
			'limit'          => $limit,
			'strictlanguage' => $strictLanguage,
			'profile'        => $profileContext,
			'continue'       => 0,
		];

		$remoteResp = $this->remoteEntitySearchClient->searchEntities( $remoteParams );
		$remoteEntries = $remoteResp['search'] ?? [];

		$remoteResults = $this->mapRemoteEntriesToTermSearchResults(
			$remoteEntries,
			$languageCode
		);

		// 4) Append remote below local
		return array_merge( $localResults, $remoteResults );
	}

	/**
	 * Map wbsearchentities entries → TermSearchResult, ensuring:
	 *  - local entities keep plain IDs (Q… / P… / etc.)
	 *  - remote entities expose conceptUri as id
	 */
	private function mapRemoteEntriesToTermSearchResults( array $remoteEntries, string $languageCode ): array {
		$results = [];
		$localBase = WikibaseRepo::getLocalEntitySource()->getConceptBaseUri();

		foreach ( $remoteEntries as $entry ) {
			$id = $entry['id'] ?? null; // local id on the remote (e.g. Q…/P…)
			$conceptUri = $entry['concepturi'] ?? null;
			$labelText = $entry['label'] ?? $id ?? '';
			$descriptionText = $entry['description'] ?? null;

			$matchedTerm = new Term( $languageCode, $labelText !== '' ? $labelText : ( $id ?? '' ) );
			$displayLabel = $labelText !== '' ? new Term( $languageCode, $labelText ) : null;
			$displayDescription = $descriptionText !== null && $descriptionText !== '' ? new Term( $languageCode, $descriptionText ) : null;

			$meta = $entry;

			// Determine local vs remote by concept URI base
			$isRemote = is_string( $conceptUri ) && strpos( $conceptUri, $localBase ) !== 0;

			if ( $conceptUri ) {
				$meta['concepturi'] = $conceptUri;
				$meta['url'] = $meta['url'] ?? $conceptUri;
			}

			// Use concept URI as id only for remote results; keep plain id for local.
			$meta['id'] = ( $isRemote && $conceptUri ) ? $conceptUri : ( $id ?? '' );

			if ( !isset( $meta['title'] ) ) {
				$meta['title'] = $id ?? '';
			}
			if ( !isset( $meta['pageid'] ) ) {
				$meta['pageid'] = 0;
			}

			$results[] = new TermSearchResult(
				$matchedTerm,
				'label',
				null,                // remote → no local EntityId
				$displayLabel,
				$displayDescription,
				$meta
			);
		}

		return $results;
	}

	private function isRemoteSearchEnabled(): bool {
		if ( !$this->settings->hasSetting( 'federatedValuesEnabled' ) ) {
			return false;
		}
		return (bool)$this->settings->getSetting( 'federatedValuesEnabled' );
	}
}
