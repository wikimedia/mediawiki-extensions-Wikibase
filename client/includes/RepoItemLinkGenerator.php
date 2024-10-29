<?php

namespace Wikibase\Client;

use MediaWiki\Context\IContextSource;
use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * @license GPL-2.0-or-later
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
	 * @param NamespaceChecker $namespaceChecker
	 * @param RepoLinker $repoLinker
	 * @param EntityIdParser $entityIdParser
	 * @param string $langLinkSiteGroup
	 * @param string $siteGlobalId
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
	 * @param IContextSource $context
	 * @param string $action
	 * @param bool $hasLangLinks
	 * @param string[]|null $noExternalLangLinks
	 * @param string|null $prefixedId
	 *
	 * @return string|null HTML or null for no link
	 */
	public function getLink( IContextSource $context, $action, $hasLangLinks, ?array $noExternalLangLinks, $prefixedId ) {
		if ( $this->canHaveLink( $context->getTitle(), $action, $noExternalLangLinks ) ) {
			$entityId = null;

			if ( is_string( $prefixedId ) ) {
				$entityId = $this->entityIdParser->parse( $prefixedId );
			}

			if ( $entityId && $hasLangLinks ) {
				return $this->getEditLinksLink( $context, $entityId );
			}

			return $this->getAddLinksLink( $context, $entityId );
		}

		return null;
	}

	/**
	 * @param Title $title
	 * @param string $action
	 * @param string[]|null $noExternalLangLinks
	 *
	 * @return bool
	 */
	private function canHaveLink( Title $title, $action, array $noExternalLangLinks = null ) {
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
	 * @param string[]|null $noExternalLangLinks
	 *
	 * @return bool
	 */
	private function isSuppressed( array $noExternalLangLinks = null ) {
		return $noExternalLangLinks !== null && in_array( '*', $noExternalLangLinks );
	}

	/**
	 * @param IContextSource $context
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	private function getEditLinksLink( IContextSource $context, EntityId $entityId ) {
		$link = [
			'href' => $this->getEntityUrl( $entityId ),
			'title' => $context->msg( 'wikibase-editlinkstitle' )->text(),
			'class' => 'wbc-editpage',
		];

		$text = $context->msg( 'wikibase-editlinks' )->text();
		return $this->formatLink( $link, 'edit', $text );
	}

	/**
	 * Links to the item or Special:NewItem on the repo. The link might get
	 * overwritten by the JavaScript add links widget.
	 *
	 * @param IContextSource $context
	 * @param EntityId|null $entityId Entity which $title is linked to
	 *
	 * @return string HTML
	 */
	private function getAddLinksLink( IContextSource $context, EntityId $entityId = null ) {
		if ( $entityId ) {
			$href = $this->getEntityUrl( $entityId );
		} else {
			$href = $this->getNewItemUrl( $context->getTitle() );
		}

		$link = [
			'href' => $href,
			'title' => $context->msg( 'wikibase-addlinkstitle' )->text(),
			'class' => 'wbc-editpage',
		];

		$text = $context->msg( 'wikibase-linkitem-addlinks' )->text();
		return $this->formatLink( $link, 'add', $text );
	}

	/**
	 * @param Title $title
	 *
	 * @return string
	 */
	private function getNewItemUrl( Title $title ) {
		$params = [
			'site' => $this->siteGlobalId,
			'page' => $title->getPrefixedText(),
		];

		$url = $this->repoLinker->getPageUrl( 'Special:NewItem' );
		$url = $this->repoLinker->addQueryParams( $url, $params );

		return $url;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string HTML unsafe
	 */
	private function getEntityUrl( EntityId $entityId ) {
		$fragment = '#sitelinks-' . $this->langLinkSiteGroup;
		return $this->repoLinker->getEntityUrl( $entityId ) . $fragment;
	}

	/**
	 * @param array $linkAttribs
	 * @param string $action
	 * @param string $text
	 *
	 * @return string HTML
	 */
	private function formatLink( array $linkAttribs, $action, $text ) {
		$link = Html::element( 'a', $linkAttribs, $text );

		$html = Html::rawElement(
			'span',
			[
				'class' => "wb-langlinks-$action wb-langlinks-link",
			],
			$link
		);

		return $html;
	}

}
