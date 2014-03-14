<?php

namespace Wikibase\Test;

use Title;
use Wikibase\NamespaceUtils;

/**
 * @covers Wikibase\NamespaceUtils
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

	/**
	 * @dataProvider isTitleInEntityNamespaceProvider
	 */
	public function testIsTitleInEntityNamespace( $expected, array $namespaces, Title $title ) {
		$namespaceUtils = new NamespaceUtils( $namespaces );

		$this->assertEquals( $expected, $namespaceUtils->isTitleInEntityNamespace( $title ) );
	}

	public function isTitleInEntityNamespaceProvider() {
		$namespaces = array(
			'wikibase-item' => 0,
			'wikibase-property' => 102,
		);

		$namespaces2 = array(
			'wikibase-item' => 120,
			'wikibase-property' => 122
		);

		return array(
			array( true, $namespaces, Title::makeTitle( 0, 'Cat' ) ),
			array( false, $namespaces, Title::makeTitle( 2, 'Cat' ) ),
			array( true, $namespaces, Title::makeTitle( 102, 'Cat' ) ),
			array( false, $namespaces2, Title::makeTitle( 0, 'Cat' ) ),
			array( false, $namespaces2, Title::makeTitle( 2, 'Cat' ) ),
			array( true, $namespaces2, Title::makeTitle( 120, 'Cat' ) ),
			array( true, $namespaces2, Title::makeTitle( 122, 'Cat' ) )
		);
	}

}
