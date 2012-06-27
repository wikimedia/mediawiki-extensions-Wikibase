<?php

namespace Wikibase\Test;
use Wikibase\Sites as Sites;

/**
 * Tests for the Wikibase\MediaWikiSite class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group Sites
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MediaWikiSiteTest extends \MediaWikiTestCase {

	public function testFactoryConstruction() {
		$this->assertInstanceOf( 'Wikibase\MediaWikiSite', Sites::newSite( array( 'type' => SITE_TYPE_MEDIAWIKI ) ) );
		$this->assertInstanceOf( 'Wikibase\Site', Sites::newSite( array( 'type' => SITE_TYPE_MEDIAWIKI ) ) );
	}

}
