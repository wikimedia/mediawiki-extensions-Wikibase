<?php

namespace Wikibase\Repo\LinkedData;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Manages URIs for the linked data interface
 *
 * @license GPL-2.0+
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

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @param Title                $interfaceTitle
	 * @param string[]             $supportedExtensions an associative Array mapping canonical format names to file extensions.
	 * @param EntityTitleLookup    $entityTitleLookup
	 */
	public function __construct(
		Title $interfaceTitle,
		array $supportedExtensions,
		EntityTitleLookup $entityTitleLookup
	) {
		$this->interfaceTitle = $interfaceTitle;
		$this->supportedExtensions = $supportedExtensions;
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

		if ( isset( $this->supportedExtensions[ $format ] ) ) {
			return $this->supportedExtensions[ $format ];
		}

		return null;
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

		if ( isset( $formats[ $extension ] ) ) {
			return $formats[ $extension ];
		}

		return null;
	}

	/**
	 * Parser for the file-name like document name syntax for specifying an entity data document.
	 * This does not validate or interpret the ID or format, it just splits the string.
	 *
	 * @param string $doc
	 *
	 * @return string[] An array of two strings, array( $id, $format ).
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
	 * @return Title
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
	 * @return string
	 */
	public function getDocUrl( EntityId $id, $format = '', $revision = 0 ) {
		$params = '';

		if ( $revision > 0 ) {
			$params = 'oldid=' . $revision;
		}

		$title = $this->getDocTitle( $id, $format );
		$url = $title->getFullURL( $params );
		return $url;
	}

	/**
	 * Returns a list of all cacheable URLs for all the formats of
	 * the given entity.
	 *
	 * @param EntityId $id
	 *
	 * @return string[]
	 */
	public function getCacheableUrls( EntityId $id ) {
		$urls = [];

		foreach ( $this->supportedExtensions as $format => $ext ) {
			$title = $this->getDocTitle( $id, $format );
			$urls[] = $title->getInternalURL();
		}

		return $urls;
	}

}
