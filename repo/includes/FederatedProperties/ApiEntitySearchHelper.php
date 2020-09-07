<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Api\PropertyDataTypeSearchHelper;

/**
 * Helper class to search for entities via an api from another wikibase instance
 */
class ApiEntitySearchHelper implements EntitySearchHelper {

	/**
	 * @var int
	 *
	 * When making requests there is a risk of too many items being filtered out.
	 * In order to work around this but not ending up making multiple requests,
	 * for now each search limit will be multiplied using this variable.
	 *
	 * @see https://phabricator.wikimedia.org/T252012
	 */
	public const API_SEARCH_MULTIPLIER = 2;

	/**
	 * @var GenericActionApiClient
	 */
	private $api;

	/**
	 * @var array
	 */
	private $typesEnabled = [];

	/**
	 * @param GenericActionApiClient $api
	 * @param string[] $enabledDataTypes
	 */
	public function __construct( GenericActionApiClient $api, array $enabledDataTypes ) {
		$this->api = $api;
		foreach ( $enabledDataTypes as $dataType ) {
			$this->typesEnabled[$dataType] = true;
		}
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
	 * @throws InvalidArgumentException
	 */
	public function getRankedSearchResults( $text, $languageCode, $entityType, $limit, $strictLanguage ) {
		$allResults = [];

		if ( $entityType !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( 'Wrong argument passed in. Entity type must be a property' );
		}
		$jsonResult = $this->makeRequest( $text, $languageCode, $entityType, $limit, $strictLanguage );
		$filteredResult = $this->filterRequest( $jsonResult, $limit );

		foreach ( $filteredResult as $result ) {
			$termSearchResult = new TermSearchResult(
				$this->getMatchedTerm( $result['match'] ),
				$result['match']['type'],
				new PropertyId( $result['id'] ),
				array_key_exists( 'label', $result ) ? new Term( $languageCode, $result['label'] ) : null,
				array_key_exists( 'description', $result ) ? new Term( $languageCode, $result['description'] ) : null,
				[
					TermSearchResult::CONCEPTURI_META_DATA_KEY => $result['concepturi'],
					PropertyDataTypeSearchHelper::DATATYPE_META_DATA_KEY => $result['datatype'],
				]
			);

			$allResults[ $result['id'] ] = $termSearchResult;
		}
		return $allResults;
	}

	/**
	 * @param array $jsonResult
	 * @param int $limit
	 *
	 * @return array
	 * @throws FederatedPropertiesException
	 *
	 * @see https://phabricator.wikimedia.org/T252012
	 */
	private function filterRequest( array $jsonResult, int $limit ) {
		$resultsArray = [];
		$filteredResultCount = 0;

		foreach ( $jsonResult['search'] as $result ) {
			if ( !isset( $this->typesEnabled[$result['datatype']] ) ) {
				continue;
			}
			$filteredResultCount++;
			$resultsArray[] = $result;

			if ( $filteredResultCount === $limit ) {
				return $resultsArray;
			}
		}

		$shouldFetchMore = $filteredResultCount < $limit && count( $jsonResult['search'] ) > $limit;

		if ( $shouldFetchMore ) {
			throw new FederatedPropertiesException( 'Result has too many unsupported data types.' );
		}
		return $resultsArray;
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 *
	 * @return mixed
	 * @throws ApiRequestException
	 *
	 * @see https://www.wikidata.org/w/api.php?action=help&modules=wbsearchentities
	 */
	private function makeRequest(
		string $text,
		string $languageCode,
		string $entityType,
		int $limit,
		bool $strictLanguage
	) {
		$params = [
			'action' => 'wbsearchentities',
			'search' => $text,
			'language' => $languageCode,
			'type' => $entityType,
			'limit' => self::API_SEARCH_MULTIPLIER * $limit,
			'strictlanguage' => $strictLanguage,
			'uselang' => $languageCode,
			'format' => 'json',
		];
		$response = $this->api->get( $params );
		$jsonResult = json_decode( $response->getBody()->getContents(), true );

		if ( $response->getStatusCode() !== 200 ) {
			throw new ApiRequestException( 'Unexpected response output' );
		}
		return $jsonResult;
	}

	private function getMatchedTerm( array $match ) {

		if ( $match['type'] === 'entityId' ) {
			return new Term( 'qid', $match['text'] );
		}
		return new Term( $match['language'], $match['text'] );
	}
}
