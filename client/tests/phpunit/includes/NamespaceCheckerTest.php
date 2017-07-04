<?php

namespace Wikibase\Client\Tests;

use InvalidArgumentException;
use MWNamespace;
use Wikibase\Client\NamespaceChecker;

/**
 * @covers Wikibase\Client\NamespaceChecker
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
		return [
			[ [], [ NS_MAIN ] ],
			[ [ NS_USER_TALK ], [] ]
		];
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

		return [
			[ NS_USER_TALK, [], [], true ], // #0
			[ NS_USER_TALK, [], [ NS_MAIN ], false ], // #1
			[ NS_USER_TALK, [ NS_USER_TALK ], [], false ], // #2
			[ NS_USER_TALK, [ NS_CATEGORY ], [ NS_USER_TALK ], true ], // #3
			[ NS_CATEGORY, [ NS_USER_TALK ], [ NS_MAIN ], false ], // #4
			[ NS_CATEGORY, [ NS_USER_TALK ], [], true ], // #5
			[ NS_USER_TALK, [ NS_USER_TALK ], [ NS_USER_TALK ], false ] // #6
		];
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
		return [
			[ 'Item', [], [] ]
		];
	}

	public function wikibaseNamespacesProvider() {
		// Edge cases:
		// * empty "exclude" matches nothing
		// * empty "include" matches everything
		// * if neither include nor exclude are matched, the namespace is
		//   accepted if and only if the include array is empty.
		// * if the ns is in both, include and exclude, then it is excluded.

		$all = MWNamespace::getValidNamespaces();

		return [
			[ [], [], $all ], // #0
			[ [], [ NS_MAIN ], [ NS_MAIN ] ], // #1
			[ [ NS_USER_TALK ], [], array_diff( $all, [ NS_USER_TALK ] ) ], // #2
			[ [ NS_CATEGORY ], [ NS_USER_TALK ], [ NS_USER_TALK ] ], // #3
			[ [ NS_USER_TALK ], [ NS_USER_TALK ], [] ] // #4
		];
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
