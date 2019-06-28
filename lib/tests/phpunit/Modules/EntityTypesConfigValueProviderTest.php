<?php

namespace Wikibase\Lib\Tests\Modules;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Modules\EntityTypesConfigValueProvider;
use Wikibase\Lib\Modules\MediaWikiConfigValueProvider;

/**
 * @covers \Wikibase\Lib\Modules\EntityTypesConfigValueProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class EntityTypesConfigValueProviderTest extends \PHPUnit\Framework\TestCase {

	public function testConstructor_returnsMediaWikiConfigValueProviderInterface() {
		$instance = $this->newInstance();
		$this->assertInstanceOf( MediaWikiConfigValueProvider::class, $instance );
	}

	public function testGetKey() {
		$this->assertSame( 'wbEntityTypes', $this->newInstance()->getKey() );
	}

	public function testGetValue() {
		$expected = [
			'types' => [],
			'deserializer-factory-functions' => [],
		];
		$this->assertSame( $expected, $this->newInstance()->getValue() );
	}

	private function newInstance() {
		return new EntityTypesConfigValueProvider( new EntityTypeDefinitions( [] ) );
	}

}
