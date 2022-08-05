<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests;

use MediaWiki\Site\MediaWikiPageNameNormalizer;
use MediaWikiUnitTestCase;
use Site;
use Wikibase\Repo\SiteLinkPageNormalizer;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 * @covers \Wikibase\Repo\SiteLinkPageNormalizer
 */
class SiteLinkPageNormalizerTest extends MediaWikiUnitTestCase {

	private const BADGE_GOODARTICLE = 'Q1';
	private const BADGE_FEATUREDARTICLE = 'Q2';
	private const BADGE_SITELINK_TO_REDIRECT = 'Q10';
	private const BADGE_INTENTIONAL_SITELINK_TO_REDIRECT = 'Q11';
	private const REDIRECT_BADGE_ITEMS = [
		self::BADGE_SITELINK_TO_REDIRECT,
		self::BADGE_INTENTIONAL_SITELINK_TO_REDIRECT,
	];

	public function provideBadgesAndFlags(): iterable {
		yield 'no badges' => [
			'badges' => [],
			'followFlag' => MediaWikiPageNameNormalizer::FOLLOW_REDIRECT,
		];
		yield 'one unrelated badge' => [
			'badges' => [ self::BADGE_GOODARTICLE ],
			'followFlag' => MediaWikiPageNameNormalizer::FOLLOW_REDIRECT,
		];
		yield 'two unrelated badges' => [
			'badges' => [ self::BADGE_GOODARTICLE, self::BADGE_FEATUREDARTICLE ],
			'followFlag' => MediaWikiPageNameNormalizer::FOLLOW_REDIRECT,
		];

		yield 'redirect badge' => [
			'badges' => [ self::BADGE_SITELINK_TO_REDIRECT ],
			'followFlag' => MediaWikiPageNameNormalizer::NOFOLLOW_REDIRECT,
		];
		yield 'other redirect badge' => [
			'badges' => [ self::BADGE_INTENTIONAL_SITELINK_TO_REDIRECT ],
			'followFlag' => MediaWikiPageNameNormalizer::NOFOLLOW_REDIRECT,
		];
		yield 'both redirect badges' => [
			'badges' => [ self::BADGE_SITELINK_TO_REDIRECT, self::BADGE_INTENTIONAL_SITELINK_TO_REDIRECT ],
			'followFlag' => MediaWikiPageNameNormalizer::NOFOLLOW_REDIRECT,
		];
		yield 'redirect badge and unrelated badge' => [
			'badges' => [ self::BADGE_SITELINK_TO_REDIRECT, self::BADGE_GOODARTICLE ],
			'followFlag' => MediaWikiPageNameNormalizer::NOFOLLOW_REDIRECT,
		];
		yield 'unrelated badge and redirect badge' => [
			'badges' => [ self::BADGE_GOODARTICLE, self::BADGE_SITELINK_TO_REDIRECT ],
			'followFlag' => MediaWikiPageNameNormalizer::NOFOLLOW_REDIRECT,
		];
	}

	/** @dataProvider provideBadgesAndFlags */
	public function testCallsNormalizeWithCorrectFlag( array $badges, int $followFlag ): void {
		$title = __METHOD__ . ': random title';
		$normalizedTitle = 'Normalized Random Title';
		$site = $this->createMock( Site::class );
		$site->expects( $this->once() )
			->method( 'normalizePageName' )
			->with( $title, $followFlag )
			->willReturn( $normalizedTitle );

		$normalizer = new SiteLinkPageNormalizer( self::REDIRECT_BADGE_ITEMS );
		$this->assertSame( $normalizedTitle,
			$normalizer->normalize( $site, $title, $badges ) );
	}

	public function testPassesThroughFalseReturnValue(): void {
		$site = $this->createMock( Site::class );
		$site->method( 'normalizePageName' )
			->willReturn( false );

		$normalizer = new SiteLinkPageNormalizer( [] );
		$this->assertSame( false, $normalizer->normalize( $site, '', [] ) );
	}

}
