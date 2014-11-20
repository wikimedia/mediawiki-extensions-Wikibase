<?php

namespace Wikibase\Tests\Lib;

use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\WikibaseLabelLookupFactory;

/**
 * @covers Wikibase\Lib\Store\WikibaseLabelLookupFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseLibTest
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
class WikibaseLabelLookupFactoryTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider getLabelLookupProvider
	 */
	public function testGetLabelLookup( $languageSpec, $class ) {
		$wikibaseLabelLookupFactory = new WikibaseLabelLookupFactory(
			$this->getTermLookup()
		);

		$labelLookup = $wikibaseLabelLookupFactory->getLabelLookup( $languageSpec );

		$this->assertInstanceOf( $class, $labelLookup );
	}

	public function getLabelLookupProvider() {
		return array(
			array(
				'en',
				'Wikibase\Lib\Store\LanguageLabelLookup'
			),
			array(
				new LanguageFallbackChain( array( 'en', 'de' ) ),
				'Wikibase\Lib\Store\LanguageFallbackLabelLookup'
			)
		);
	}

	private function getTermLookup() {
		$termLookup = $this->getMock( 'Wikibase\Lib\Store\TermLookup' );

		return $termLookup;
	}
}
