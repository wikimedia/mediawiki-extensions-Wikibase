<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use MediaWikiIntegrationTestCase;

/**
 * @license GPL-2.0-or-later
 */
abstract class FederatedPropertiesTestCase extends MediaWikiIntegrationTestCase {

	use FederatedPropertiesTestTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->markTestSkipped( 'federatedPropertiesEnabled is forced to false for REL1_37' );
		$this->setFederatedPropertiesEnabled();
	}

}
