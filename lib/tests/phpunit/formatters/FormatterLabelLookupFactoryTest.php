<?php

namespace Wikibase\Lib\Test;

use Language;
use OutOfBoundsException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Lib\FormatterLabelLookupFactory;
use Wikibase\Lib\Store\LanguageLabelLookup;
use Wikibase\Lib\Store\TermLookup;

/**
 * @covers Wikibase\Lib\FormatterLabelLookupFactory
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class FormatterLabelLookupFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideGetLabelLookup
	 */
	public function testGetLabelLookup( TermLookup $termLookup, FormatterOptions $options, $expectedLabel ) {
		$factory = new FormatterLabelLookupFactory( $termLookup );
		$labelLookup = $factory->getLabelLookup( $options );

		$this->assertInstanceOf( 'Wikibase\Lib\Store\LabelLookup', $labelLookup );

		$term = $labelLookup->getLabel( new ItemId( 'Q1' ) );
		$this->assertEquals( $expectedLabel, $term->getText() );
	}

	public function provideGetLabelLookup() {
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

		$labelLookup = new LanguageLabelLookup( $termLookup, 'de' );

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
			'language and fallback chain and LabelLookup' => array(
				$termLookup,
				new FormatterOptions( array(
					ValueFormatter::OPT_LANG => 'fr',
					'languages' => $frChain,
					'LabelLookup' => $labelLookup
				) ),
				'Kätzchen'
			),
		);
	}

	/**
	 * @dataProvider provideGetLabelLookup_failure
	 */
	public function testGetLabelLookup_failure( FormatterOptions $options ) {
		$termLookup = $this->getMock( 'Wikibase\Lib\Store\TermLookup' );
		$factory = new FormatterLabelLookupFactory( $termLookup );

		$this->setExpectedException( 'InvalidArgumentException' );
		$factory->getLabelLookup( $options );
	}

	public function provideGetLabelLookup_failure() {
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
			'bad LabelLookup' => array(
				new FormatterOptions( array(
					'LabelLookup' => new LanguageFallbackChain( array() )
				) ),
			),
			'no options' => array(
				new FormatterOptions( array( ) ),
			),
		);
	}

}
