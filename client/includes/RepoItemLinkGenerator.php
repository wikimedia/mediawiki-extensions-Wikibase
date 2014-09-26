<?php

namespace Wikibase\Client;

use Title;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\NamespaceChecker;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RepoItemLinkGenerator {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var string
	 */
	private $langLinkSiteGroup;

	/**
	 * @since 0.4
	 *
	 * @param NamespaceChecker $namespaceChecker
	 * @param RepoLinker       $repoLinker
	 * @param EntityIdParser   $entityIdParser
	 * @param string           $langLinkSiteGroup
	 */
	public function __construct(
		NamespaceChecker $namespaceChecker,
		RepoLinker $repoLinker,
		EntityIdParser $entityIdParser,
		$langLinkSiteGroup
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->repoLinker = $repoLinker;
		$this->entityIdParser = $entityIdParser;
		$this->langLinkSiteGroup = $langLinkSiteGroup;
	}

	/**
	 * @since 0.4
	 *
	 * @param Title $title
	 * @param string $action
	 * @param bool $isAnon
	 * @param array|null $noExternalLangLinks
	 * @param string|null $prefixedId
	 *
	 * @return string[]|null
	 */
	public function getLink( Title $title, $action, $isAnon, $noExternalLangLinks, $prefixedId ) {
		$editLink = null;

		if ( $this->canHaveLink( $title, $action, $noExternalLangLinks ) ) {
			if ( is_string( $prefixedId ) ) {
				$entityId = $this->entityIdParser->parse( $prefixedId );

				// link to the associated item on the repo
				$editLink = $this->getEditLinksLink( $entityId );
			} else {
				if ( !$isAnon ) {
					$editLink = $this->getAddLinksLink();
				}
			}
		}

		return $editLink;
	}

	/**
	 * @param Title $title
	 * @param string $action
	 * @param mixed $noExternalLangLinks
	 *
	 * @return bool
	 */
	private function canHaveLink( Title $title, $action, $noExternalLangLinks ) {
		if ( $action !== 'view' ) {
			return false;
		}

		if ( $this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() )
			&& $title->exists()
		) {
			if ( ! $this->isSuppressed( $noExternalLangLinks ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param mixed
	 *
	 * @return bool
	 */
	private function isSuppressed( $noExternalLangLinks ) {
		if ( $noExternalLangLinks === null || !in_array( '*', $noExternalLangLinks ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param EntityId
	 *
	 * @return string[]
	 */
	private function getEditLinksLink( EntityId $entityId ) {
		$fragment = '#sitelinks-' . htmlspecialchars( $this->langLinkSiteGroup, ENT_QUOTES );

		$link = array(
			'action' => 'edit',
			'href' => $this->repoLinker->getEntityUrl( $entityId ) . $fragment,
			'text' => wfMessage( 'wikibase-editlinks' )->text(),
			'title' => wfMessage( 'wikibase-editlinkstitle' )->text(),
			'class' => 'wbc-editpage',
		);

		return $link;
	}

	/**
	 * Used by the LinkItem js widget
	 *
	 * @return string[]
	 */
	private function getAddLinksLink() {
		$link = array(
			'action' => 'add',
			'text' => '',
			'id' => 'wbc-linkToItem',
			'class' => 'wbc-editpage wbc-nolanglinks',
		);

		return $link;
	}
}
