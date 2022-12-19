<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

/**
 * Set of test methods that can be reused in AliasesChangeOpDeserializerTest and tests for
 * ChangeOpDeserializers of entities that have aliases.
 *
 * @license GPL-2.0-or-later
 */
trait AliasChangeOpDeserializationTester {

	public function testGivenChangeRequestSettingAliasesToItemWithNoAlias_addsAlias() {
		$entity = $this->getEntity();
		$alias = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => [ 'en' => [ 'language' => 'en', 'value' => $alias ] ],
		] );

		$changeOp->apply( $entity, new Summary() );
		$this->assertSame( $entity->getAliasGroups()->getByLanguage( 'en' )->getAliases(), [ $alias ] );
	}

	public function testGivenChangeRequestSettingAliases_overridesExistingAliases() {
		$entity = $this->getEntityWithExistingAliases();
		$newAlias = 'foo';
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => [ 'en' => [ 'language' => 'en', 'value' => $newAlias ] ],
		] );

		$changeOp->apply( $entity, new Summary() );
		$this->assertSame( $entity->getAliasGroups()->getByLanguage( 'en' )->getAliases(), [ $newAlias ] );
	}

	public function testGivenChangeRequestSettingAliasesToEmpty_enAliasGroupDoesNotExist() {
		$entity = $this->getEntityWithExistingAliases();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => [ 'en' => [] ],
		] );

		$changeOp->apply( $entity, new Summary() );
		$this->assertFalse( $entity->getAliasGroups()->hasGroupForLanguage( 'en' ) );
	}

	public function testGivenChangeRequestRemovingAllExistingEnAliases_enAliasGroupDoesNotExist() {
		$entity = $this->getEntityWithExistingAliases();
		$existingAliases = $entity->getAliasGroups()->getByLanguage( 'en' )->getAliases();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => array_map( function( $alias ) {
				return [ 'language' => 'en', 'value' => $alias, 'remove' => '' ];
			}, $existingAliases ),
		] );

		$changeOp->apply( $entity, new Summary() );
		$this->assertFalse( $entity->getAliasGroups()->hasGroupForLanguage( 'en' ) );
	}

	public function testGivenChangeRequestAddingAlias_addsAlias() {
		$entity = $this->getEntityWithExistingAliases();
		$newAlias = 'foo';
		$existingAliases = $entity->getAliasGroups()->getByLanguage( 'en' )->getAliases();
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => [
				'en' => [ 'language' => 'en', 'value' => $newAlias, 'add' => '' ],
			],
		] );

		$changeOp->apply( $entity, new Summary() );
		$this->assertSame(
			array_merge( $existingAliases, [ $newAlias ] ),
			$entity->getAliasGroups()->getByLanguage( 'en' )->getAliases()
		);
	}

	private function getEntityWithExistingAliases() {
		$existingEnAliases = [ 'en-existingAlias1', 'en-existingAlias2' ];
		$entity = $this->getEntity();
		$entity->setAliases( 'en', $existingEnAliases );

		return $entity;
	}

	/**
	 * @return AliasesProvider|EntityDocument
	 */
	abstract protected function getEntity();

	/**
	 * @return ChangeOpDeserializer
	 */
	abstract protected function getChangeOpDeserializer();

}
