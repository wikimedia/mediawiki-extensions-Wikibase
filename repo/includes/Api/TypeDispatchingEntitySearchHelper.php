<?php
namespace Wikibase\Repo\Api;

use WebRequest;
use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * EntitySearchHelper implementation which uses entity configuration to
 * instantiate the specific handler.
 */
class TypeDispatchingEntitySearchHelper implements EntitySearchHelper {

	/**
	 * @var callable[]
	 */
	private $callbacks;
	/**
	 * @var WebRequest
	 */
	private $request;

	public function __construct( array $callbacks, WebRequest $request ) {
		$this->callbacks = $callbacks;
		$this->request = $request;
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
		if ( empty( $this->callbacks[$entityType] ) ) {
			return [];
		}
		$factory = $this->callbacks[$entityType];
		$helper = $factory( $this->request );
		if ( !( $helper instanceof EntitySearchHelper ) ) {
			throw new \RuntimeException( "Bad helper returned by the factory for $entityType" );
		}
		return $helper->getRankedSearchResults( $text, $languageCode, $entityType, $limit,
			$strictLanguage );
	}

}
