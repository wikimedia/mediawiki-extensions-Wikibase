<?php


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
	 * @var GenericActionApiClient
	 */
	private $api;

	/**
	 * @param GenericActionApiClient $api
	 */
	public function __construct( GenericActionApiClient $api ) {
		$this->api = $api;
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
	 * @throws ApiRequestException
	 * @throws InvalidArgumentException
	 */
	public function getRankedSearchResults( $text, $languageCode, $entityType, $limit, $strictLanguage ) {
		$allResults = [];

		if ( $entityType !== Property::ENTITY_TYPE ) {

			throw new InvalidArgumentException( 'Wrong argument passed in. Entity type must be a property' );
		}

		// https://www.wikidata.org/w/api.php?action=help&modules=wbsearchentities
		$params = [
			'action' => 'wbsearchentities',
			'search' => $text,
			'language' => $languageCode,
			'type' => $entityType,
			'limit' => $limit,
			'strictlanguage' => $strictLanguage,
			'uselang' => $languageCode,
			'format' => 'json',
		];
		$response = $this->api->get( $params );
		$jsonResult = json_decode( $response->getBody()->getContents(), true );

		if ( $response->getStatusCode() !== 200 ) {

			throw new ApiRequestException( 'Unexpected response output' );
		}

		// @phan-suppress-next-line PhanTypeArraySuspiciousNullable The API response will be JSON here
		foreach ( $jsonResult['search'] as $result ) {

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

	private function getMatchedTerm( array $match ) {

		if ( $match['type'] === 'entityId' ) {
			return new Term( 'qid', $match['text'] );
		}
		return new Term( $match['language'], $match['text'] );
	}
}
