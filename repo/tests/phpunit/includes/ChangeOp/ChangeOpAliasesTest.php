<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\ChangeOp\ChangeOpAliases;
use Wikibase\Repo\ChangeOp\ChangeOpAliasesResult;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\Content\EntityInstanceHolder;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpAliases
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpAliasesTest extends \PHPUnit\Framework\TestCase {

	private function getTermValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockTermValidatorFactory();
	}

	public function invalidConstructorProvider() {
		$args = [];
		$args[] = [ 42, [ 'myNewAlias' ], 'add' ];
		$args[] = [ 'en', [ 'myNewAlias' ], 1234 ];

		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 *
	 * @param string $language
	 * @param string[] $aliases
	 * @param string $action
	 */
	public function testInvalidConstruct( $language, array $aliases, $action ) {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$this->expectException( InvalidArgumentException::class );
		new ChangeOpAliases( $language, $aliases, $action, $validatorFactory );
	}

	public function changeOpAliasesProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$enAliases = [ 'en-alias1', 'en-alias2', 'en-alias3' ];
		$existingEnAliases = [ 'en-existingAlias1', 'en-existingAlias2' ];
		$itemContent = new ItemContent( new EntityInstanceHolder( new Item() ) );
		$item = $itemContent->getEntity();
		$item->setAliases( 'en', $existingEnAliases );

		return [
			'add' => [
				$item->copy(),
				new ChangeOpAliases( 'en', $enAliases, 'add', $validatorFactory ),
				array_merge( $existingEnAliases, $enAliases ),
			],
			'set' => [
				$item->copy(),
				new ChangeOpAliases( 'en', $enAliases, 'set', $validatorFactory ),
				$enAliases,
			],
			'set (default)' => [
				$item->copy(),
				new ChangeOpAliases( 'en', $enAliases, '', $validatorFactory ),
				$enAliases,
			],
			'remove' => [
				$item->copy(),
				new ChangeOpAliases( 'en', $existingEnAliases, 'remove', $validatorFactory ),
				null,
			],
		];
	}

	/**
	 * @dataProvider changeOpAliasesProvider
	 */
	public function testApply(
		Item $item,
		ChangeOpAliases $changeOpAliases,
		array $expectedAliases = null
	) {
		$changeOpAliases->apply( $item );
		$fingerprint = $item->getFingerprint();

		if ( $expectedAliases === null ) {
			$this->assertFalse( $fingerprint->hasAliasGroup( 'en' ) );
		} else {
			$this->assertEquals( $expectedAliases, $fingerprint->getAliasGroup( 'en' )->getAliases() );
		}
	}

	public function changeOpAliasesAndResultProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$enAliases = [ 'en-alias1', 'en-alias2', 'en-alias3' ];
		$existingEnAliases = [ 'en-existingAlias1', 'en-existingAlias2' ];
		$itemContent = new ItemContent( new EntityInstanceHolder( new Item() ) );
		$item = $itemContent->getEntity();
		$item->setAliases( 'en', $existingEnAliases );

		return [
			'add new aliases' => [
				$item->copy(),
				new ChangeOpAliases( 'en', $enAliases, 'add', $validatorFactory ),
				new ChangeOpAliasesResult(
					$item->getId(),
					'en',
					$existingEnAliases,
					array_merge( $existingEnAliases, $enAliases ),
					true
				),
			],
			'set new aliases' => [
				$item->copy(),
				new ChangeOpAliases( 'en', $enAliases, 'set', $validatorFactory ),
				new ChangeOpAliasesResult(
					$item->getId(),
					'en',
					$existingEnAliases,
					$enAliases,
					true
				),
			],
			'remove existing aliases' => [
				$item->copy(),
				new ChangeOpAliases( 'en', $existingEnAliases, 'remove', $validatorFactory ),
				new ChangeOpAliasesResult(
					$item->getId(),
					'en',
					$existingEnAliases,
					[],
					true
				),
			],
			'remove only one existing alias' => [
				$item->copy(),
				new ChangeOpAliases( 'en', [ 'en-existingAlias1' ], 'set', $validatorFactory ),
				new ChangeOpAliasesResult(
					$item->getId(),
					'en',
					$existingEnAliases,
					[ 'en-existingAlias1' ],
					true
				),
			],
			'remove non existing alias' => [
				$item->copy(),
				new ChangeOpAliases( 'en', [ 'en-NonExistingAlias1' ], 'remove', $validatorFactory ),
				new ChangeOpAliasesResult(
					$item->getId(),
					'en',
					$existingEnAliases,
					$existingEnAliases,
					false
				),
			],
			'set to no aliases' => [
				$item->copy(),
				new ChangeOpAliases( 'en', [], 'set', $validatorFactory ),
				new ChangeOpAliasesResult(
					$item->getId(),
					'en',
					$existingEnAliases,
					[],
					true
				),
			],
			'add no aliases' => [
				$item->copy(),
				new ChangeOpAliases( 'en', [], 'add', $validatorFactory ),
				new ChangeOpAliasesResult(
					$item->getId(),
					'en',
					$existingEnAliases,
					$existingEnAliases,
					false
				),
			],
			'remove none' => [
				$item->copy(),
				new ChangeOpAliases( 'en', [], 'remove', $validatorFactory ),
				new ChangeOpAliasesResult(
					$item->getId(),
					'en',
					$existingEnAliases,
					$existingEnAliases,
					false
				),
			],
		];
	}

	/**
	 * @param Item $item
	 * @param ChangeOpAliases $changeOpAliases
	 * @param ChangeOpAliasesResult $expectedChangeOpAliasesResult
	 * @dataProvider changeOpAliasesAndResultProvider
	 */
	public function testApplyReturnsCorrectChangeOpResult( $item, $changeOpAliases, $expectedChangeOpAliasesResult ) {
		$changeOpAliasesResult = $changeOpAliases->apply( $item );

		$this->assertEquals( $expectedChangeOpAliasesResult, $changeOpAliasesResult );
	}

	public function validateProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		return [
			'set invalid alias' => [
				new ChangeOpAliases( 'fr', [ 'INVALID' ], 'set', $validatorFactory ),
			],
			'add invalid alias' => [
				new ChangeOpAliases( 'fr', [ 'INVALID' ], 'add', $validatorFactory ),
			],
			'set invalid language' => [
				new ChangeOpAliases( 'INVALID', [ 'valid' ], 'set', $validatorFactory ),
			],
			'add invalid language' => [
				new ChangeOpAliases( 'INVALID', [ 'valid' ], 'add', $validatorFactory ),
			],
			'remove invalid language' => [
				new ChangeOpAliases( 'INVALID', [ 'valid' ], 'remove', $validatorFactory ),
			],
		];
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( ChangeOpAliases $changeOpAliases ) {
		$entity = new Item();

		$result = $changeOpAliases->validate( $entity );
		$this->assertFalse( $result->isValid() );
	}

	public function testValidateLeavesEntityUntouched() {
		$entity = new Item();
		$validatorFactory = $this->getTermValidatorFactory();
		$changeOpAliases = new ChangeOpAliases( 'de', [ 'test' ], 'set', $validatorFactory );
		$changeOpAliases->validate( $entity );
		$this->assertTrue( $entity->equals( new Item() ) );
	}

	public function testApplyWithInvalidAction() {
		$entity = new Item();
		$validatorFactory = $this->getTermValidatorFactory();

		$changeOpAliases = new ChangeOpAliases( 'en', [ 'test' ], 'invalidAction', $validatorFactory );

		$this->expectException( ChangeOpException::class );
		$changeOpAliases->apply( $entity );
	}

	public function testApplyNoAliasesProvider() {
		$changeOp = new ChangeOpAliases( 'en', [ 'Foo' ], 'set', $this->getTermValidatorFactory() );
		$entity = $this->createMock( EntityDocument::class );

		$this->expectException( InvalidArgumentException::class );
		$changeOp->apply( $entity );
	}

	public function testGetActions() {
		$changeOp = new ChangeOpAliases( 'en', [ 'Foo' ], 'set', $this->getTermValidatorFactory() );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT_TERMS ], $changeOp->getActions() );
	}

}
