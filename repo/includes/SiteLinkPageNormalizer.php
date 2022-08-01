<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use MediaWiki\Site\MediaWikiPageNameNormalizer;
use Site;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkPageNormalizer {

	/** @var string[] */
	private $redirectBadgeItems;

	/** @param string[] $redirectBadgeItems */
	public function __construct( array $redirectBadgeItems ) {
		$this->redirectBadgeItems = $redirectBadgeItems;
	}

	/**
	 * Normalize the given title on the given site,
	 * resolving redirects unless the badges include one of the configured redirect badges.
	 *
	 * @param Site $site
	 * @param string $title
	 * @param string[] $badges item ID serializations
	 * @return string|false
	 */
	public function normalize( Site $site, string $title, array $badges ) {
		$followFlag = MediaWikiPageNameNormalizer::FOLLOW_REDIRECT;

		foreach ( $badges as $badge ) {
			if ( in_array( $badge, $this->redirectBadgeItems, true ) ) {
				$followFlag = MediaWikiPageNameNormalizer::NOFOLLOW_REDIRECT;
				break;
			}
		}

		return $site->normalizePageName( $title, $followFlag );
	}

}
