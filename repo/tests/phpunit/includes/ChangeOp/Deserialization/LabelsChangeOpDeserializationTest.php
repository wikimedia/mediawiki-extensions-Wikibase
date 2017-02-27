<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Summary;

/**
 * Set of test methods that can be reused in LabelsChangeOpDeserializerTest and tests for
 * ChangeOpDeserializers of entities that have labels
 */
trait LabelsChangeOpDeserialization {

	public function testGivenChangeRequestWithLabel_addsLabel() {
		$item = $this->getItemWithoutEnLabel();
		$label = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => $label ] ] ]
		);

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $label, $item->getLabels()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithNewLabel_overridesExistingLabel() {
		$item = $this->getItemWithEnLabel();
		$newLabel = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => $newLabel ] ] ]
		);

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $newLabel, $item->getLabels()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithRemove_removesLabel() {
		$item = $this->getItemWithEnLabel();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ]
		);

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getLabels()->hasTermForLanguage( 'en' ) );
	}

	public function testGivenChangeRequestWithEmptyLabel_removesLabel() {
		$item = $this->getItemWithEnLabel();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => '' ] ] ]
		);

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getLabels()->hasTermForLanguage( 'en' ) );
	}

	private function getItemWithoutEnLabel() {
		return new Item();
	}

	private function getItemWithEnLabel() {
		$item = new Item();
		$item->setLabel( 'en', 'en-label' );

		return $item;
	}

}
