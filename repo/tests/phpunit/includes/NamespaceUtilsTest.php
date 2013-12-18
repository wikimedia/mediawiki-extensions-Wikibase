<?php

namespace Wikibase\Test;

use Wikibase\NamespaceUtils;

/**
 * @covers Wikibase\NamespaceUtils
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseUtils
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NamespaceUtilsTest extends \PHPUnit_Framework_TestCase {

	public function testGetEntityNamespaces() {
		$this->assertInternalType( 'array', NamespaceUtils::getEntityNamespaces() );
	}

	public function testGetEntityNamespace() {
		foreach ( NamespaceUtils::getEntityNamespaces() as $namespaceId ) {
			$this->assertTrue( NamespaceUtils::isEntityNamespace( $namespaceId ) );
		}

		$this->assertFalse( NamespaceUtils::isEntityNamespace( 720101010 ) );
	}

}
