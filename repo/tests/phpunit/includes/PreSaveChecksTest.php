<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\PreSaveChecks;
use Wikibase\Repo\WikibaseRepo;

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
		//TODO: use mock checks and/or inject settings.
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$limits = $settings->getSetting( 'multilang-limits' );
		$maxLength = $limits['length'];

		return array(
			array(
				'Wikibase\DataModel\Entity\Item',
				array(),
				array(),
				false
			),
			array(
				'Wikibase\DataModel\Entity\Item',
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
				'Wikibase\DataModel\Entity\Item',
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
				'Wikibase\DataModel\Entity\Property',
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
			array(
				'Wikibase\DataModel\Entity\Property',
				array(
					'label' => array( 'de' => 'xxx' ),
				),
				array(
					'label' => array( 'de' => 'P17' ),
				),
				array(
					'wikibase-error-label-no-entityid'
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
	public function testApplyPreSaveChecks( $class, $oldData, $newData, $errors ) {
		//TODO: mock the MultiLangConstraintDetector used by PreSaveChecks
		//TODO: mock the LabelDescriptionDuplicateDetector used by PreSaveChecks

		$termIndex = $this->getMock( 'Wikibase\TermIndex' );
		$idParser = new BasicEntityIdParser();
		$checks = new PreSaveChecks( $termIndex, $idParser );

		/* @var Entity $oldEntity */
		/* @var Entity $newEntity */
		$oldEntity = $class::newFromArray( $oldData );
		$newEntity = $class::newFromArray( $newData );
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
