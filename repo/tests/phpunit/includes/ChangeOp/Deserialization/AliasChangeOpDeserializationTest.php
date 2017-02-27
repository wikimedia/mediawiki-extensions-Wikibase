<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Summary;

/**
 * Set of test methods that can be reused in AliasesChangeOpDeserializerTest and tests for
 * ChangeOpDeserializers of entities that have aliases
 */
trait AliasChangeOpDeserializationTest {

	public function testGivenChangeRequestSettingAliasesToItemWithNoAlias_addsAlias() {
		$item = $this->getItemWithoutAliases();
		$alias = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => [ 'en' => [ 'language' => 'en', 'value' => $alias ] ]
		] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $item->getAliasGroups()->getByLanguage( 'en' )->getAliases(), [ $alias ] );
	}

	public function testGivenChangeRequestSettingAliases_overridesExistingAliases() {
		$item = $this->getItemWithExistingAliases();
		$newAlias = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => [ 'en' => [ 'language' => 'en', 'value' => $newAlias ] ]
		] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $item->getAliasGroups()->getByLanguage( 'en' )->getAliases(), [ $newAlias ] );
	}

	public function testGivenChangeRequestRemovingAllExistingEnAliases_enAliasGroupDoesNotExist() {
		$item = $this->getItemWithExistingAliases();
		$existingAliases = $item->getAliasGroups()->getByLanguage( 'en' )->getAliases();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => array_map( function( $alias ) {
				return [ 'language' => 'en', 'value' => $alias, 'remove' => '' ];
			}, $existingAliases )
		] );

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getAliasGroups()->hasGroupForLanguage( 'en' ) );
	}

	public function testGivenChangeRequestAddingAlias_addsAlias() {
		$item = $this->getItemWithExistingAliases();
		$newAlias = 'foo';
		$existingAliases = $item->getAliasGroups()->getByLanguage( 'en' )->getAliases();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => [
				'en' => [ 'language' => 'en', 'value' => $newAlias, 'add' => '' ]
			]
		] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame(
			array_merge( $existingAliases, [ $newAlias ] ),
			$item->getAliasGroups()->getByLanguage( 'en' )->getAliases()
		);
	}

	private function getItemWithoutAliases() {
		return new Item();
	}

	private function getItemWithExistingAliases() {
		$existingEnAliases = [ 'en-existingAlias1', 'en-existingAlias2' ];
		$item = new Item();
		$item->setAliases( 'en', $existingEnAliases );

		return $item;
	}

}
