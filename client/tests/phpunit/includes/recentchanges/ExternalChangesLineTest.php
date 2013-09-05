<?php

namespace Wikibase\Client\Test;
use Wikibase\ExternalChangesLine;

/**
 * @covers Wikibase\ExternalChangesLine
 *
 * @since 0.1
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseChange
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ExternalChangesLineTest extends \PHPUnit_Framework_TestCase {

	/**
	 */
	public function testChangesLine( /* $cl, $rc */ ) {
		$this->markTestIncomplete( 'Test me!' );
	}

	/**
	 * @dataProvider parseCommentProvider
	 */
	public function testParseComment( $entityData, $expected, $warnings = 'fail' ) {
		if ( $warnings === 'suppress' ) {
			wfSuppressWarnings();
		}

		$actual = ExternalChangesLine::parseComment( $entityData );
		$this->assertEquals( $expected, $actual );

		if ( $warnings === 'suppress' ) {
			wfRestoreWarnings();
		}
	}

	public static function parseCommentProvider() {
		return array(
			'plain string' => array(
				array(
					'type' => 'wikibase-item~change',
					'comment' => 'wikibase-comment-update',
				),
				wfMessage( 'wikibase-comment-update' )->text()
			),
			'wikibase-item~add' => array(
				array(
					'type' => 'wikibase-item~add',
					'comment' => array( 'message' => 'wikibase-comment-update' ),
					// comment will be ignored, but must be an array
				),
				wfMessage( 'wikibase-comment-linked' )->text()
			),
			'message array' => array(
				array(
					'type' => 'wikibase-item~restore',
					'comment' => array( 'message' => 'wikibase-comment-restore' ),
				),
				wfMessage( 'wikibase-comment-restore' )->text()
			),
			'broken message array' => array(
				array(
					'type' => 'wikibase-item~restore',
					'comment' => array( 'foo' => 'wikibase-comment-restore' ),
				),
				wfMessage( 'wikibase-comment-update' )->text(),
				'suppress'
			),
		);
	}

	/**
	 */
	public function testGetComment( /* $entityData */ ) {
		$this->markTestIncomplete( 'Test me!' );
	}

	/**
	 */
	public function testGetTimestamp( /* $cl, $rc */ ) {
		$this->markTestIncomplete( 'Test me!' );
	}

	/**
	 */
	public function testUserLinks( /* $cl, $userName */ ) {
		$this->markTestIncomplete( 'Test me!' );
	}

}
