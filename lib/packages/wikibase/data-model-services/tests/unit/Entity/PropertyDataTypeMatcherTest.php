<?php

namespace Wikibase\DataModel\Services\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\InProcessCachingDataTypeLookup;

/**
 * @covers \Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyDataTypeMatcherTest extends TestCase {

	public function testIsMatchingDataType() {
		$propertyDataTypeMatcher = $this->getPropertyDataTypeMatcher();

		$isMatching = $propertyDataTypeMatcher->isMatchingDataType(
			new NumericPropertyId( 'P2' ),
			'string'
		);

		$this->assertTrue( $isMatching, 'P2 matches commonsMedia data type.' );
	}

	public function testIsMatchingDataTypeNotmatch() {
		$propertyDataTypeMatcher = $this->getPropertyDataTypeMatcher();

		$isMatching = $propertyDataTypeMatcher->isMatchingDataType(
			new NumericPropertyId( 'P2' ),
			'url'
		);

		$this->assertFalse( $isMatching, 'P2 does not match url data type.' );
	}

	public function testIsMatchingDataTypeUnknownPropertyId() {
		$propertyDataTypeMatcher = $this->getPropertyDataTypeMatcher();

		$isMatching = $propertyDataTypeMatcher->isMatchingDataType(
			new NumericPropertyId( 'P9000' ),
			'string'
		);

		$this->assertFalse( $isMatching, 'Lookup of unknown property returns false.' );
	}

	private function getPropertyDataTypeMatcher() {
		$inMemoryDataTypeLookup = new InMemoryDataTypeLookup();
		$inMemoryDataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'P2' ), 'string' );

		$propertyDataTypeLookup = new InProcessCachingDataTypeLookup( $inMemoryDataTypeLookup );

		return new PropertyDataTypeMatcher( $propertyDataTypeLookup );
	}

}
