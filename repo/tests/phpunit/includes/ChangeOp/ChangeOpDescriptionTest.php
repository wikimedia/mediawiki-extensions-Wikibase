<?php

namespace Wikibase\Test;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpDescription;
use Wikibase\ItemContent;
use InvalidArgumentException;
use Wikibase\Summary;

/**
 * @covers Wikibase\ChangeOp\ChangeOpDescription
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpDescriptionTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct() {
		new ChangeOpDescription( 42, 'myOld' );
	}

	public function changeOpDescriptionProvider() {
		$args = array();
		$args[] = array ( new ChangeOpDescription( 'en', 'myNew' ), 'myNew' );
		$args[] = array ( new ChangeOpDescription( 'en', null ), '' );

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

		$changeOpDescription->apply( $entity );

		$this->assertEquals( $expectedDescription, $entity->getDescription( 'en' ) );
	}

	protected function provideNewEntity() {
		$item = ItemContent::newEmpty();
		return $item->getEntity();
	}

	public function changeOpSummaryProvider() {
		$args = array();

		$entity = $this->provideNewEntity();
		$entity->setDescription( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', 'Zusammenfassung' ), 'set', 'de' );

		$entity = $this->provideNewEntity();
		$entity->setDescription( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', null ), 'remove', 'de' );

		$entity = $this->provideNewEntity();
		$entity->removeDescription( 'de' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', 'Zusammenfassung' ), 'add', 'de' );

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
