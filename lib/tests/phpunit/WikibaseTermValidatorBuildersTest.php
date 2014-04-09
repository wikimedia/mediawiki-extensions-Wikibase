<?php

namespace Wikibase\Test;

use DataValues\GlobeCoordinateValue;
use DataValues\LatLongValue;
use DataValues\NumberValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\WikibaseTermValidatorBuilders;
use Wikibase\SettingsArray;

/**
 * @covers WikibaseTermValidatorBuilders
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class WikibaseTermValidatorBuildersTest extends \PHPUnit_Framework_TestCase {

	protected function newBuilders( $limits, $languages ) {
		if ( !isset( $limits['length'] ) ) {
			$limits['length'] = 200;
		}

		$settings = new SettingsArray( array(
			'multilang-limits' => $limits
		) );

		$builders = new WikibaseTermValidatorBuilders( $settings, $languages );
		return $builders;
	}

	public function testBuildLanguageValidator() {
		$builders = $this->newBuilders( array(), array( 'ja', 'ru' ) );

		$validator = $builders->buildLanguageValidator();

		$this->assertInstanceOf( 'ValueValidators\ValueValidator', $validator );

		$this->assertTrue( $validator->validate( 'ja' )->isValid() );
		$this->assertFalse( $validator->validate( 'xx' )->isValid() );
	}

	public function testBuildLabelValidator() {
		$builders = $this->newBuilders( array( 'length' => 8 ), array( 'en' ) );

		$validator = $builders->buildLabelValidator();

		$this->assertInstanceOf( 'ValueValidators\ValueValidator', $validator );

		$this->assertTrue( $validator->validate( 'foo' )->isValid() );
		$this->assertFalse( $validator->validate( '' )->isValid() );
		$this->assertFalse( $validator->validate( '0123456789' )->isValid() );
	}

	public function testBuildDescriptionValidator() {
		$builders = $this->newBuilders( array( 'length' => 8 ), array( 'en' ) );

		$validator = $builders->buildDescriptionValidator();

		$this->assertInstanceOf( 'ValueValidators\ValueValidator', $validator );

		$this->assertTrue( $validator->validate( 'foo' )->isValid() );
		$this->assertFalse( $validator->validate( '' )->isValid() );
		$this->assertFalse( $validator->validate( '0123456789' )->isValid() );
	}

	public function testBuildAliasValidator() {
		$builders = $this->newBuilders( array( 'length' => 8 ), array( 'en' ) );

		$validator = $builders->buildAliasValidator();

		$this->assertInstanceOf( 'ValueValidators\ValueValidator', $validator );

		$this->assertTrue( $validator->validate( 'foo' )->isValid() );
		$this->assertFalse( $validator->validate( '' )->isValid() );
		$this->assertFalse( $validator->validate( '0123456789' )->isValid() );
	}

}
