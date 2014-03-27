<?php

namespace Wikibase\Test;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpDescription;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use InvalidArgumentException;
use Wikibase\Summary;
use Wikibase\Validators\TermChangeValidationHelper;

/**
 * @covers Wikibase\ChangeOp\ChangeOpDescription
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpDescriptionTest extends \PHPUnit_Framework_TestCase {

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
			->method( 'validateDescription' )
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
		new ChangeOpDescription( 42, 'myOld', $this->getMockTermChangeValidationHelper()  );
	}

	public function changeOpDescriptionProvider() {
		// "INVALID" is invalid, "DUPE" is a dupe
		$validation = $this->getMockTermChangeValidationHelper();

		$args = array();
		$args[] = array ( new ChangeOpDescription( 'en', 'myNew', $validation ), 'myNew' );
		$args[] = array ( new ChangeOpDescription( 'en', null, $validation ), '' );
		$args['proto-dupe'] = array ( new ChangeOpDescription( 'en', 'DUPE', $validation ), 'DUPE' );

		return $args;
	}

	/**
	 * @dataProvider changeOpDescriptionProvider
	 *
	 * @param ChangeOpDescription $changeOpDescription
	 * @param string $expectedDescription
	 */
	public function testApply( $changeOpDescription, $expectedDescription ) {
		$entity = $this->provideNewEntity();
		$entity->setDescription( 'en', 'test' );
		$entity->setLabel( 'fr', 'DUPE' );

		$changeOpDescription->apply( $entity );

		$this->assertEquals( $expectedDescription, $entity->getDescription( 'en' ) );
	}

	public function invalidChangeOpDescriptionProvider() {
		// "INVALID" is invalid, "DUPE" is a dupe
		$validation = $this->getMockTermChangeValidationHelper();

		$args = array();
		$args['invalid description'] = array ( new ChangeOpDescription( 'fr', 'INVALID', $validation ) );
		$args['invalid language'] = array ( new ChangeOpDescription( 'INVALID', 'valid', $validation ) );
		$args['dupe'] = array ( new ChangeOpDescription( 'fr', 'DUPE', $validation ) );

		return $args;
	}

	/**
	 * @dataProvider invalidChangeOpDescriptionProvider
	 *
	 * @param ChangeOp $changeOpDescription
	 */
	public function testApplyInvalid( ChangeOp $changeOpDescription ) {
		$entity = $this->provideNewEntity();
		$entity->setLabel( 'fr', 'DUPE' );

		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpException' );
		$changeOpDescription->apply( $entity );
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
		$entity->setDescription( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', 'Zusammenfassung', $validation ), 'set', 'de' );

		$entity = $this->provideNewEntity();
		$entity->setDescription( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', null, $validation ), 'remove', 'de' );

		$entity = $this->provideNewEntity();
		$entity->removeDescription( 'de' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', 'Zusammenfassung', $validation ), 'add', 'de' );

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
