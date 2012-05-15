<?php
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
class WikibaseUtilsTests extends MediaWikiTestCase {

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerGetLanguageCodes
	 */
	public function testGetLanguageCodes( $lang ) {
    	$result = WikibaseUtils::getLanguageCodes();
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
    	$result = WikibaseSites::singleton()->getIdentifiers();
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
    	$actual = WikibaseUtils::getSiteUrl( $siteId, $pageTitle );
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
    		array( 'de', 'foo1', 'https://de.wikipedia.org' ),
    		array( 'en', 'foo2', 'https://en.wikipedia.org' ),
    		array( 'no', 'foo3', 'https://no.wikipedia.org' ),
    		array( 'nn', 'foo4', 'https://nn.wikipedia.org' ),
    	);
    }

}