<?php

namespace Wikibase\Client\Hooks;

use Action;
use OutputPage;
use QuickTemplate;
use Skin;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\RepoItemLinkGenerator;

/**
 * Handler for the "SkinTemplateOutputPageBeforeExec" hook.
 * Injects an edit link for language links pointing to the repo, and creates
 * a dummy "Other languages" section for JS use.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class SkinTemplateOutputPageBeforeExecHandler {

	/**
	 * @var RepoItemLinkGenerator
	 */
	private $repoItemLinkGenerator;

	public function __construct( RepoItemLinkGenerator $repoItemLinkGenerator ) {
		$this->repoItemLinkGenerator = $repoItemLinkGenerator;
	}

	private static function newFromGlobalState() {
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
	 * @param Skin &$skin
	 * @param QuickTemplate &$template
	 *
	 * @return bool
	 */
	public static function onSkinTemplateOutputPageBeforeExec( Skin &$skin, QuickTemplate &$template ) {
		$title = $skin->getTitle();

		if ( !$title || !WikibaseClient::getDefaultInstance()->getNamespaceChecker()->isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return true;
		}

		$handler = self::newFromGlobalState();
		return $handler->doSkinTemplateOutputPageBeforeExec( $skin, $template );
	}

	/**
	 * @param Skin $skin
	 * @param QuickTemplate $template
	 *
	 * @return bool
	 */
	public function doSkinTemplateOutputPageBeforeExec( Skin $skin, QuickTemplate $template ) {
		$title = $skin->getTitle();

		$languageUrls = $template->get( 'language_urls' );
		$action = Action::getActionName( $skin->getContext() );
		$noExternalLangLinks = $skin->getOutput()->getProperty( 'noexternallanglinks' );

		$this->setEditLink( $skin->getOutput(), $template, $title, $action, $languageUrls );

		// Needed to have "Other languages" section display, so we can add "add links".
		// Only force the section to display if we are going to actually add such a link:
		// Where external langlinks aren't suppressed and where action == 'view'.
		if ( $languageUrls === false && $title->exists()
			&& ( $noExternalLangLinks === null || !in_array( '*', $noExternalLangLinks ) )
			&& $action === 'view'
		) {
			$template->set( 'language_urls', [] );
		}

		return true;
	}

	/**
	 * @param OutputPage $out
	 * @param QuickTemplate $template
	 * @param Title $title
	 * @param string $action
	 * @param array|bool $languageUrls
	 */
	private function setEditLink(
		OutputPage $out,
		QuickTemplate $template,
		Title $title,
		$action,
		$languageUrls
	) {
		$hasLangLinks = $languageUrls !== false && !empty( $languageUrls );
		$prefixedId = $out->getProperty( 'wikibase_item' );
		$noExternalLangLinks = $out->getProperty( 'noexternallanglinks' );

		$editLink = $this->repoItemLinkGenerator->getLink( $title, $action, $hasLangLinks, $noExternalLangLinks, $prefixedId );

		// There will be no link in some situations, like add links widget disabled
		if ( $editLink ) {
			$template->set( 'wbeditlanglinks', $editLink );
		}
	}

}
