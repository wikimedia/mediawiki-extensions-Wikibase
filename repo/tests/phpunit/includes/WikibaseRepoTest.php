<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\Tests\ExtensionServicesTestBase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\WikibaseRepo
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseRepoTest extends ExtensionServicesTestBase {

	protected string $className = WikibaseRepo::class;

	protected string $serviceNamePrefix = 'WikibaseRepo.';

}
