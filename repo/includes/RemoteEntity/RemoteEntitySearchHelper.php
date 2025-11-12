<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity;

use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\SettingsArray;
use Wikibase\DataModel\Term\Term;

/**
 * Decorator that adds remote (federated) entity results on top of local
 * EntitySearchHelper results, for configurable entity types.
 *
 * Controlled by:
 *  - federationEnabled (bool)
 *  - federationForEntityTypes (string[])
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

		// Always get local results first
		$localResults = $this->localSearchHelper->getRankedSearchResults(
			$text,
			$languageCode,
			$entityType,
			$limit,
			$strictLanguage,
			$profileContext
		);

		if (
			!$this->isFederationEnabled() ||
			!$this->isEntityTypeFederated( $entityType )
		) {
			return $localResults;
		}

		// Build remote params similar to wbsearchentities;
		$remoteParams = [
			'search'         => $text,
			'language'       => $languageCode,
			'type'           => $entityType,
			'limit'          => $limit,
			'strictlanguage' => $strictLanguage,
			'profile'        => $profileContext,
			// keep the “ignore remote continuation” behavior
			'continue'       => 0,
		];

		$remoteResp = $this->remoteEntitySearchClient->searchEntities( $remoteParams );
		$remoteEntries = $remoteResp['search'] ?? [];

		$remoteResults = $this->mapRemoteEntriesToTermSearchResults(
			$remoteEntries,
			$languageCode
		);

		// For now just append remote results at the end
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

			$results[] = new TermSearchResult(
				$matchedTerm,        // Term
				'label',             // matchedTermType
				null,                // entityId (remote, not a local EntityId)
				$displayLabel,       // displayLabel
				$displayDescription, // displayDescription
				$meta                // metaData
			);
		}

		return $results;
	}

	private function isFederationEnabled(): bool {
		if ( !$this->settings->hasSetting( 'federationEnabled' ) ) {
			return false;
		}

		return (bool)$this->settings->getSetting( 'federationEnabled' );
	}

	private function isEntityTypeFederated( string $entityType ): bool {
		if ( !$this->settings->hasSetting( 'federationForEntityTypes' ) ) {
			return false;
		}

		$types = $this->settings->getSetting( 'federationForEntityTypes' );
		if ( !is_array( $types ) ) {
			return false;
		}

		return in_array( $entityType, $types, true );
	}
}
