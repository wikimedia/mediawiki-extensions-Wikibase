<?php

namespace Wikibase\Repo;

use BagOStuff;
use MediaWiki\Site\MediaWikiPageNameNormalizer;
use InvalidArgumentException;
use Wikimedia\Assert\Assert;

/**
 * Caching service that looks up normalized file names from Wikimedia Commons.
 *
 * @license GPL 2+
 * @author Marius Hoch
 */
class CachingCommonsMediaFileNameLookup {

	const CACHE_DURATION = 600;

	/**
	 * @var MediaWikiPageNameNormalizer
	 */
	private $mediaWikiPageNameNormalizer;

	/**
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @param MediaWikiPageNameNormalizer $mediaWikiPageNameNormalizer
	 * @param BagOStuff $cache
	 */
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
	 * @return string|null The normalized file name or null (if the page does not exist)
	 * @throws InvalidArgumentException
	 */
	public function normalize( $fileName ) {
		Assert::parameterType( 'string', $fileName, '$pageName' );

		$cachedValue = $this->cache->get( $this->getCacheKey( $fileName ) );
		if ( $cachedValue !== false ) {
			return $cachedValue;
		}

		$actualPageName = $this->mediaWikiPageNameNormalizer->normalizePageName(
			'File:' . $fileName
		);

		if ( $actualPageName === false ) {
			$actualPageName = null;
		} else {
			$actualPageName = str_replace( 'File:', '', $actualPageName );
		}

		// Cache with the given name and (if available) the normalized one.
		if ( $actualPageName !== null ) {
			$this->cache->set(
				$this->getCacheKey( $actualPageName ),
				$actualPageName,
				self::CACHE_DURATION
			);
		}

		$this->cache->set(
			$this->getCacheKey( $fileName ),
			$actualPageName,
			self::CACHE_DURATION
		);

		return $actualPageName;
	}

	/**
	 * @param string $pageName
	 * @return string
	 */
	private function getCacheKey( $pageName ) {
		return 'commons-media-' . $pageName;
	}

}
