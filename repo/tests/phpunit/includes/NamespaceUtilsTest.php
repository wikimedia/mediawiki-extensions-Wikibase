<?php

namespace Wikibase\Test;
use Wikibase\NamespaceUtils;

/**
 * Tests for the Wikibase\Utils class.
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseUtils
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NamespaceUtilsTest extends \MediaWikiTestCase {

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
