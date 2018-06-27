<?php

namespace Wikibase\Lib\Tests\Store;

use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lib\Store\CachingFallbackLabelDescriptionLookup;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Store\EntityRevisionLookup;

class CachingFallbackLabelDescriptionLookupTest extends TestCase {

	public function testAds() {
		$cache = $this->prophesize( CacheInterface::class );
		$revLookup = $this->prophesize( EntityRevisionLookup::class );
		$ldLookup = $this->prophesize( LabelDescriptionLookup::class );
		$ttl = 10;

		$lookup = new CachingFallbackLabelDescriptionLookup(
			$cache->reveal(),
			$revLookup->reveal(),
			$ldLookup->reveal(),
			$ttl
		);


	}

}
