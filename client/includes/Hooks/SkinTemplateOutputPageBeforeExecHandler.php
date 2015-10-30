<?php

namespace Wikibase\Client\Hooks;

use Action;
use OutputPage;
use QuickTemplate;
use Skin;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\RepoItemLinkGenerator;
use Wikibase\NamespaceChecker;

/**
 * Handler for the "SkinTemplateOutputPageBeforeExec" hook.
 * Injects an edit link for language links pointing to the repo, and creates
 * a dummy "Other languages" section for JS use.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class SkinTemplateOutputPageBeforeExecHandler {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var RepoItemLinkGenerator
	 */
	private $repoItemLinkGenerator;

	/**
	 *
	 * @param NamespaceChecker $namespaceChecker
	 * @param RepoItemLinkGenerator $repoItemLinkGenerator
	 */
	public function __construct( NamespaceChecker $namespaceChecker, RepoItemLinkGenerator $repoItemLinkGenerator ) {
		$this->namespaceChecker = $namespaceChecker;
		$this->repoItemLinkGenerator = $repoItemLinkGenerator;
	}

	private static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$repoItemLinkGenerator = new RepoItemLinkGenerator(
			WikibaseClient::getDefaultInstance()->getNamespaceChecker(),
			$wikibaseClient->newRepoLinker(),
			$wikibaseClient->getEntityIdParser(),
			$wikibaseClient->getLangLinkSiteGroup(),
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' )
		);

		return new self(
			$wikibaseClient->getNamespaceChecker(),
			$repoItemLinkGenerator
		);
	}

	/**
	 * @since 0.5
	 *
	 * @param Skin &$skin
	 * @param QuickTemplate &$template
	 *
	 * @return bool
	 */
	public static function onSkinTemplateOutputPageBeforeExec( Skin &$skin, QuickTemplate &$template ) {
		$handler = self::newFromGlobalState();
		return $handler->doSkinTemplateOutputPageBeforeExec( $skin, $template );
	}

	/**
	 * @since 0.5
	 *
	 * @param Skin &$skin
	 * @param QuickTemplate &$template
	 *
	 * @return bool
	 */
	public function doSkinTemplateOutputPageBeforeExec( Skin &$skin, QuickTemplate &$template ) {
		$title = $skin->getTitle();

		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return true;
		}

		$languageUrls = $template->get( 'language_urls' );
		$action = Action::getActionName( $skin->getContext() );
		$noExternalLangLinks = $skin->getOutput()->getProperty( 'noexternallanglinks' );

		$this->setEditLink( $skin->getOutput(), $template, $title, $action, $noExternalLangLinks, $languageUrls );

		// Needed to have "Other languages" section display, so we can add "add links".
		// Only force the section to display if we are going to actually add such a link:
		// Where external langlinks aren't suppressed and where action == 'view'.
		if ( $languageUrls === false && $title->exists()
			&& ( $noExternalLangLinks === null || !in_array( '*', $noExternalLangLinks ) )
			&& $action === 'view'
		) {
			$template->set( 'language_urls', array() );
		}

		return true;
	}

	/**
	 * @param OutputPage $out
	 * @param QuickTemplate $template
	 * @param Title $title
	 * @param string $action
	 * @param array $noExternalLangLinks
	 * @param array|bool $languageUrls
	 */
	private function setEditLink( OutputPage $out, QuickTemplate $template, Title $title, $action, array $noExternalLangLinks = null, $languageUrls ) {
		$hasLangLinks = $languageUrls !== false && !empty( $languageUrls );
		$prefixedId = $out->getProperty( 'wikibase_item' );

		$editLink = $this->repoItemLinkGenerator->getLink( $title, $action, $hasLangLinks, $noExternalLangLinks, $prefixedId );

		// There will be no link in some situations, like add links widget disabled
		if ( $editLink ) {
			$template->set( 'wbeditlanglinks', $editLink );
		}
	}

}
