<?php

namespace Wikibase\Client\Tests\Integration;

use MediaWikiIntegrationTestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use Wikibase\Client\WikibaseClient;

/**
 * @covers \Wikibase\Client\WikibaseClient
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseClientTest extends MediaWikiIntegrationTestCase {

	/** @dataProvider provideMethods */
	public function testMethodSignature( ReflectionMethod $method ): void {
		$this->assertTrue( $method->isPublic(),
			'service accessor must be public' );
		$this->assertTrue( $method->isStatic(),
			'service accessor must be static' );
		$this->assertStringStartsWith( 'get', $method->getName(),
			'service accessor must be a getter' );
		$this->assertTrue( $method->hasReturnType(),
			'service accessor must declare return type' );
	}

	/** @dataProvider provideMethods */
	public function testMethodWithDefaultServiceContainer( ReflectionMethod $method ): void {
		$methodName = $method->getName();
		$serviceName = 'WikibaseClient.' . substr( $methodName, 3 );
		$expectedService = $this->createValue( $method->getReturnType() );
		$this->setService( $serviceName, $expectedService );

		$actualService = WikibaseClient::$methodName();

		$this->assertSame( $expectedService, $actualService,
			'should return service from MediaWikiServices' );
	}

	/** @dataProvider provideMethods */
	public function testMethodWithCustomServiceContainer( ReflectionMethod $method ): void {
		$methodName = $method->getName();
		$serviceName = 'WikibaseClient.' . substr( $methodName, 3 );
		$expectedService = $this->createValue( $method->getReturnType() );
		$services = $this->createMock( ContainerInterface::class );
		$services->expects( $this->once() )
			->method( 'get' )
			->with( $serviceName )
			->willReturn( $expectedService );

		$actualService = WikibaseClient::$methodName( $services );

		$this->assertSame( $expectedService, $actualService,
			'should return service from injected container' );
	}

	public function provideMethods(): iterable {
		$reflectionClass = new ReflectionClass( WikibaseClient::class );
		$methods = $reflectionClass->getMethods();

		foreach ( $methods as $method ) {
			if ( $method->isConstructor() ) {
				continue;
			}
			yield $method->getName() => [ $method ];
		}
	}

	private function createValue( ReflectionType $type ) {
		// (in PHP 8.0, account for $type being a ReflectionUnionType here)
		$this->assertInstanceOf( ReflectionNamedType::class, $type );
		/** @var ReflectionNamedType $type */
		if ( $type->allowsNull() ) {
			return null;
		}
		if ( $type->isBuiltin() ) {
			switch ( $type->getName() ) {
				case 'bool':
					return true;
				case 'int':
					return 0;
				case 'float':
					return 0.0;
				case 'string':
					return '';
				case 'array':
				case 'iterable':
					return [];
				case 'callable':
					return 'is_null';
				default:
					$this->fail( "unknown builtin type {$type->getName()}" );
			}
		}
		return $this->createMock( $type->getName() );
	}

}
