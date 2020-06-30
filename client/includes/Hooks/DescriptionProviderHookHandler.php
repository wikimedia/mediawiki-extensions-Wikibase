<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Rest\Entity\SearchResultPageIdentity;
use MediaWiki\Rest\Hook\SearchResultProvideDescriptionHook;
use Title;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\Client\WikibaseClient;

/**
 * Description Provider Hook Handler for Search Results
 */
class DescriptionProviderHookHandler implements SearchResultProvideDescriptionHook {

	private $descriptionLookup;
	private $allowLocalShortDesc;

	public function __construct(
		bool $allowLocalShortDesc,
		DescriptionLookup $descriptionLookup
	) {
		$this->allowLocalShortDesc = $allowLocalShortDesc;
		$this->descriptionLookup = $descriptionLookup;
	}

	public function onSearchResultProvideDescription(
		array $pageIdentities,
		&$descriptions
	): void {
		if ( !$this->allowLocalShortDesc ) {
			$sources = [ DescriptionLookup::SOURCE_CENTRAL ];
		} else {
			$sources = [ DescriptionLookup::SOURCE_CENTRAL, DescriptionLookup::SOURCE_LOCAL ];
		}

		$pageIdTitles = array_map( function ( SearchResultPageIdentity $identity ) {
			return Title::makeTitle( $identity->getNamespace(), $identity->getDBkey() );
		}, $pageIdentities );

		$newDescriptions = $this->descriptionLookup->getDescriptions(
			$pageIdTitles,
			$sources
		);

		foreach ( $newDescriptions as $pageId => $description ) {
			$descriptions[$pageId] = $description;
		}
	}

	public static function newFromGlobalState(): DescriptionProviderHookHandler {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$allowLocalShortDesc = $wikibaseClient->getSettings()->getSetting( 'allowLocalShortDesc' );
		$descriptionLookup = $wikibaseClient->getDescriptionLookup();
		return new DescriptionProviderHookHandler( $allowLocalShortDesc, $descriptionLookup );
	}

}
