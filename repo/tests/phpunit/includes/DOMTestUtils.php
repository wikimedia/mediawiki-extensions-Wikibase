<?php

namespace Wikibase\Test;

use DOMDocument;

/**
 * Base class for DOM testing.
 *
 * @licence GNU GPL v2+
 */
class DOMTestUtils {

	public static function assertTagSimple( $testCase, array $matcher, $actual, $message = '', $isHtml = true ) {
		$testCase->assertEmpty( array_diff(
			array_keys( $matcher ),
			array( 'tag', 'attributes', 'content' )
		) );
		$doc = new DOMDocument();
		if ( $isHtml ) {
			$doc->loadHTML( $actual );
		} else {
			$doc->loadXML( $actual );
		}
		$found = false;
		$elements = $doc->getElementsByTagName( $matcher['tag'] );
		foreach ( $elements as $node ) {
			$valid = true;
			if ( isset( $matcher['attributes'] ) ) {
				foreach ( $matcher['attributes'] as $name => $value ) {
					if ( $name === 'class' ) {
						$expected = preg_split( '/\s+/', $value, null, PREG_SPLIT_NO_EMPTY );
						$got = preg_split( '/\s+/', $node->getAttribute( $name ), null, PREG_SPLIT_NO_EMPTY );
						// make sure each class given is in the actual node
						if ( array_diff( $expected, $got ) ) {
							$valid = false;
							break;
						}
					} elseif ( $node->getAttribute( $name ) !== $value ) {
						$valid = false;
						break;
					}
				}
			}
			if ( isset( $matcher['content'] ) ) {
				if ( $matcher['content'] === '' ) {
					if ( $node->nodeValue !== '' ) {
						$valid = false;
					}
				} elseif ( strstr( $node->nodeValue, $matcher['content'] ) === false ) {
					$valid = false;
				}
			}
			if ( $valid ) {
				$found = true;
				break;
			}
		}
		$testCase->assertTrue( $found, $message );
	}

}
