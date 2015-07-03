<?php

namespace Wikibase\Lib\Test;

use Language;
use OutOfBoundsException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\LanguageLabelDescriptionLookup;
use Wikibase\Lib\Store\TermLookup;

/**
 * @covers Wikibase\Lib\FormatterLabelDescriptionLookupFactory
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class FormatterLabelDescriptionLookupFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideGetLabelDescriptionLookup
	 */
	public function testGetLabelDescriptionLookup( TermLookup $termLookup, FormatterOptions $options, $expectedLabel ) {
		$factory = new FormatterLabelDescriptionLookupFactory( $termLookup );
		$labelDescriptionLookup = $factory->getLabelDescriptionLookup( $options );

		$this->assertInstanceOf( 'Wikibase\Lib\Store\LabelDescriptionLookup', $labelDescriptionLookup );

		$term = $labelDescriptionLookup->getLabel( new ItemId( 'Q1' ) );
		$this->assertEquals( $expectedLabel, $term->getText() );
	}

	public function provideGetLabelDescriptionLookup() {
		$termLookup = $this->getMock( 'Wikibase\Lib\Store\TermLookup' );

		$termLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function ( $item, $language ) {
				if ( $language === 'de' ) {
					return 'Kätzchen';
				}

				throw new OutOfBoundsException( 'no bananas' );
			} ) );

		$termLookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnValue( array( 'de' => 'Kätzchen' ) ) );

		$labelDescriptionLookup = new LanguageLabelDescriptionLookup( $termLookup, 'de' );

		$deChChain = new LanguageFallbackChain( array(
			LanguageWithConversion::factory( 'de-ch' ),
			LanguageWithConversion::factory( 'de' ),
		) );

		$frChain = new LanguageFallbackChain( array(
			LanguageWithConversion::factory( 'fr' ),
		) );

		return array(
			'language' => array(
				$termLookup,
				new FormatterOptions( array(
					ValueFormatter::OPT_LANG => 'de',
				) ),
				'Kätzchen'
			),
			'language and fallback chain' => array(
				$termLookup,
				new FormatterOptions( array(
					ValueFormatter::OPT_LANG => 'fr',
					'languages' => $deChChain,
				) ),
				'Kätzchen'
			),
			'language and fallback chain and LabelDescriptionLookup' => array(
				$termLookup,
				new FormatterOptions( array(
					ValueFormatter::OPT_LANG => 'fr',
					'languages' => $frChain,
					'LabelDescriptionLookup' => $labelDescriptionLookup
				) ),
				'Kätzchen'
			),
		);
	}

	/**
	 * @dataProvider provideGetLabelDescriptionLookup_failure
	 */
	public function testGetLabelDescriptionLookup_failure( FormatterOptions $options ) {
		$termLookup = $this->getMock( 'Wikibase\Lib\Store\TermLookup' );
		$factory = new FormatterLabelDescriptionLookupFactory( $termLookup );

		$this->setExpectedException( 'InvalidArgumentException' );
		$factory->getLabelDescriptionLookup( $options );
	}

	public function provideGetLabelDescriptionLookup_failure() {
		return array(
			'bad language' => array(
				new FormatterOptions( array(
					ValueFormatter::OPT_LANG => Language::factory( 'en' ),
				) ),
			),
			'bad fallback chain' => array(
				new FormatterOptions( array(
					'languages' => array( 'x', 'y', 'z' ),
				) ),
			),
			'bad LabelDescriptionLookup' => array(
				new FormatterOptions( array(
					'LabelDescriptionLookup' => new LanguageFallbackChain( array() )
				) ),
			),
			'no options' => array(
				new FormatterOptions( array() ),
			),
		);
	}

}
