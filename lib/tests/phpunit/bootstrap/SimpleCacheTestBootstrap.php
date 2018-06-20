<?php

// phpcs:disable

namespace Cache\IntegrationTests {

	use PHPUnit\Framework\TestCase;

	if ( !class_exists( 'Cache\IntegrationTests\SimpleCacheTest' ) ) {
		abstract class SimpleCacheTest extends TestCase {
			public function setUp() {
				$this->markTestSkipped( 'Cache\IntegrationTests not installed.' );
			}
		}
	}
}
