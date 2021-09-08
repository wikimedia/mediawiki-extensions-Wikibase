<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Repo\PropertyInfoBuilder;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyInfoBuilderTest extends ServiceWiringTestCase {

	/** @dataProvider provideSettingsWithPropertyIdMap */
	public function testConstruction( array $settings, array $expectedPropertyIdMap ): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( $settings ) );

		/** @var PropertyInfoBuilder $propertyInfoBuilder */
		$propertyInfoBuilder = $this->getService( 'WikibaseRepo.PropertyInfoBuilder' );

		$this->assertInstanceOf( PropertyInfoBuilder::class, $propertyInfoBuilder );
		$this->assertEqualsCanonicalizing( $expectedPropertyIdMap, $propertyInfoBuilder->getPropertyIdMap() );
	}

	public function provideSettingsWithPropertyIdMap(): iterable {
		yield 'default' => [
			'settings' => [
				'formatterUrlProperty' => null,
				'canonicalUriProperty' => null,
			],
			'expectedPropertyIdMap' => [],
		];

		yield 'with formatter URL property' => [
			'settings' => [
				'formatterUrlProperty' => 'P123',
				'canonicalUriProperty' => null,
			],
			'expectedPropertyIdMap' => [
				PropertyInfoLookup::KEY_FORMATTER_URL => new NumericPropertyId( 'P123' ),
			],
		];

		yield 'with canonical URI property' => [
			'settings' => [
				'formatterUrlProperty' => null,
				'canonicalUriProperty' => 'P321',
			],
			'expectedPropertyIdMap' => [
				PropertyInfoStore::KEY_CANONICAL_URI => new NumericPropertyId( 'P321' ),
			],
		];

		yield 'with both properties' => [
			'settings' => [
				'formatterUrlProperty' => 'P123',
				'canonicalUriProperty' => 'P321',
			],
			'expectedPropertyIdMap' => [
				PropertyInfoLookup::KEY_FORMATTER_URL => new NumericPropertyId( 'P123' ),
				PropertyInfoStore::KEY_CANONICAL_URI => new NumericPropertyId( 'P321' ),
			],
		];
	}

}
