<?php

namespace Wikibase\Bootstrap\Tests;

use MWException;
use OutOfBoundsException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\WikibaseSettings;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers Wikibase\Bootstrap/WikibaseSettings
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 */
class WikibaseSettingsTest extends PHPUnit_Framework_TestCase {

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
