<?php


namespace Wikibase\Repo\FederatedProperties;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchHelper;

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
		$jsonResult = json_decode( $response->getBody()->getContents() );

		if ( $response->getStatusCode() !== 200 ) {

			throw new ApiRequestException( 'Unexpected response output' );
		}

		foreach ( $jsonResult->search as $result ) {
			$allResults[ $result->id ] = new TermSearchResult(
				new Term( $result->match->language, $result->match->text ),
				$result->match->type,
				new PropertyId( $result->id ),
				new Term( $languageCode, $result->label ),
				new Term( $languageCode, $result->description )
			);
		}
		return $allResults;
	}

}
