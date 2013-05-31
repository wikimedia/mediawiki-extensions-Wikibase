<?php

namespace Wikibase;

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
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RepoLinker {

	protected $baseUrl;

	protected $articlePath;

	protected $scriptPath;

	protected $namespaces;

	/**
	 * @since 0.4
	 *
	 * @param string $baseUrl
	 * @param string $articlePath
	 * @param string $scriptPath
	 * @param array $namespaces // repoNamespaces setting
	 */
	public function __construct( $baseUrl, $articlePath, $scriptPath, array $namespaces ) {
		$this->baseUrl = $baseUrl;
		$this->articlePath = $articlePath;
		$this->scriptPath = $scriptPath;
		$this->namespaces = $namespaces;
	}

	/**
	 * @since 0.2
	 *
	 * @return string
	 */
	public function baseUrl() {
		return rtrim( $this->baseUrl, '/' );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $target
	 *
	 * @return string
	 */
	public function repoArticleUrl( $target ) {
		$encodedPage = $this->encodePage( $target );
		return $this->baseUrl() . str_replace( '$1', $encodedPage, $this->articlePath );
	}

	/**
	 * Encode a page title
	 *
	 * @since 0.4
	 *
	 * @param string $page
	 *
	 * @return string
	 */
	protected function encodePage( $page ) {
		if ( !is_string( $page ) ) {
			trigger_error( __CLASS__ . ' : Trying to encode a page but $page is not a string.', E_USER_WARNING );
			return '';
		}
		return is_string( $page ) ? wfUrlencode( str_replace( ' ', '_', $page ) ) : '';
	}

	/**
	 * Returns a url to the item page on the repo
	 * @todo support all types of entities
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function repoItemUrl( EntityId $entityId ) {
		$prefixedId = $entityId->getPrefixedId();

		$itemNamespace = $this->getNamespace( Item::ENTITY_TYPE );

		$formattedNamespace = is_string( $itemNamespace ) && !empty( $itemNamespace ) ?
			$itemNamespace . ':' : $itemNamespace;

		return $this->repoArticleUrl( $formattedNamespace . strtoupper( $prefixedId ) );
	}

	/**
	 * Get namespace of an entity in string format
	 *
	 * @since 0.2
	 *
	 * @param string $entityType
	 *
	 * @return string
	 */
	public function getNamespace( $entityType ) {
		$nsList = $this->namespaces;
		$ns = null;

		$contentType = 'wikibase-' . $entityType;
		if ( is_array( $nsList ) && array_key_exists( $contentType, $nsList ) ) {
			$ns = $nsList[$contentType];
		} else {
			// todo: support queries and better error handling here
			return false;
		}

		return $ns;
	}

	/**
	 * @since 0.3
	 * @todo could be made nicer
	 *
	 * @param string|null $target - needed only for /wiki/$1 type links
	 * @param string $text - what goes inside the <a> tag
	 * @param array $attribs - optional, used only for query string urls, and both
	 *  url formats to insert additional css classes; example:
	 *    $attribs = array(
	 *      'query' =>
	 *        'params' => array(
	 *          'action' => 'query',
	 *           'meta' => 'siteinfo
	 *        ),
	 *        'type' => 'api' // or 'index' for index.php
	 *      ),
	 *      'class' => 'wikibase-link item' // string
	 *    );
	 *
	 * @throws \MWException
	 *
	 * @return string
	 */
	public function repoLink( $target, $text, $attribs = array() ) {
		if ( array_key_exists( 'query', $attribs ) && is_array( $attribs['query'] ) ) {
			if ( $attribs['query']['type'] === 'index' ) {
				$url = $this->baseUrl() . $this->scriptPath . '/index.php';
			} else if ( $attribs['query']['type'] === 'api' ) {
				$url = $this->baseUrl() . $this->scriptPath . '/api.php';
			} else {
				throw new \MWException( 'Invalid query type' );
			}
			$url = wfAppendQuery( $url, wfArrayToCgi( $attribs['query']['params'] ) );
			unset( $attribs['query'] );
		} else {
			// should not happen, but just in case...
			if ( !is_string( $target ) ) {
				throw new \MWException( 'repoLink requires a $target to contruct an article url.' );
			}
			$url = $this->repoArticleUrl( $target );
		}

		if ( $url === null ) {
			throw new \MWException( 'Could not build a repoLink url.' );
		}

		$class = 'plainlinks';
		// @todo more validation and maybe accept array instead
		if ( array_key_exists( 'class', $attribs ) ) {
			$class .= ' ' . $attribs['class'];
		}

		$attribs['class'] = $class;
		$attribs['href'] = $url;

		return \Html::element( 'a', $attribs, $text );
	}

}
