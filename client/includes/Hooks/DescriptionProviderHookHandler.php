<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Page\PageIdentity;
use MediaWiki\Rest\Hook\SearchResultProvideDescriptionHook;
use Title;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\Lib\SettingsArray;

/**
 * Description Provider Hook Handler for Search Results
 * @license GPL-2.0-or-later
 */
class DescriptionProviderHookHandler implements SearchResultProvideDescriptionHook {

	/** @var bool */
	private $allowLocalShortDesc;
	/** @var bool */
	private $forceLocalShortDesc;
	/** @var DescriptionLookup */
	private $descriptionLookup;

	public function __construct(
		bool $allowLocalShortDesc,
		bool $forceLocalShortDesc,
		DescriptionLookup $descriptionLookup
	) {
		$this->allowLocalShortDesc = $allowLocalShortDesc;
		$this->forceLocalShortDesc = $forceLocalShortDesc;
		$this->descriptionLookup = $descriptionLookup;
	}

	public function onSearchResultProvideDescription(
		array $pageIdentities,
		&$descriptions
	): void {
		if ( !$this->allowLocalShortDesc ) {
			$sources = [ DescriptionLookup::SOURCE_CENTRAL ];
		} elseif ( $this->forceLocalShortDesc ) {
			$sources = [ DescriptionLookup::SOURCE_LOCAL ];
		} else {
			$sources = [ DescriptionLookup::SOURCE_CENTRAL, DescriptionLookup::SOURCE_LOCAL ];
		}

		$pageIdTitles = array_map( function ( PageIdentity $identity ) {
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

	public static function factory(
		DescriptionLookup $descriptionLookup,
		SettingsArray $clientSettings
	): self {
		$allowLocalShortDesc = $clientSettings->getSetting( 'allowLocalShortDesc' );
		$forceLocalShortDesc = $clientSettings->getSetting( 'forceLocalShortDesc' );

		return new self( $allowLocalShortDesc, $forceLocalShortDesc, $descriptionLookup );
	}

}
