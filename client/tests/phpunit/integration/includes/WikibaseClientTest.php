<?php

namespace Wikibase\Client\Tests\Integration;

use MediaWiki\Tests\ExtensionServicesTestBase;
use Wikibase\Client\WikibaseClient;

/**
 * @covers \Wikibase\Client\WikibaseClient
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseClientTest extends ExtensionServicesTestBase {

	protected string $className = WikibaseClient::class;

	protected string $serviceNamePrefix = 'WikibaseClient.';

}
