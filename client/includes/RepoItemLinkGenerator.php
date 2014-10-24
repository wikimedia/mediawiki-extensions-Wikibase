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
	 * @var string
	 */
	private $siteGlobalId;

	/**
	 * @since 0.4
	 *
	 * @param NamespaceChecker $namespaceChecker
	 * @param RepoLinker       $repoLinker
	 * @param EntityIdParser   $entityIdParser
	 * @param string           $langLinkSiteGroup
	 * @param string           $siteGlobalId
	 */
	public function __construct(
		NamespaceChecker $namespaceChecker,
		RepoLinker $repoLinker,
		EntityIdParser $entityIdParser,
		$langLinkSiteGroup,
		$siteGlobalId
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->repoLinker = $repoLinker;
		$this->entityIdParser = $entityIdParser;
		$this->langLinkSiteGroup = $langLinkSiteGroup;
		$this->siteGlobalId = $siteGlobalId;
	}

	/**
	 * @since 0.4
	 *
	 * @param Title $title
	 * @param string $action
	 * @param bool $hasLangLinks
	 * @param array|null $noExternalLangLinks
	 * @param string|null $prefixedId
	 *
	 * @return string|null HTML or null for no link
	 */
	public function getLink( Title $title, $action, $hasLangLinks, $noExternalLangLinks, $prefixedId ) {
		$entityId = null;
		if ( is_string( $prefixedId ) ) {
			$entityId = $this->entityIdParser->parse( $prefixedId );
		}

		if ( $entityId && $hasLangLinks ) {
			return $this->getEditLinksLink( $entityId );
		}

		if ( $this->canHaveLink( $title, $action, $noExternalLangLinks ) ) {
			return $this->getAddLinksLink( $title, $entityId );
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
	 * @param null|array $noExternalLangLinks
	 *
	 * @return bool
	 */
	private function isSuppressed( $noExternalLangLinks ) {
		return $noExternalLangLinks !== null && in_array( '*', $noExternalLangLinks );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	private function getEditLinksLink( EntityId $entityId ) {
		$link = array(
			'href' => $this->getEntityUrl( $entityId ),
			'title' => wfMessage( 'wikibase-editlinkstitle' )->text(),
			'class' => 'wbc-editpage',
		);

		$text = wfMessage( 'wikibase-editlinks' )->text();
		return $this->formatLink( $link, 'edit', $text );
	}

	/**
	 * Links to the item or Special:NewItem on the repo. The link might get
	 * overwritten by the JavaScript add links widget.
	 *
	 * @param Title $title
	 * @param EntityId|null $entityId Entity which $title is linked to
	 *
	 * @return string HTML
	 */
	private function getAddLinksLink( Title $title, EntityId $entityId = null ) {
		if ( $entityId ) {
			$href = $this->getEntityUrl( $entityId );
		} else {
			$href = $this->getNewItemUrl( $title );
		}

		$link = array(
			'href' => $href,
			'title' => wfMessage( 'wikibase-addlinkstitle' )->text(),
			'class' => 'wbc-editpage',
		);

		$text = wfMessage( 'wikibase-linkitem-addlinks' )->text();
		return $this->formatLink( $link, 'add', $text );
	}

	/**
	 * @param Title $title
	 *
	 * @return string
	 */
	private function getNewItemUrl( Title $title ) {
		$params = array(
			'site' => $this->siteGlobalId,
			'page' => $title->getPrefixedText()
		);

		$url = $this->repoLinker->getPageUrl( 'Special:NewItem' );
		$url = $this->repoLinker->addQueryParams( $url, $params );

		return $url;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	private function getEntityUrl( EntityId $entityId ) {
		$fragment = '#sitelinks-' . htmlspecialchars( $this->langLinkSiteGroup, ENT_QUOTES );
		return $this->repoLinker->getEntityUrl( $entityId ) . $fragment;
	}

	/**
	 * @param array $link
	 * @param string $action
	 * @param string $text
	 *
	 * @return string HTML
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
