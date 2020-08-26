<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\Repo\Tests\Api\WikibaseApiTestCase;
use Wikibase\Repo\Tests\FederatedProperties\FederatedPropertiesTestTrait;

/**
 * @license GPL-2.0-or-later
 *
 * @author Tobias Andersson
 */
abstract class FederatedPropertiesApiTestCase extends WikibaseApiTestCase {

	use FederatedPropertiesTestTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->setFederatedPropertiesEnabled();
	}
}
