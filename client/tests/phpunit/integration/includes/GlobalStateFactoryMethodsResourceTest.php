<?php

namespace Wikibase\Client\Tests\Integration;

use MediaWiki\Tests\ExtensionJsonTestBase;
use ReflectionClass;
use ReflectionMethod;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\Hooks\NoLangLinkHandler;
use Wikibase\Client\Hooks\ShortDescHandler;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\WikibaseServices;
use Wikimedia\TestingAccessWrapper;

/**
 * Test to assert that factory methods of hook service classes (and similar services)
 * don't access the database or do http requests (which would be a performance issue).
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 * @coversNothing
 */
class GlobalStateFactoryMethodsResourceTest extends ExtensionJsonTestBase {

	protected string $extensionJsonPath = __DIR__ . '/../../../../../extension-client.json';

	protected ?string $serviceNamePrefix = 'WikibaseClient.';

	protected function setUp(): void {
		parent::setUp();

		// Configure the site group so that it doesn’t need to fall back to the DB site store
		$this->configureSiteGroup();
	}

	/** @dataProvider provideWikibaseServicesMethods */
	public function testWikibaseServicesMethod( string $methodName ) {
		$wikibaseServices = WikibaseClient::getWikibaseServices();

		$wikibaseServices->$methodName();
		$this->addToAssertionCount( 1 );
	}

	public static function provideWikibaseServicesMethods(): iterable {
		$reflectionClass = new ReflectionClass( WikibaseServices::class );
		foreach ( $reflectionClass->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
			yield $method->getName() => [ $method->getName() ];
		}
	}

	public function testEchoNotificationsHandlers() {
		EchoNotificationsHandlers::factory();
		$this->assertTrue( true );
	}

	public function testNoLangLinkHandler(): void {
		TestingAccessWrapper::newFromClass( NoLangLinkHandler::class )
			->factory();
		$this->assertTrue( true );
	}

	public function testShortDescHandler(): void {
		TestingAccessWrapper::newFromClass( ShortDescHandler::class )
			->factory();
		$this->assertTrue( true );
	}

	private function configureSiteGroup(): void {
		$settings = clone WikibaseClient::getSettings( $this->getServiceContainer() );
		$settings->setSetting( 'siteGroup', 'testgroup' );
		$this->setService( 'WikibaseClient.Settings', $settings );
	}

}
