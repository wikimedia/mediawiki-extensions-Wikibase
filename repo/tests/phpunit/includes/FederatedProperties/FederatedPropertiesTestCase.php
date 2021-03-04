<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use MediaWikiTestCase;

/**
 * @license GPL-2.0-or-later
 */
abstract class FederatedPropertiesTestCase extends MediaWikiTestCase {

	use FederatedPropertiesTestTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->setFederatedPropertiesEnabled();
	}

}
