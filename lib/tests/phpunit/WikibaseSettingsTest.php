<?php

namespace Wikibase\Lib\Tests;

use MWException;
use PHPUnit4And6Compat;
use Wikibase\WikibaseSettings;

/**
 * @covers Wikibase\WikibaseSettings
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 */
class WikibaseSettingsTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testGetRepoSettings() {
		if ( WikibaseSettings::isRepoEnabled() ) {
			$this->assertNotNull( WikibaseSettings::getRepoSettings() );
		} else {
			$this->setExpectedException( MWException::class );
			WikibaseSettings::getRepoSettings();
		}
	}

	public function testGetClientSettings() {
		if ( WikibaseSettings::isClientEnabled() ) {
			$this->assertNotNull( WikibaseSettings::getClientSettings() );
		} else {
			$this->setExpectedException( MWException::class );
			WikibaseSettings::getClientSettings();
		}
	}

}
