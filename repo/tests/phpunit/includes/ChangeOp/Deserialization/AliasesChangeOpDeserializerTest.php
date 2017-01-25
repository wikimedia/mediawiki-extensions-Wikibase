<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\StringNormalizer;
use Wikibase\Summary;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class AliasesChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenAliasesFieldNotAnArray_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$deserializer = $this->newAliasesChangeOpDeserializer( $this->getTermChangeOpValidator() );
				$deserializer->createEntityChangeOp( [ 'aliases' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$validator = $this->getMockBuilder( TermChangeOpSerializationValidator::class )
					->disableOriginalConstructor()
					->getMock();

				$validator->method( $this->anything() )
					->will(
						$this->throwException(
							new ChangeOpDeserializationException( 'invalid serialization', 'test-error' )
						)
					);

				$deserializer = $this->newAliasesChangeOpDeserializer( $validator );
				$deserializer->createEntityChangeOp( [
					'aliases' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ]
				] );
			},
			'test-error'
		);
	}

	public function testGivenChangeRequestSettingAliasesToItemWithNoAlias_addsAlias() {
		$item = $this->getItemWithoutAliases();
		$alias = 'foo';
		$changeOp = $this->newAliasesChangeOpDeserializer( $this->getTermChangeOpValidator() )->createEntityChangeOp( [
			'aliases' => [ 'en' => [ 'language' => 'en', 'value' => $alias ] ]
		] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $item->getAliasGroups()->getByLanguage( 'en' )->getAliases(), [ $alias ] );
	}

	public function testGivenChangeRequestSettingAliases_overridesExistingAliases() {
		$item = $this->getItemWithExistingAliases();
		$newAlias = 'foo';
		$changeOp = $this->newAliasesChangeOpDeserializer( $this->getTermChangeOpValidator() )->createEntityChangeOp( [
			'aliases' => [ 'en' => [ 'language' => 'en', 'value' => $newAlias ] ]
		] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $item->getAliasGroups()->getByLanguage( 'en' )->getAliases(), [ $newAlias ] );
	}

	public function testGivenChangeRequestRemovingAllExistingEnAliases_enAliasGroupDoesNotExist() {
		$item = $this->getItemWithExistingAliases();
		$existingAliases = $item->getAliasGroups()->getByLanguage( 'en' )->getAliases();
		$changeOp = $this->newAliasesChangeOpDeserializer( $this->getTermChangeOpValidator() )->createEntityChangeOp( [
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
		$changeOp = $this->newAliasesChangeOpDeserializer( $this->getTermChangeOpValidator() )->createEntityChangeOp( [
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

	private function newAliasesChangeOpDeserializer( TermChangeOpSerializationValidator $validator ) {
		return new AliasesChangeOpDeserializer(
			$this->getFingerPrintChangeOpFactory(),
			$this->getStringNormalizer(),
			$validator
		);
	}

	private function getStringNormalizer() {
		return new StringNormalizer();
	}

	private function getFingerPrintChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return new FingerprintChangeOpFactory( $mockProvider->getMockTermValidatorFactory() );
	}

	private function getTermChangeOpValidator() {
		return new TermChangeOpSerializationValidator( new StaticContentLanguages( [ 'en' ] ) );
	}

}
