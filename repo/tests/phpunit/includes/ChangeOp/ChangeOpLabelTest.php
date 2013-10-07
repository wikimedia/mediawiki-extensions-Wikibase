<?php

namespace Wikibase\Test;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\Summary;
use Wikibase\ChangeOp\ChangeOpLabel;
use Wikibase\ItemContent;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOp\ChangeOpLabel
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpLabelTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct() {
		$changeOpLabel = new ChangeOpLabel( 42, 'myNew' );
	}

	public function changeOpLabelProvider() {
		$args = array();
		$args[] = array ( new ChangeOpLabel( 'en', 'myNew' ), 'myNew' );
		$args[] = array ( new ChangeOpLabel( 'en', null ), '' );

		return $args;
	}

	/**
	 * @dataProvider changeOpLabelProvider
	 *
	 * @param ChangeOpLabel $changeOpLabel
	 * @param string $expectedLabel
	 */
	public function testApply( $changeOpLabel, $expectedLabel ) {
		$entity = $this->provideNewEntity();
		$entity->setLabel( 'en', 'test' );

		$changeOpLabel->apply( $entity );

		$this->assertEquals( $expectedLabel, $entity->getLabel( 'en' ) );
	}

	protected function provideNewEntity() {
		$item = ItemContent::newEmpty();
		return $item->getEntity();
	}

	public function changeOpSummaryProvider() {
		$args = array();

		$entity = $this->provideNewEntity();
		$entity->setLabel( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpLabel( 'de', 'Zusammenfassung' ), 'set', 'de' );

		$entity = $this->provideNewEntity();
		$entity->setLabel( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpLabel( 'de', null ), 'remove', 'de' );

		$entity = $this->provideNewEntity();
		$entity->removeLabel( 'de' );
		$args[] = array ( $entity, new ChangeOpLabel( 'de', 'Zusammenfassung' ), 'add', 'de' );

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
