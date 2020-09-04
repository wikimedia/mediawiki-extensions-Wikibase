<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityUrlLookup;

/**
 * @license GPL-2.0-or-later
 */
class ApiEntityUrlLookup implements EntityUrlLookup {

	/**
	 * @var ApiEntityTitleTextLookup
	 */
	private $titleTextLookup;

	/**
	 * @var string
	 */
	private $sourceWikibaseUrl;

	/**
	 * ApiEntityUrlLookup constructor.
	 * @param ApiEntityTitleTextLookup $titleTextLookup
	 * @param string $sourceWikibaseUrl
	 */
	public function __construct( ApiEntityTitleTextLookup $titleTextLookup, string $sourceWikibaseUrl ) {
		$this->titleTextLookup = $titleTextLookup;
		$this->sourceWikibaseUrl = $sourceWikibaseUrl;
	}

	/**
	 * @param EntityId $id
	 * @return string|null
	 */
	public function getFullUrl( EntityId $id ): ?string {
		$titleText = $this->titleTextLookup->getPrefixedText( $id );

		if ( $titleText === null ) {
			return null;
		}

		return $this->sourceWikibaseUrl . 'index.php?' . http_build_query( [
				'title' => $titleText,
			] );
	}

	/**
	 * @param EntityId $id
	 * @return string|null
	 */
	public function getLinkUrl( EntityId $id ): ?string {
		// Assume that when using an API based lookup we are always referring to Entities somewhere else.
		// So always return the full URL.
		// This is always true for Federated Properties, but might change if this is ever used elsewhere.
		return $this->getFullUrl( $id );
	}
}
