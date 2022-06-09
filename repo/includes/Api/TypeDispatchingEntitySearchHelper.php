<?php

namespace Wikibase\Repo\Api;

use WebRequest;

/**
 * EntitySearchHelper implementation which uses entity configuration to
 * instantiate the specific handler.
 * @license GPL-2.0-or-later
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
	 * @inheritDoc
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage,
		?string $profileContext
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
			$strictLanguage, $profileContext );
	}

}
