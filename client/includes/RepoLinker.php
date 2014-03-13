<?php

namespace Wikibase;

use Html;
use InvalidArgumentException;

/**
 * @since 0.2
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
	 * @todo: need a better way to have knowledge of repo namespace mappings
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
			throw new InvalidArgumentException( "No namespace configured for entities of type $entityType" );
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
	 * @since 0.3
	 *
	 * @param string $page
	 *
	 * @throws InvalidArgumentException
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
	 * Format a link, with url encoding
	 *
	 * @since 0.5
	 *
	 * @param string $url
	 * @param string $text
	 * @param array $attribs
	 *
	 * @return string (html)
	 */
	public function formatLink( $url, $text, $attribs = array() ) {
		$attribs['class'] = isset( $attribs['class'] ) ?
			'plainlinks ' . $attribs['class'] : 'plainlinks';

		$attribs['href'] = $url;

		return Html::element( 'a', $attribs, $text );
	}

	/**
	 * Constructs an html link to an entity
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 * @param array $classes
	 *
	 * @return string (html)
	 */
	public function buildEntityLink( EntityId $entityId, array $classes = array() ) {
		$title = $entityId->getSerialization();
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
	 * Get the full title as string, including namespace for an entity
	 * @todo: use a more robust mechanism for building entity titles
	 *   if efficient enough, maybe EntityTitleLookup.
	 *
	 * @param EntityId
	 *
	 * @return string
	 */
	public function getEntityTitle( EntityId $entityId ) {
		$entityNamespace = $this->getEntityNamespace( $entityId );
		$title = $entityId->getSerialization();

		if ( $entityNamespace ) {
			$title = $entityNamespace . ':' . $title;
		}

		return $title;
	}

	/**
	 * Constructs a link to an entity
	 *
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
	 * @param string $url
	 * @param array $params
	 *
	 * @return string
	 */
	public function addQueryParams( $url, array $params ) {
		return wfAppendQuery( $url, wfArrayToCgi( $params ) );
	}

}
