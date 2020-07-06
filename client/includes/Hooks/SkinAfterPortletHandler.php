<?php

namespace Wikibase\Client\Hooks;

use Action;
use MediaWiki\Skins\Hook\SkinAfterPortletHook;
use Skin;
use Wikibase\Client\RepoItemLinkGenerator;
use Wikibase\Client\WikibaseClient;

/**
 * Handler for the "SkinAfterPortlet" hook.
 * Injects an edit link for language links pointing to the repo, and creates
 * a dummy "Other languages" section for JS use.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SkinAfterPortletHandler implements SkinAfterPortletHook {
	/**
	 * @var RepoItemLinkGenerator
	 */
	private $repoItemLinkGenerator;

	public function __construct( RepoItemLinkGenerator $repoItemLinkGenerator ) {
		$this->repoItemLinkGenerator = $repoItemLinkGenerator;
	}

	public static function newFromGlobalState(): self {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		return new self(
			new RepoItemLinkGenerator(
				$wikibaseClient->getNamespaceChecker(),
				$wikibaseClient->newRepoLinker(),
				$wikibaseClient->getEntityIdParser(),
				$wikibaseClient->getLangLinkSiteGroup(),
				$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' )
			)
		);
	}

	/**
	 * @param Skin $skin
	 * @param string $portlet
	 * @param string $html
	 */
	public function onSkinAfterPortlet( $skin, $portlet, &$html ): void {
		if ( $portlet === 'lang' ) {
			$actionLink = $this->doSkinAfterPortlet( $skin );
			if ( $actionLink ) {
				$html .= $actionLink;
			}
		}
	}

	/**
	 * Sets the the appropriate languages action link (edit|add) or none for this title and context
	 *
	 * @see RepoItemLinkGenerator::getLink()
	 *
	 * @param Skin $skin
	 * @return null|string
	 * @suppress PhanTypeComparisonFromArray
	 */
	public function doSkinAfterPortlet( Skin $skin ): ?string {
		$out = $skin->getOutput();
		$title = $skin->getTitle();

		$languageUrls = $skin->getLanguages();
		$prefixedId = $out->getProperty( 'wikibase_item' );
		$action = Action::getActionName( $skin->getContext() );
		$noExternalLangLinks = $out->getProperty( 'noexternallanglinks' );
		$hasLangLinks = $languageUrls !== false && !empty( $languageUrls );

		$itemLink = $this->repoItemLinkGenerator->getLink( $title, $action,
			$hasLangLinks, $noExternalLangLinks, $prefixedId
		);

		return $itemLink;
	}

}
