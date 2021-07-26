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

	/**
	 * @var bool Test without local properties by default. Can be overridden by subclasses in setUp before calling parent::setUp()
	 */
	protected $withLocalPropertySource = false;

	protected function setUp(): void {
		parent::setUp();
		$this->setFederatedPropertiesEnabled( $this->withLocalPropertySource );
	}

}
