<?php

namespace Wikibase\Test;
use Wikibase\Utils as Utils;
use Wikibase\Sites as Sites;

/**
 * Tests for the Wikibase\Utils class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseUtils
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher
 */
class UtilsTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerGetLanguageCodes
	 */
	public function testGetLanguageCodes( $lang ) {
    	$result = Utils::getLanguageCodes();
    	$this->assertContains(
    		$lang,
    		$result,
    		"The language code {$lang} could not be found in the returned result"
    	);
    }

    public function providerGetLanguageCodes() {
    	return array(
    		array( 'de' ),
    		array( 'en' ),
    		array( 'no' ),
    		array( 'nn' ),
    	);
    }

}