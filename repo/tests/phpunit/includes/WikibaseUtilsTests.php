<?php

namespace Wikibase\Test;
use Wikibase\Utils as Utils;
use Wikibase\Sites as Sites;

/**
 * Tests for the WikibaseUtils class.
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
 */
class UtilsTests extends \MediaWikiTestCase {

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

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerGetSiteIdentifiers
	 */
	public function testGetSiteIdentifiers( $site ) {
    	$result = Sites::singleton()->getIdentifiers();
    	$this->assertContains(
    		$site,
    		$result,
    		"The site identifier with code {$site} could not be found in the returned result"
    	);
    }
    
	public function providerGetSiteIdentifiers() {
    	return array(
    		array( 'de' ),
    		array( 'en' ),
    		array( 'no' ),
    		array( 'nn' ),
    	);
    }

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerGetSiteUrl
	 */
	public function testGetSiteUrl( $siteId, $pageTitle, $expected ) {
    	$actual = Sites::singleton()->getUrl( $siteId, $pageTitle );
    	$this->assertEquals(
    		$expected,
    		$actual,
    		"The URL for site with code {$siteId} was not evaluated to the expected form"
    	);
    	if (!substr_count($actual, $pageTitle)) {
    		$this->markTestincomplete("There are no traces of the title '{$pageTitle}' within the URL '{$actual}'");
    	}
	}
	public function providerGetSiteUrl() {
    	return array(
    		array( 'de', 'foo1', 'http://de.wikipedia.org/wiki/foo1' ),
    		array( 'en', 'foo2', 'http://en.wikipedia.org/wiki/foo2' ),
    		array( 'no', 'foo3', 'http://no.wikipedia.org/wiki/foo3' ),
    		array( 'nn', 'foo4', 'http://nn.wikipedia.org/wiki/foo4' ),
    	);
    }

}