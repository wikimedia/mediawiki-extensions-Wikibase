<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\SiteLinkPageNormalizer;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkPageNormalizerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$redirectBadgeItems = [ 'Q1', 'Q2' ];
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [ 'redirectBadgeItems' => $redirectBadgeItems ] ) );

		$normalizer = $this->getService( 'WikibaseRepo.SiteLinkPageNormalizer' );

		$this->assertInstanceOf( SiteLinkPageNormalizer::class, $normalizer );
		$this->assertSame( $redirectBadgeItems,
			TestingAccessWrapper::newFromObject( $normalizer )->redirectBadgeItems );
	}

}
