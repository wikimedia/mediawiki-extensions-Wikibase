<?php

namespace Wikibase\Test;

use Status;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\PreSaveChecks;
use Wikibase\TermDuplicateDetector;
use Wikibase\Validators\TermValidatorFactory;
use Wikibase\Validators\ValidatorErrorLocalizer;

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

		return array(
			'empty' => array(
				'Wikibase\DataModel\Entity\Item',
				null,
				array(),
				array()
			),
			'unchanged bad data' => array(
				'Wikibase\DataModel\Entity\Item',
				array(
					'label' => array(
						'de' => str_repeat( 'x', 16 )
					),
				),
				array(
					'label' => array(
						'de' => str_repeat( 'x', 16 ),
						'en' => 'foo'
					),
				),
				array()
			),
			'duplicate label' => array(
				'Wikibase\DataModel\Entity\Property',
				array(
					'label' => array( 'de' => 'xxx' ),
				),
				array(
					'label' => array( 'de' => 'DUPE' ),
				),
				array(
					'wikibase-validator-label-conflict'
				)
			),
			'duplicate label/description' => array(
				'Wikibase\DataModel\Entity\Item',
				array(
					'label' => array( 'de' => 'xxx' ),
					'description' => array( 'de' => 'DUPE' ),
				),
				array(
					'label' => array( 'de' => 'DUPE' ),
					'description' => array( 'de' => 'DUPE' ),
				),
				array(
					'wikibase-validator-label-conflict'
				)
			),
			'duplicate label/description 2' => array(
				'Wikibase\DataModel\Entity\Item',
				array(
					'label' => array( 'de' => 'DUPE' ),
					'description' => array( 'de' => 'xxx' ),
				),
				array(
					'label' => array( 'de' => 'DUPE' ),
					'description' => array( 'de' => 'DUPE' ),
				),
				array(
					'wikibase-validator-label-conflict'
				)
			),
			'duplicate label is ok for items' => array(
				'Wikibase\DataModel\Entity\Item',
				array(
					'label' => array( 'de' => 'xxx' ),
				),
				array(
					'label' => array( 'de' => 'DUPE' ),
				),
				array()
			),
			'duplicate label, but no change' => array(
				'Wikibase\DataModel\Entity\Property',
				array(
					'label' => array( 'de' => 'DUPE' ),
				),
				array(
					'label' => array( 'de' => 'DUPE' ),
				),
				array()
			),
		);
	}

	public function detectLabelConflictsForEntity( Entity $entity ) {

		foreach ( $entity->getLabels() as $label ) {
			if ( $label === 'DUPE' ) {
				return Result::newError( array(
					Error::newError( 'Foo!', 'label', 'label-conflict' )
				) );
			}
		}

		return Result::newSuccess();
	}

	public function detectLabelDescriptionConflictsForEntity( Entity $entity ) {

		foreach ( $entity->getLabels() as $lang => $label ) {
			if ( $label === 'DUPE' ) {
				$description = $entity->getDescription( $lang );

				if ( $description === 'DUPE' ) {
					return Result::newError( array(
						Error::newError( 'Foo!', 'label', 'label-conflict' )
					) );
				}
			}
		}

		return Result::newSuccess();
	}

	/**
	 * @return TermDuplicateDetector
	 */
	private function getMockTermDuplicateDetector() {
		$mock =  $this->getMockBuilder( 'Wikibase\TermDuplicateDetector' )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->any() )
			->method( 'detectLabelConflictsForEntity' )
			->will( $this->returnCallback( array( $this, 'detectLabelConflictsForEntity' ) ) );

		$mock->expects( $this->any() )
			->method( 'detectLabelDescriptionConflictsForEntity' )
			->will( $this->returnCallback( array( $this, 'detectLabelDescriptionConflictsForEntity' ) ) );

		return $mock;
	}

	/**
	 * @dataProvider providePreSaveChecks
	 *
	 * @param array $oldData
	 * @param array $newData
	 * @param string[] $expectedErrors
	 */
	public function testApplyPreSaveChecks( $class, $oldData, $newData, $expectedErrors ) {
		//TODO: check dupe detection against mock dupe detector!
		$dupeDetector = $this->getMockTermDuplicateDetector();

		$idParser = new BasicEntityIdParser();
		$maxLength = 12;
		$languages = array( 'en', 'de' );

		$validatorFactory = new TermValidatorFactory( $maxLength, $languages, $idParser, $dupeDetector );
		$errorLocalizer = new ValidatorErrorLocalizer();

		$checks = new PreSaveChecks(
			$validatorFactory,
			$errorLocalizer
		);

		/* @var Entity $oldEntity */
		/* @var Entity $newEntity */
		$oldEntity = $oldData == null ? null : $class::newFromArray( $oldData );
		$newEntity = $class::newFromArray( $newData );
		$diff = $oldEntity == null ? null : $oldEntity->getDiff( $newEntity );

		$status = $checks->applyPreSaveChecks( $newEntity, $diff );

		$this->assertEquals( empty( $expectedErrors ), $status->isOK(), 'isOK()' );

		if ( $expectedErrors ) {
			$this->assertErrors( $expectedErrors, $status );
		}
	}

	private function assertErrors( array $expectedErrors, Status $status ) {
		$statusErrors = array();
		foreach ( $status->getErrorsArray() as $row ) {
			$key = array_shift( $row );
			$statusErrors[$key] = $row;
		}

		foreach ( $expectedErrors as $error ) {
			$this->assertArrayHasKey( $error, $statusErrors, 'Expected error ' . $error );
		}
	}

}
