<?php

namespace Wikibase\Client;

use Html;
use InvalidArgumentException;
use LogicException;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RepoLinker {

	private $baseUrl;

	private $articlePath;

	private $scriptPath;

	private $entitySourceDefinitions;

	/**
	 * @param EntitySourceDefinitions $entitySourceDefinitions
	 * @param string $baseUrl
	 * @param string $articlePath
	 * @param string $scriptPath
	 */
	public function __construct(
		EntitySourceDefinitions $entitySourceDefinitions,
		$baseUrl,
		$articlePath,
		$scriptPath
	) {
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->baseUrl = $baseUrl;
		$this->articlePath = $articlePath;
		$this->scriptPath = $scriptPath;
	}

	/**
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
	 * @param string $page
	 *
	 * @return string
	 */
	private function encodePage( $page ) {
		return wfUrlencode( str_replace( ' ', '_', $page ) );
	}

	/**
	 * Format a link, with url encoding
	 *
	 * @param string $url
	 * @param string $text
	 * @param array $attribs
	 *
	 * @return string (html)
	 */
	public function formatLink( $url, $text, array $attribs = [] ) {
		$attribs['class'] = isset( $attribs['class'] )
			? 'extiw ' . $attribs['class']
			: 'extiw';

		$attribs['href'] = $url;

		return Html::element( 'a', $attribs, $text );
	}

	/**
	 * Constructs an html link to an entity
	 *
	 * @param EntityId $entityId
	 * @param array $classes
	 * @param string $text Defaults to the entity id serialization.
	 *
	 * @return string (html)
	 */
	public function buildEntityLink( EntityId $entityId, array $classes = [], $text = null ) {
		if ( $text === null ) {
			$text = $entityId->getSerialization();
		}

		$class = 'wb-entity-link';

		if ( $classes !== [] ) {
			$class .= ' ' . implode( ' ', $classes );
		}

		return $this->formatLink(
			$this->getEntityUrl( $entityId ),
			$text,
			[ 'class' => $class ]
		);
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function getEntityTitle( EntityId $entityId ) {
		$title = $entityId->getSerialization();
		return 'Special:EntityPage/' . $title;
	}

	/**
	 * Constructs a link to an entity
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function getEntityUrl( EntityId $entityId ) {
		$title = $this->getEntityTitle( $entityId );
		return $this->getPageUrl( $title );
	}

	/**
	 * Constructs the machine followable link to an entity. E.g., https://www.wikidata.org/entity/Q42.
	 *
	 * @param EntityId $entityId
	 *
	 * @throws LogicException when there is no base URI for the repository $entityId belongs to
	 *
	 * @return string
	 */
	public function getEntityConceptUri( EntityId $entityId ) {
		$baseUri = $this->getConceptBaseUri( $entityId );
		return $baseUri . '/' . wfUrlencode( $entityId->getLocalPart() );
	}

	/**
	 * @return string
	 */
	public function getBaseUrl() {
		return rtrim( $this->baseUrl, '/' );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LogicException when there is no base URI for the repository $entityId belongs to
	 *
	 * @return string
	 */
	private function getConceptBaseUri( EntityId $entityId ) {
		$uri = null;
		$source = $this->entitySourceDefinitions->getSourceForEntityType( $entityId->getEntityType() );
		if ( $source !== null ) {
			$uri = $source->getConceptBaseUri();
		}

		if ( !isset( $uri ) ) {
			throw new LogicException(
				'No base URI for for concept URI for repository: ' . $entityId->getRepositoryName()
			);
		}
		return rtrim( $uri, '/' );
	}

	/**
	 * @return string
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
