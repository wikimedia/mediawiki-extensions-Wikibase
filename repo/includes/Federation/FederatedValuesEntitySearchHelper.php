<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\SettingsArray;
use Wikibase\DataModel\Term\Term;

/**
 * Decorator that adds federated (remote) item results on top of local
 * EntitySearchHelper results.
 */
class FederatedValuesEntitySearchHelper implements EntitySearchHelper {

	private EntitySearchHelper $inner;
	private RemoteSearchClient $remoteSearchClient;
	private SettingsArray $settings;

	public function __construct(
		EntitySearchHelper $inner,
		RemoteSearchClient $remoteSearchClient,
		SettingsArray $settings
	) {
		$this->inner = $inner;
		$this->remoteSearchClient = $remoteSearchClient;
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
		// Always get local results first
		$localResults = $this->inner->getRankedSearchResults(
			$text,
			$languageCode,
			$entityType,
			$limit,
			$strictLanguage,
			$profileContext
		);

		// Only apply to items for now, and only if enabled
		if (
			$entityType !== 'item' ||
			!$this->settings->getSetting( 'federatedValuesEnabled' )
		) {
			return $localResults;
		}

		// Build remote params similar to your old wbsearchentities call
		$remoteParams = [
			'search'         => $text,
			'language'       => $languageCode,
			'type'           => 'item',
			'limit'          => $limit,
			'strictlanguage' => $strictLanguage,
			'profile'        => $profileContext,
			// keep the “ignore remote continuation” behavior
			'continue'       => 0,
		];

		$remoteResp = $this->remoteSearchClient->searchEntities( $remoteParams );
		$remoteEntries = $remoteResp['search'] ?? [];

		$remoteResults = $this->mapRemoteEntriesToTermSearchResults(
			$remoteEntries,
			$languageCode
		);

		// For now: just append remote results at the end, like your old SearchEntities
		// code did (where it array_merge'd $entries with $remoteEntries).
		return array_merge( $localResults, $remoteResults );
	}

/**
 * Turn remote wbsearchentities "search" entries into TermSearchResult objects.
 *
 * @param array[] $remoteEntries
 * @param string $languageCode
 * @return TermSearchResult[]
 */
private function mapRemoteEntriesToTermSearchResults( array $remoteEntries, string $languageCode ): array {
		$results = [];

		foreach ( $remoteEntries as $entry ) {
				$id = $entry['id'] ?? null;
				$labelText = $entry['label'] ?? $id ?? '';
				$descriptionText = $entry['description'] ?? null;

				// Matched term: use the label if available, fall back to ID
				$matchedTerm = new Term(
						$languageCode,
						$labelText !== '' ? $labelText : ( $id ?? '' )
				);

				$displayLabel = $labelText !== ''
						? new Term( $languageCode, $labelText )
						: null;

				$displayDescription = $descriptionText !== null && $descriptionText !== ''
						? new Term( $languageCode, $descriptionText )
						: null;

				// Start metadata with the remote entry
				$meta = $entry;

				// Ensure required metadata for the "entityId === null" path:
				// id, title, pageid, url must exist.
				if ( $id !== null ) {
						$meta['id'] = $id;
				}

				if ( !isset( $meta['title'] ) && $id !== null ) {
						$meta['title'] = $id;
				}

				if ( !isset( $meta['pageid'] ) ) {
						$meta['pageid'] = 0;
				}

				if ( !isset( $meta['url'] ) && isset( $meta['concepturi'] ) ) {
						$meta['url'] = $meta['concepturi'];
				}

				if ( !isset( $meta['repository'] ) ) {
						$meta['repository'] = 'wikidata';
				}

				// TODO: Use TermSearchResultFactory instead?
				$results[] = new TermSearchResult(
						$matchedTerm,        // Term
						'label',             // matchedTermType
						null,                // entityId (remote)
						$displayLabel,       // displayLabel
						$displayDescription, // displayDescription
						$meta                // metaData
				);
		}

		return $results;
	}
}
