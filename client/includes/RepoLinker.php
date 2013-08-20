<?php

namespace Wikibase;

use Html;
use InvalidArgumentException;
use Wikibase\Client\WikibaseClient;

/**
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
	 * Get namespace of an entity in string format
	 *
	 * @since 0.5
	 *
	 * @param string $entityType
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getNamespace( $entityType ) {
		$contentType = 'wikibase-' . $entityType;

		if ( !array_key_exists( $contentType, $this->namespaces ) ) {
			throw new InvalidArgumentException( 'Unknown entity namespace' );
		}

		return $this->namespaces[$contentType];
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function getEntityNamespace( EntityId $entityId ) {
		$entityType = $entityId->getEntityType();
		return $this->getNamespace( $entityType );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function getEntityTitle( EntityId $entityId ) {
		$ns = $this->getEntityNamespace( $entityId );
		$title = $entityId->getSerialization();

		if ( strlen( $ns ) > 0 ) {
			$title = "$ns:$title";
		}

		return $title;
	}

	/**
	 * @since 0.3
	 *
	 * @param string $page
	 *
	 * @return string
	 */
	public function getPageUrl( $page ) {
		if ( !is_string( $page ) ) {
			throw new InvalidArgumentException( '$page must be a string' );
		}

		$encodedPage = $this->encodePage( $page );
		return $this->getBaseUrl() . str_replace( '$1', $encodedPage, $this->articlePath );
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
		return wfUrlencode( str_replace( ' ', '_', $page ) );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $url
	 * @param string $text
	 * @param array $attribs
	 *
	 * @return string
	 */
	public function formatLink( $url, $text, $attribs = array() ) {
		$attribs['class'] = isset( $attribs['class'] ) ?
			'plainlinks ' . $attribs['class'] : 'plainlinks';

		$attribs['href'] = $url;

		return Html::element( 'a', $attribs, $text );
	}

	/**
	 * @since 0.4
	 *
	 * @param ExternalChange $externalChange
	 * @param array $classes
	 *
	 * @return string
	 */
	public function buildEntityLink( EntityId $entityId, array $classes = array() ) {
		$title = $this->getEntityTitle( $entityId );

		$class = 'wb-entity-link';

		if ( $classes !== array() ) {
			$class .= ' ' . implode( ' ', $classes );
		}

		return $this->formatLink(
			$this->getEntityUrl( $entityId ),
			$title,
			array( 'class' => $class )
		);
	}

	/**
	 * @param EntityId
	 *
	 * @return string
	 */
	public function getEntityUrl( EntityId $entityId ) {
		$title = $this->getEntityTitle( $entityId );

		return $this->getPageUrl( $title );
	}

	/**
	 * @return string
	 */
	public function getBaseUrl() {
		return rtrim( $this->baseUrl, '/' );
	}

	/**
	 * @return string;
	 */
	public function getApiUrl() {
		return $this->getBaseUrl() . $this->scriptPath . '/api.php';
	}

	/**
	 * @return string
	 */
	public function getIndexUrl() {
		return $this->getBaseUrl() . $this->scriptPath . '/index.php';
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	public function addQueryParams( $url, array $params ) {
		return wfAppendQuery( $url, wfArrayToCgi( $params ) );
	}

}
