<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\Tests\ExtensionJsonTestBase;
use ReflectionClass;
use ReflectionMethod;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\Repo\ParserOutput\TermboxFlag;
use Wikibase\Repo\WikibaseRepo;

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

	protected string $extensionJsonPath = __DIR__ . '/../../../../extension-repo.json';

	protected ?string $serviceNamePrefix = 'WikibaseRepo.';

	/** @dataProvider provideWikibaseServicesMethods */
	public function testWikibaseServicesMethod( string $methodName ) {
		$wikibaseServices = WikibaseRepo::getWikibaseServices();

		$wikibaseServices->$methodName();
		$this->addToAssertionCount( 1 );
	}

	public function provideWikibaseServicesMethods(): iterable {
		$reflectionClass = new ReflectionClass( WikibaseServices::class );
		foreach ( $reflectionClass->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
			yield $method->getName() => [ $method->getName() ];
		}
	}

	public function testTermboxFlag(): void {
		TermboxFlag::getInstance();
		$this->assertTrue( true );
	}

}
