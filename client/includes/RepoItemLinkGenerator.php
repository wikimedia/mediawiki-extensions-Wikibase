<?php

namespace Wikibase;

use \Wikibase\Lib\EntityIdParser;
use \ValueParsers\ParseException;

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RepoItemLinkGenerator {

	protected $namespacesChecker;

	protected $repoLinker;

	protected $entityIdParser;

	protected $enableSiteLinkWidget;

	protected $siteGroup;

	/**
	 * @since 0.4
	 *
	 * @param NamespaceChecker $namespaceChecker
	 * @param RepoLinker       $repoLinker
	 * @param EntityIdParser   $entityIdParser
	 * @param boolean          $enableSiteLinkWidget
	 * @param string           $siteGroup
	 */
	public function __construct( NamespaceChecker $namespaceChecker, RepoLinker $repoLinker,
		EntityIdParser $entityIdParser, $enableSiteLinkWidget, $siteGroup ) {

		$this->namespaceChecker = $namespaceChecker;
		$this->repoLinker = $repoLinker;
		$this->entityIdParser = $entityIdParser;
		$this->enableSiteLinkWidget = $enableSiteLinkWidget;
		$this->siteGroup = $siteGroup;
	}

	/**
	 * @since 0.4
	 *
	 * @param \Title $title
	 * @param string $action
	 * @param boolean $isAnon
	 * @param array|null $noExternalLangLinks
	 * @param string|null $prefixedId
	 *
	 * @return array|null
	 */
	public function getLink( \Title $title, $action, $isAnon, $noExternalLangLinks, $prefixedId ) {
		$editLink = null;

		if ( $this->canHaveLink( $title, $action, $noExternalLangLinks ) ) {
			if ( is_string( $prefixedId ) ) {
				$entityId = $this->entityIdParser->parse( $prefixedId );

				// link to the associated item on the repo
				$editLink = $this->getEditLinksLink( $entityId );
			} else {
				if ( $this->enableSiteLinkWidget === true && ! $isAnon ) {
					$editLink = $this->getAddLinksLink();
				}
			}
		}

		return $editLink;
	}

	/**
	 * @since 0.4
	 *
	 * @param \Title $title
	 * @param string $action
	 * @param mixed $noExternalLangLinks
	 *
	 * @return boolean
	 */
	protected function canHaveLink( \Title $title, $action, $noExternalLangLinks ) {
		if ( $action !== 'view' ) {
			return false;
		}

		if ( $title->exists() && $this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {

			if ( ! $this->isSuppressed( $noExternalLangLinks ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 0.4
	 *
	 * @param mixed
	 *
	 * @return boolean
	 */
	protected function isSuppressed( $noExternalLangLinks ) {
		if ( $noExternalLangLinks === null || !in_array( '*', $noExternalLangLinks ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId
	 *
	 * @return array
	 */
	protected function getEditLinksLink( EntityId $entityId ) {
		$fragment = '#sitelinks-' . htmlspecialchars( $this->siteGroup, ENT_QUOTES );

		$link = array(
			'href' => $this->repoLinker->repoItemUrl( $entityId ) . $fragment,
			'text' => wfMessage( 'wikibase-editlinks' )->text(),
			'title' => wfMessage( 'wikibase-editlinkstitle' )->text(),
			'class' => 'wbc-editpage',
		);

		return $link;
	}

	/**
	 * Used by the LinkItem js widget
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	protected function getAddLinksLink() {
		$link = array(
			'text' => '',
			'id' => 'wbc-linkToItem',
			'class' => 'wbc-editpage wbc-nolanglinks',
		);

		return $link;
	}
}
