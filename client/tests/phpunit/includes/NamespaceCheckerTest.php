<?php

namespace Wikibase\Client\Tests;

use InvalidArgumentException;
use MWNamespace;
use Wikibase\NamespaceChecker;

/**
 * @covers Wikibase\NamespaceChecker
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class NamespaceCheckerTest extends \MediaWikiTestCase {

	public function constructorProvider() {
		return array(
			array( [], array( NS_MAIN ) ),
			array( array( NS_USER_TALK ), [] )
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( array $excluded, array $enabled ) {
		$namespaceChecker = new NamespaceChecker( $excluded, $enabled );
		$this->assertEquals( $enabled, $namespaceChecker->getEnabledNamespaces() );
		$this->assertEquals( $excluded, $namespaceChecker->getExcludedNamespaces() );
	}

	public function enabledProvider() {
		// Edge cases:
		// * empty "exclude" matches nothing
		// * empty "include" matches everything
		// * if neither include nor exclude are matched, the namespace is
		//   accepted if and only if the include array is empty.
		// * if the ns is in both, include and exclude, then it is excluded.

		return array(
			array( NS_USER_TALK, [], [], true ), // #0
			array( NS_USER_TALK, [], array( NS_MAIN ), false ), // #1
			array( NS_USER_TALK, array( NS_USER_TALK ), [], false ), // #2
			array( NS_USER_TALK, array( NS_CATEGORY ), array( NS_USER_TALK ), true ), // #3
			array( NS_CATEGORY, array( NS_USER_TALK ), array( NS_MAIN ), false ), // #4
			array( NS_CATEGORY, array( NS_USER_TALK ), [], true ), // #5
			array( NS_USER_TALK, array( NS_USER_TALK ), array( NS_USER_TALK ), false ) // #6
		);
	}

	/**
	 * @dataProvider enabledProvider
	 */
	public function testIsWikibaseEnabled( $namespace, $excluded, $enabled, $expected ) {
		$namespaceChecker = new NamespaceChecker( $excluded, $enabled );
		$result = $namespaceChecker->isWikibaseEnabled( $namespace );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @dataProvider enabledInvalidProvider
	 */
	public function testIsWikibaseEnabledInvalid( $namespace, $excluded, $enabled ) {
		$namespaceChecker = new NamespaceChecker( $excluded, $enabled );
		$this->setExpectedException( InvalidArgumentException::class );
		$namespaceChecker->isWikibaseEnabled( $namespace );
	}

	public function enabledInvalidProvider() {
		return array(
			array( 'Item', [], [] )
		);
	}

	public function wikibaseNamespacesProvider() {
		// Edge cases:
		// * empty "exclude" matches nothing
		// * empty "include" matches everything
		// * if neither include nor exclude are matched, the namespace is
		//   accepted if and only if the include array is empty.
		// * if the ns is in both, include and exclude, then it is excluded.

		$all = MWNamespace::getValidNamespaces();

		return array(
			array( [], [], $all ), // #0
			array( [], array( NS_MAIN ), array( NS_MAIN ) ), // #1
			array( array( NS_USER_TALK ), [], array_diff( $all, array( NS_USER_TALK ) ) ), // #2
			array( array( NS_CATEGORY ), array( NS_USER_TALK ), array( NS_USER_TALK ) ), // #3
			array( array( NS_USER_TALK ), array( NS_USER_TALK ), [] ) // #4
		);
	}

	/**
	 * @dataProvider wikibaseNamespacesProvider
	 */
	public function testGetWikibaseNamespaces( $excluded, $enabled, $expected ) {
		$namespaceChecker = new NamespaceChecker( $excluded, $enabled );
		$result = $namespaceChecker->getWikibaseNamespaces();
		$this->assertArrayEquals( $expected, $result );
	}

}
