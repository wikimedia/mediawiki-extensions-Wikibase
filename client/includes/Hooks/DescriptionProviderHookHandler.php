<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use Title;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\Client\WikibaseClient;

/**
 * Description Provider Hook Hander for Search Results
 */
class DescriptionProviderHookHandler {

	private $descriptionLookup;
	private $allowLocalShortDesc;

	public function __construct(
		bool $allowLocalShortDesc,
		DescriptionLookup $descriptionLookup
	) {
		$this->allowLocalShortDesc = $allowLocalShortDesc;
		$this->descriptionLookup = $descriptionLookup;
	}

	public function doSearchResultProvideDescription(
		array $pageIdentities,
		array &$results
	): void {
		if ( !$this->allowLocalShortDesc ) {
			$sources = [ DescriptionLookup::SOURCE_CENTRAL ];
		} else {
			$sources = [ DescriptionLookup::SOURCE_CENTRAL, DescriptionLookup::SOURCE_LOCAL ];
		}

		$pageIdTitles = array_map( function ( $identity ) {
			return Title::makeTitle( $identity->getNamespace(), $identity->getDBkey() );
		}, $pageIdentities );

		$descriptions = $this->descriptionLookup->getDescriptions(
			$pageIdTitles,
			$sources
		);

		foreach ( $descriptions as $pageId => $description ) {
			$results[$pageId] = $description;
		}
	}

	public static function newFromGlobalState(): DescriptionProviderHookHandler {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$allowLocalShortDesc = $wikibaseClient->getSettings()->getSetting( 'allowLocalShortDesc' );
		$descriptionLookup = $wikibaseClient->getDescriptionLookup();
		return new DescriptionProviderHookHandler( $allowLocalShortDesc, $descriptionLookup );
	}

	/**
	 * Used to update Search Results with descriptions for Search Engine.
	 */
	public static function onSearchResultProvideDescription(
		array $pageIdentities,
		array &$results
	): void {
		$handler = self::newFromGlobalState();
		$handler->doSearchResultProvideDescription( $pageIdentities, $results );
	}

}
