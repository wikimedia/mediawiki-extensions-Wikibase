<?php

namespace Wikibase\Tests\Lib;

use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\PresetLabelLookupFactory;

/**
 * @covers Wikibase\Lib\Store\PresetLabelLookupFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseLibTest
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
class PresetLabelLookupFactoryTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider validGetLabelLookupProvider
	 */
	public function testValidGetLabelLookup( $presetForLangSpec, $requestLangSpec ) {
		$labelLookup = $this->getLabelLookup();

		$wikibaseLabelLookupFactory = new PresetLabelLookupFactory(
			$presetForLangSpec,
			$labelLookup
		);

		$returnValue = $wikibaseLabelLookupFactory->getLabelLookup( $requestLangSpec );

		$this->assertEquals( $returnValue , $labelLookup );
	}

	public function validGetLabelLookupProvider() {
		return array(
			array(
				'en',
				'en'
			),
			array(
				new LanguageFallbackChain( array( 'en', 'de' ) ),
				new LanguageFallbackChain( array( 'en', 'de' ) )
			)
		);
	}

	/**
	 * @dataProvider invalidGetLabelLookupProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidGetLabelLookup( $presetForLangSpec, $requestLangSpec ) {
		$labelLookup = $this->getLabelLookup();

		$wikibaseLabelLookupFactory = new PresetLabelLookupFactory(
			$presetForLangSpec,
			$labelLookup
		);

		$wikibaseLabelLookupFactory->getLabelLookup( $requestLangSpec );
	}

	public function invalidGetLabelLookupProvider() {
		return array(
			array(
				'en',
				'de'
			),
			array(
				new LanguageFallbackChain( array( 'en', 'de' ) ),
				new LanguageFallbackChain( array( 'de', 'en' ) )
			)
		);
	}

	private function getLabelLookup() {
		$labelLookup = $this->getMock( 'Wikibase\Lib\Store\LabelLookup' );

		return $labelLookup;
	}
}
