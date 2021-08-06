<?php

declare( strict_types = 1 );

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

	/** @var string */
	private $baseUrl;

	/** @var string */
	private $articlePath;

	/** @var string */
	private $scriptPath;

	/** @var EntitySourceDefinitions */
	private $entitySourceDefinitions;

	public function __construct(
		EntitySourceDefinitions $entitySourceDefinitions,
		string $baseUrl,
		string $articlePath,
		string $scriptPath
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
	public function getPageUrl( string $page ): string {
		$encodedPage = $this->encodePage( $page );
		return $this->getBaseUrl() . str_replace( '$1', $encodedPage, $this->articlePath );
	}

	private function encodePage( string $page ): string {
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
	public function formatLink( string $url, string $text, array $attribs = [] ): string {
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
	 * @param string|null $text Defaults to the entity id serialization.
	 *
	 * @return string (html)
	 */
	public function buildEntityLink( EntityId $entityId, array $classes = [], string $text = null ): string {
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

	public function getEntityTitle( EntityId $entityId ): string {
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
	public function getEntityUrl( EntityId $entityId ): string {
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
	public function getEntityConceptUri( EntityId $entityId ): string {
		$baseUri = $this->getConceptBaseUri( $entityId );
		return $baseUri . '/' . wfUrlencode( $entityId->getLocalPart() );
	}

	public function getBaseUrl(): string {
		return rtrim( $this->baseUrl, '/' );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LogicException when there is no base URI for the repository $entityId belongs to
	 *
	 * @return string
	 */
	private function getConceptBaseUri( EntityId $entityId ): string {
		$uri = null;
		$source = $this->entitySourceDefinitions->getDatabaseSourceForEntityType( $entityId->getEntityType() );
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

	public function getApiUrl(): string {
		return $this->getBaseUrl() . $this->scriptPath . '/api.php';
	}

	public function getIndexUrl(): string {
		return $this->getBaseUrl() . $this->scriptPath . '/index.php';
	}

	public function addQueryParams( string $url, array $params ): string {
		return wfAppendQuery( $url, wfArrayToCgi( $params ) );
	}

}
