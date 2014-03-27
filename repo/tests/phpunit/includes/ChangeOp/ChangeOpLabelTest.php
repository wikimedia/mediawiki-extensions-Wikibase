<?php

namespace Wikibase\Test;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Summary;
use Wikibase\ChangeOp\ChangeOpLabel;
use InvalidArgumentException;
use Wikibase\Validators\TermChangeValidationHelper;

/**
 * @covers Wikibase\ChangeOp\ChangeOpLabel
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpLabelTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Returns a mock validator. The term and the language "INVALID" is considered to be
	 * invalid, the combination of the label "DUPE" and the description "DUPE" is considered
	 * a duplicate.
	 *
	 * @return TermChangeValidationHelper
	 */
	private function getMockTermChangeValidationHelper() {
		$mock = $this->getMockBuilder( 'Wikibase\Validators\TermChangeValidationHelper' )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->any() )
			->method( 'validateLanguage' )
			->will( $this->returnCallback( function( $lang ) {
				if ( $lang == 'INVALID' ) {
					$error = Error::newError( 'Invalid', '', 'test-invalid' );
					throw new ChangeOpValidationException( Result::newError( array( $error ) ) );
				}
			} ) );

		$mock->expects( $this->any() )
			->method( 'validateLabel' )
			->will( $this->returnCallback( function( $lang, $text ) {
				if ( $text === 'INVALID' ) {
					$error = Error::newError( 'Invalid', '', 'test-invalid' );
					throw new ChangeOpValidationException( Result::newError( array( $error ) ) );
				}
			} ) );

		$mock->expects( $this->any() )
			->method( 'validateUniqueness' )
			->will( $this->returnCallback( function( $entityId, $lang, $label, $description ) {
				if ( $label === 'DUPE' && $description === 'DUPE' ) {
					$error = Error::newError( 'Dupe', '', 'test-dupe' );
					throw new ChangeOpValidationException( Result::newError( array( $error ) ) );
				}
			} ) );

		return $mock;
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct() {
		new ChangeOpLabel( 42, 'myNew', $this->getMockTermChangeValidationHelper() );
	}

	public function changeOpLabelProvider() {
		// "INVALID" is invalid, "DUPE" is a dupe
		$validation = $this->getMockTermChangeValidationHelper();

		$args = array();
		$args['update'] = array ( new ChangeOpLabel( 'en', 'myNew', $validation ), 'myNew' );
		$args['set to null'] = array ( new ChangeOpLabel( 'en', null, $validation ), '' );
		$args['proto-dupe'] = array ( new ChangeOpLabel( 'en', 'DUPE', $validation ), 'DUPE' );

		return $args;
	}

	/**
	 * @dataProvider changeOpLabelProvider
	 *
	 * @param ChangeOpLabel $changeOpLabel
	 * @param string $expectedLabel
	 */
	public function testApply( ChangeOp $changeOpLabel, $expectedLabel ) {
		$entity = $this->provideNewEntity();
		$entity->setLabel( 'en', 'INVALID' );
		$entity->setDescription( 'fr', 'DUPE' );

		$changeOpLabel->apply( $entity );

		$this->assertEquals( $expectedLabel, $entity->getLabel( 'en' ) );
	}

	public function invalidChangeOpLabelProvider() {
		// "INVALID" is invalid, "DUPE" is a dupe
		$validation = $this->getMockTermChangeValidationHelper();

		$args = array();
		$args['invalid label'] = array ( new ChangeOpLabel( 'fr', 'INVALID', $validation ) );
		$args['invalid language'] = array ( new ChangeOpLabel( 'INVALID', 'valid', $validation ) );
		$args['dupe'] = array ( new ChangeOpLabel( 'fr', 'DUPE', $validation ) );

		return $args;
	}

	/**
	 * @dataProvider invalidChangeOpLabelProvider
	 *
	 * @param ChangeOp $changeOpLabel
	 */
	public function testApplyInvalid( ChangeOp $changeOpLabel ) {
		$entity = $this->provideNewEntity();
		$entity->setDescription( 'fr', 'DUPE' );

		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpException' );
		$changeOpLabel->apply( $entity );
	}

	/**
	 * @return Entity
	 */
	protected function provideNewEntity() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q23' ) );
		return $item;
	}

	public function changeOpSummaryProvider() {
		$validation = $this->getMockTermChangeValidationHelper();

		$args = array();

		$entity = $this->provideNewEntity();
		$entity->setLabel( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpLabel( 'de', 'Zusammenfassung', $validation ), 'set', 'de' );

		$entity = $this->provideNewEntity();
		$entity->setLabel( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpLabel( 'de', null, $validation ), 'remove', 'de' );

		$entity = $this->provideNewEntity();
		$entity->removeLabel( 'de' );
		$args[] = array ( $entity, new ChangeOpLabel( 'de', 'Zusammenfassung', $validation ), 'add', 'de' );

		return $args;
	}

	/**
	 * @dataProvider changeOpSummaryProvider
	 */
	public function testUpdateSummary( $entity, ChangeOp $changeOp, $summaryExpectedAction, $summaryExpectedLanguage ) {
		$summary = new Summary();

		$changeOp->apply( $entity, $summary );

		$this->assertEquals( $summaryExpectedAction, $summary->getActionName() );
		$this->assertEquals( $summaryExpectedLanguage, $summary->getLanguageCode() );
	}
}
