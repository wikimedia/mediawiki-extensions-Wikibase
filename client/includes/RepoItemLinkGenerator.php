<?php

namespace Wikibase\Client;

use Title;
use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\NamespaceChecker;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
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
	 * @var bool
	 */
	private $hasLangLinks;

	/**
	 * @since 0.4
	 *
	 * @param NamespaceChecker $namespaceChecker
	 * @param RepoLinker       $repoLinker
	 * @param EntityIdParser   $entityIdParser
	 * @param string           $langLinkSiteGroup
	 * @param bool             $hasLangLinks
	 */
	public function __construct(
		NamespaceChecker $namespaceChecker,
		RepoLinker $repoLinker,
		EntityIdParser $entityIdParser,
		$langLinkSiteGroup,
		$hasLangLinks
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->repoLinker = $repoLinker;
		$this->entityIdParser = $entityIdParser;
		$this->langLinkSiteGroup = $langLinkSiteGroup;
		$this->hasLangLinks = $hasLangLinks;
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
		if ( is_string( $prefixedId ) && $this->hasLangLinks ) {
			$entityId = $this->entityIdParser->parse( $prefixedId );

			// link to the associated item on the repo
			return $this->getEditLinksLink( $entityId );
		}

		if ( $this->canHaveLink( $title, $action, $noExternalLangLinks ) && !$isAnon ) {
			return $this->getAddLinksLink();
		}

		return null;
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
			&& !$this->isSuppressed( $noExternalLangLinks )
		) {
			return true;
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
	 * @param EntityId $entityId
	 *
	 * @return string[]
	 */
	private function getEditLinksLink( EntityId $entityId ) {
		$fragment = '#sitelinks-' . htmlspecialchars( $this->langLinkSiteGroup, ENT_QUOTES );

		$link = array(
			'href' => $this->repoLinker->getEntityUrl( $entityId ) . $fragment,
			'title' => wfMessage( 'wikibase-editlinkstitle' )->text(),
			'class' => 'wbc-editpage',
		);

		$text = wfMessage( 'wikibase-editlinks' )->text();
		return $this->formatLink( $link, 'edit', $text );
	}

	/**
	 * Used by the LinkItem js widget
	 *
	 * @return string[]
	 */
	private function getAddLinksLink() {
		$link = array(
			'id' => 'wbc-linkToItem-link',
			'href' => '#',
			'class' => 'wbc-editpage wbc-nolanglinks',
		);

		$text = wfMessage( 'wikibase-linkitem-addlinks' )->text();
		return $this->formatLink( $link, 'add', $text );
	}

	/**
	 * @param array $link
	 * @param string $text
	 * @param string $action
	 * @return string
	 */
	private function formatLink( array $link, $action, $text ) {
		$link = Html::element( 'a', $link, $text );

		$html = Html::rawElement(
			'span',
			array(
				'class' => "wb-langlinks-$action wb-langlinks-link"
			),
			$link
		);

		return $html;
	}

}
