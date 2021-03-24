<?php

namespace Wikibase\Repo\LinkedData;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Manages URIs for the linked data interface
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityDataUriManager {

	/**
	 * @var Title
	 */
	private $interfaceTitle;

	/**
	 * @var string[]
	 */
	private $supportedExtensions;

	/** @var string[] */
	private $cachePaths;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @param Title                $interfaceTitle
	 * @param string[]             $supportedExtensions an associative Array mapping canonical format names to file extensions.
	 * @param string[]             $cachePaths List of additional URL paths for which entity data should be cached,
	 *                                         with {entity_id} and {revision_id} placeholders.
	 * @param EntityTitleLookup    $entityTitleLookup
	 */
	public function __construct(
		Title $interfaceTitle,
		array $supportedExtensions,
		array $cachePaths,
		EntityTitleLookup $entityTitleLookup
	) {
		$this->interfaceTitle = $interfaceTitle;
		$this->supportedExtensions = $supportedExtensions;
		$this->cachePaths = $cachePaths;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * @param string $format a canonical format name
	 *
	 * @return string|null a file extension (without the leading dot), or null.
	 */
	public function getExtension( $format ) {
		if ( $format === '' ) {
			// "no format" -> "no extension"
			return '';
		}

		return $this->supportedExtensions[$format] ?? null;
	}

	/**
	 * @param string $extension file extension
	 *
	 * @return string|null the canonical format name (or null)
	 */
	public function getFormatName( $extension ) {
		$extension = trim( strtolower( $extension ) );

		if ( $extension === '' ) {
			// "no extension" -> "no format"
			return '';
		}

		if ( isset( $this->supportedExtensions[ $extension ] ) ) {
			return $extension; // already is a format name
		}

		$formats = array_flip( $this->supportedExtensions );
		return $formats[$extension] ?? null;
	}

	/**
	 * Parser for the file-name like document name syntax for specifying an entity data document.
	 * This does not validate or interpret the ID or format, it just splits the string.
	 *
	 * @param string $doc
	 *
	 * @return string[] A 2-element array in the form [ string $id, string $format ]
	 */
	public function parseDocName( $doc ) {
		$format = '';

		// get format from $doc or request param
		if ( preg_match( '#\.([-./\w]+)$#', $doc, $m ) ) {
			$doc = preg_replace( '#\.([-./\w]+)$#', '', $doc );
			$format = $m[1];
		}

		return [
			$doc,
			$format,
		];
	}

	/**
	 * Returns the canonical subpage name used to address a given set
	 * of entity data.
	 *
	 * @param EntityId $id       The entity
	 * @param string|null   $format   The (normalized) format name, or ''
	 *
	 * @return string
	 */
	public function getDocName( EntityId $id, $format = '' ) {
		$doc = $id->getSerialization();

		//Note: Use upper case everywhere.
		$doc = strtoupper( $doc );

		if ( $format !== null && $format !== '' ) {
			$ext = $this->getExtension( $format );

			if ( $ext === null ) {
				// if no extension is known, use the format name as the extension
				$ext = $format;
			}

			$doc .= '.' . $ext;
		}

		return $doc;
	}

	/**
	 * Returns a Title representing the given document.
	 *
	 * @param EntityId $id       The entity
	 * @param string|null   $format   The (normalized) format name, or ''
	 *
	 * @return Title|null
	 */
	public function getDocTitle( EntityId $id, $format = '' ) {
		if ( $format === 'html' ) {
			$title = $this->entityTitleLookup->getTitleForId( $id );
		} else {
			$doc = $this->getDocName( $id, $format );

			$name = $this->interfaceTitle->getPrefixedText();
			if ( $doc !== null && $doc !== '' ) {
				$name .= '/' . $doc;
			}

			$title = Title::newFromText( $name );
		}

		return $title;
	}

	/**
	 * Returns a Title representing the given document.
	 *
	 * @param EntityId    $id       The entity
	 * @param string|null $format   The (normalized) format name, or ''
	 * @param int         $revision
	 *
	 * @return string|null
	 */
	public function getDocUrl( EntityId $id, $format = '', $revision = 0 ) {
		$params = '';

		if ( $revision > 0 ) {
			$params = 'oldid=' . $revision;
		}

		$title = $this->getDocTitle( $id, $format );
		if ( $title === null ) {
			return null;
		}
		$url = $title->getFullURL( $params );
		return $url;
	}

	/**
	 * Returns a list of all cacheable URLs for all the formats of
	 * the given entity.
	 *
	 * @param EntityId $id
	 * @param int $revision Revision ID for which to build URLs,
	 * or 0 for latest-revision URLs.
	 * @return string[] canonical URLs
	 */
	public function getCacheableUrls( EntityId $id, int $revision = 0 ): array {
		if ( $revision <= 0 ) {
			return [];
		}

		$idSerialization = $id->getSerialization();
		return array_map( function( $path ) use ( $idSerialization, $revision ) {
			global $wgCanonicalServer;
			return $this->getCacheUrl( $path, $idSerialization, $revision, $wgCanonicalServer );
		}, $this->cachePaths );
	}

	/**
	 * Similar to {@link getCacheableUrls()},
	 * but returns internal URLs (to be sent to {@link HtmlCacheUpdater}),
	 * rather than canonical URLs (against which a request URL may be compared).
	 *
	 * @param EntityId $id as for getCacheableUrls()
	 * @param int $revision as for getCacheableUrls()
	 * @return string[] internal URLs
	 */
	public function getPotentiallyCachedUrls( EntityId $id, int $revision = 0 ): array {
		if ( $revision <= 0 ) {
			return [];
		}

		$idSerialization = $id->getSerialization();
		return array_map( function( $path ) use ( $idSerialization, $revision ) {
			global $wgInternalServer, $wgServer;
			return $this->getCacheUrl( $path, $idSerialization, $revision, $wgInternalServer ?: $wgServer );
		}, $this->cachePaths );
	}

	/**
	 * Turn a cache path from the config into a full URL.
	 * @param string $path The path, with {entity_id} and {revision_id} placeholders.
	 * @param string $entityId The entity ID serialization.
	 * @param int $revision The revision ID.
	 * @param string $server Either $wgCanonicalServer or $wgInternalServer,
	 * depending on what kind of URL you want.
	 * @return string A full URL that can be compared against request URLs
	 * or sent to {@link HtmlCacheUpdater}, depending on $server.
	 */
	private function getCacheUrl( string $path, string $entityId, int $revision, string $server ) {
		return $server . strtr( $path, [
			'{entity_id}' => $entityId,
			'{revision_id}' => (string)$revision,
		] );
	}

}
