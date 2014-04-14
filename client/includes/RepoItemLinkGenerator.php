<?php

namespace Wikibase;

use Title;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RepoItemLinkGenerator {

	private $namespacesChecker;

	private $repoLinker;

	private $entityIdParser;

	private $siteGroup;

	/**
	 * @since 0.4
	 *
	 * @param NamespaceChecker $namespaceChecker
	 * @param RepoLinker       $repoLinker
	 * @param EntityIdParser   $entityIdParser
	 * @param string           $siteGroup
	 */
	public function __construct( NamespaceChecker $namespaceChecker, RepoLinker $repoLinker,
		EntityIdParser $entityIdParser, $siteGroup ) {

		$this->namespaceChecker = $namespaceChecker;
		$this->repoLinker = $repoLinker;
		$this->entityIdParser = $entityIdParser;
		$this->siteGroup = $siteGroup;
	}

	/**
	 * @since 0.4
	 *
	 * @param Title $title
	 * @param string $action
	 * @param boolean $isAnon
	 * @param array|null $noExternalLangLinks
	 * @param string|null $prefixedId
	 *
	 * @return array|null
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
	 * @return boolean
	 */
	private function canHaveLink( Title $title, $action, $noExternalLangLinks ) {
		if ( $action !== 'view' ) {
			return false;
		}

		if ( $this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) && $title->exists() ) {

			if ( ! $this->isSuppressed( $noExternalLangLinks ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param mixed
	 *
	 * @return boolean
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
	 * @return array
	 */
	private function getEditLinksLink( EntityId $entityId ) {
		$fragment = '#sitelinks-' . htmlspecialchars( $this->siteGroup, ENT_QUOTES );

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
	 * @return array
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
