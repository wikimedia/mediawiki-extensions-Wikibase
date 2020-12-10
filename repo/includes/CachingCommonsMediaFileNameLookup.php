<?php

namespace Wikibase\Repo;

use BagOStuff;
use InvalidArgumentException;
use MediaWiki\Site\MediaWikiPageNameNormalizer;
use Wikimedia\Assert\Assert;

/**
 * Caching service that looks up normalized file names from Wikimedia Commons.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class CachingCommonsMediaFileNameLookup {

	private const CACHE_DURATION = 600;

	/**
	 * @var MediaWikiPageNameNormalizer
	 */
	private $mediaWikiPageNameNormalizer;

	/**
	 * @var BagOStuff
	 */
	private $cache;

	public function __construct(
		MediaWikiPageNameNormalizer $mediaWikiPageNameNormalizer,
		BagOStuff $cache
	) {
		$this->mediaWikiPageNameNormalizer = $mediaWikiPageNameNormalizer;
		$this->cache = $cache;
	}

	/**
	 * @param string $fileName File name, without the File: prefix.
	 *
	 * @return string|null The normalized file name or null if the page does not exist
	 * @throws InvalidArgumentException
	 */
	public function lookupFileName( $fileName ) {
		Assert::parameterType( 'string', $fileName, '$pageName' );

		$cachedValue = $this->cache->get( $this->getCacheKey( $fileName ) );
		if ( $cachedValue !== false ) {
			return $cachedValue;
		}

		$actualFileName = $this->doLookup( $fileName );
		$this->cacheResult( $fileName, $actualFileName );

		return $actualFileName;
	}

	/**
	 * @param string $fileName
	 *
	 * @return string|null
	 */
	private function doLookup( $fileName ) {
		$actualPageName = $this->mediaWikiPageNameNormalizer->normalizePageName(
			'File:' . $fileName,
			'https://commons.wikimedia.org/w/api.php'
		);

		if ( $actualPageName === false ) {
			return null;
		} else {
			return str_replace( 'File:', '', $actualPageName );
		}
	}

	/**
	 * @param string $inputFileName
	 * @param string|null $actualFileName
	 */
	private function cacheResult( $inputFileName, $actualFileName ) {
		// Cache with the given name and (if available) the normalized one.
		if ( $actualFileName !== null ) {
			$this->cache->set(
				$this->getCacheKey( $actualFileName ),
				$actualFileName,
				self::CACHE_DURATION
			);
		}

		$this->cache->set(
			$this->getCacheKey( $inputFileName ),
			$actualFileName,
			self::CACHE_DURATION
		);
	}

	/**
	 * @param string $fileName
	 * @return string
	 */
	private function getCacheKey( $fileName ) {
		return 'commons-media-' . $fileName;
	}

}
