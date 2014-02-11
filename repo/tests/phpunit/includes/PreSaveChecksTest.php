<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\PreSaveChecks;
use Wikibase\Settings;

/**
 * @covers Wikibase\PreSaveChecks
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseUtils
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PreSaveChecksTest extends \PHPUnit_Framework_TestCase {

	public function providePreSaveChecks() {
		$limits = Settings::get( 'multilang-limits' );
		$maxLength = $limits['length'];

		return array(
			array(
				array(),
				array(),
				false
			),
			array(
				array(
					'label' => array( 'de' => 'xxx' ),
				),
				array(
					'label' => array( 'de' => str_repeat( 'x', $maxLength + 1 ) ),
				),
				array(
					'wikibase-error-constraint-violation-label'
				)
			),
			array(
				array(
					'description' => array( 'de' => 'xxx' ),
				),
				array(
					'description' => array( 'de' => str_repeat( 'x', $maxLength + 1 ) ),
				),
				array(
					'wikibase-error-constraint-violation-description'
				)
			),
			array(
				array(
					'aliases' => array( 'de' => array( 'xxx' ) ),
				),
				array(
					'aliases' => array( 'de' => array( str_repeat( 'x', $maxLength + 1 ) ) ),
				),
				array(
					'wikibase-error-constraint-violation-aliases'
				)
			),
		);
	}

	/**
	 * @dataProvider providePreSaveChecks
	 *
	 * @param $oldData
	 * @param $newData
	 * @param $errors
	 */
	public function testApplyPreSaveChecks( $oldData, $newData, $errors ) {
		//TODO: mock the MultiLangConstraintDetector used by PreSaveChecks
		//TODO: mock the LabelDescriptionDuplicateDetector used by PreSaveChecks

		$termIndex = $this->getMock( 'Wikibase\TermIndex' );
		$checks = new PreSaveChecks( $termIndex );

		$oldEntity = Item::newFromArray( $oldData );
		$newEntity = Item::newFromArray( $newData );
		$diff = $oldEntity->getDiff( $newEntity );

		$status = $checks->applyPreSaveChecks( $newEntity, $diff );

		if ( !$errors ) {
			$this->assertTrue( $status->isOK(), 'No errors expected, got ' . $status->getWikiText() );
		} else {
			foreach ( $errors as $error ) {
				$this->assertTrue( $status->hasMessage( $error ), 'Expected error ' . $error );
			}
		}
	}

}
