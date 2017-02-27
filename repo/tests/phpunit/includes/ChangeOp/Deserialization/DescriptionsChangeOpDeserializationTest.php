<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Summary;

/**
 * Set of test methods that can be reused in DescriptionsChangeOpDeserializerTest and tests for
 * ChangeOpDeserializers of entities that have descriptions
 */
trait DescriptionsChangeOpDeserializationTest {

	public function testGivenChangeRequestWithDescription_addsDescription() {
		$item = $this->getItemWithoutEnDescription();
		$description = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[
				'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => $description ] ]
			] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $description, $item->getDescriptions()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithNewDescription_overridesExistingDescription() {
		$item = $this->getItemWithEnDescription();
		$newDescription = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => $newDescription ] ] ]
		);

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $newDescription, $item->getDescriptions()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithRemove_removesDescription() {
		$item = $this->getItemWithEnDescription();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ]
		);

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getDescriptions()->hasTermForLanguage( 'en' ) );
	}

	public function testGivenChangeRequestWithEmptyValue_removesDescription() {
		$item = $this->getItemWithEnDescription();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp(
			[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => '' ] ] ]
		);

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getDescriptions()->hasTermForLanguage( 'en' ) );
	}

	private function getItemWithoutEnDescription() {
		return new Item();
	}

	private function getItemWithEnDescription() {
		$item = new Item();
		$item->setDescription( 'en', 'en-description' );

		return $item;
	}

}
