<?php

namespace Wikibase\Test;

use Status;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\PreSaveChecks;
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
			'not a language' => array(
				'Wikibase\DataModel\Entity\Item',
				null,
				array(
					'label' => array( 'narf' => 'xyz' ),
				),
				array(
					'wikibase-validator-not-a-language'
				)
			),
			'label too long' => array(
				'Wikibase\DataModel\Entity\Item',
				array(
					'label' => array( 'de' => 'xxx' ),
				),
				array(
					'label' => array( 'de' => str_repeat( 'x', 16 ) ),
				),
				array(
					'wikibase-validator-too-long'
				)
			),
			'description too long' => array(
				'Wikibase\DataModel\Entity\Item',
				array(
					'description' => array( 'de' => 'xxx' ),
				),
				array(
					'description' => array( 'de' => str_repeat( 'x', 16 ) ),
				),
				array(
					'wikibase-validator-too-long'
				)
			),
			'alias too long' => array(
				'Wikibase\DataModel\Entity\Property',
				null,
				array(
					'aliases' => array( 'de' => array( str_repeat( 'x', 16 ) ) ),
				),
				array(
					'wikibase-validator-too-long'
				)
			),
			'alias empty' => array(
				'Wikibase\DataModel\Entity\Property',
				array(
					'aliases' => array( 'de' => array( 'xxx' ) ),
				),
				array(
					'aliases' => array( 'de' => array( '' ) ),
				),
				array(
					'wikibase-validator-too-short'
				)
			),
			'lable is proeprty id' => array(
				'Wikibase\DataModel\Entity\Property',
				array(
					'label' => array( 'de' => 'xxx' ),
				),
				array(
					'label' => array( 'de' => 'P17' ),
				),
				array(
					'wikibase-validator-label-no-entityid'
				)
			),
			//TODO: check for dupes
		);
	}

	private function getMockTermDuplicateDetector() {
		$mock =  $this->getMockBuilder( 'Wikibase\TermDuplicateDetector' )
			->disableOriginalConstructor()
			->getMock();

		$good = Result::newSuccess();

		$mock->expects( $this->any() )
			->method( 'detectLabelConflictsForEntity' )
			->will( $this->returnValue( $good ) );

		$mock->expects( $this->any() )
			->method( 'detectLabelDescriptionConflictsForEntity' )
			->will( $this->returnValue( $good ) );

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

		$validatorFactory = new TermValidatorFactory( $maxLength, $languages, $idParser );
		$errorLocalizer = new ValidatorErrorLocalizer();

		$checks = new PreSaveChecks(
			$dupeDetector,
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
