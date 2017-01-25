<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\ItemContent;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer;
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
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newAliasesChangeOpDeserializer()->createEntityChangeOp( [ 'aliases' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newAliasesChangeOpDeserializer()->createEntityChangeOp( [
					'aliases' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ]
				] );
			},
			'inconsistent-language'
		);
	}

	public function testGivenChangeRequestSettingAliases_overridesExistingAliases() {
		$item = $this->getItemWithExistingAliases();
		$newAlias = 'foo';
		$changeOp = $this->newAliasesChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => [ 'en' => [ 'language' => 'en', 'value' => $newAlias ] ]
		] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $item->getFingerprint()->getAliasGroup( 'en' )->getAliases(), [ $newAlias ] );
	}

	public function testGivenChangeRequestRemovingAllExistingEnAliases_enAliasGroupDoesNotExist() {
		$item = $this->getItemWithExistingAliases();
		$existingAliases = $item->getFingerprint()->getAliasGroup( 'en' )->getAliases();
		$changeOp = $this->newAliasesChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => array_map( function( $alias ) {
				return [ 'language' => 'en', 'value' => $alias, 'remove' => '' ];
			}, $existingAliases )
		] );

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getFingerprint()->hasAliasGroup( 'en' ) );
	}

	public function testGivenChangeRequestAddingAlias_addsAlias() {
		$item = $this->getItemWithExistingAliases();
		$newAlias = 'foo';
		$existingAliases = $item->getFingerprint()->getAliasGroup( 'en' )->getAliases();
		$changeOp = $this->newAliasesChangeOpDeserializer()->createEntityChangeOp( [
			'aliases' => [
				'en' => [ 'language' => 'en', 'value' => $newAlias, 'add' => '' ]
			]
		] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame(
			array_merge( $existingAliases, [ $newAlias ] ),
			$item->getFingerprint()->getAliasGroup( 'en' )->getAliases()
		);
	}

	private function getItemWithExistingAliases() {
		$existingEnAliases = [ 'en-existingAlias1', 'en-existingAlias2' ];
		$itemContent = ItemContent::newEmpty();
		$item = $itemContent->getEntity();
		$item->setAliases( 'en', $existingEnAliases );

		return $item->copy();
	}

	private function newAliasesChangeOpDeserializer() {
		return new AliasesChangeOpDeserializer(
			$this->getFingerPrintChangeOpFactory(),
			$this->getStringNormalizer(),
			$this->getTermChangeOpValidator()
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
		return new TermChangeOpSerializationValidator( new StaticContentLanguages( [ 'en', 'de' ] ) );
	}

}
