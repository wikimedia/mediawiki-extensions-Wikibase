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

	public static function invalidConstructorProvider() {
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
		$validatorFactory = $this->getTermValidatorFactory();

		$this->expectException( InvalidArgumentException::class );
		new ChangeOpAliases( $language, $aliases, $action, $validatorFactory );
	}

	public static function changeOpAliasesProvider(): iterable {
		$enAliases = [ 'en-alias1', 'en-alias2', 'en-alias3' ];
		$existingEnAliases = [ 'en-existingAlias1', 'en-existingAlias2' ];
		$itemContent = new ItemContent( new EntityInstanceHolder( new Item() ) );
		$item = $itemContent->getEntity();
		$item->setAliases( 'en', $existingEnAliases );

		return [
			'add' => [
				$item->copy(),
				[ 'en', $enAliases, 'add' ],
				array_merge( $existingEnAliases, $enAliases ),
			],
			'set' => [
				$item->copy(),
				[ 'en', $enAliases, 'set' ],
				$enAliases,
			],
			'set (default)' => [
				$item->copy(),
				[ 'en', $enAliases, '' ],
				$enAliases,
			],
			'remove' => [
				$item->copy(),
				[ 'en', $existingEnAliases, 'remove' ],
				null,
			],
		];
	}

	/**
	 * @dataProvider changeOpAliasesProvider
	 */
	public function testApply(
		Item $item,
		array $changeOpAliasesParams,
		array $expectedAliases = null
	) {
		$changeOpAliasesParams[] = $this->getTermValidatorFactory();
		$changeOpAliases = new ChangeOpAliases( ...$changeOpAliasesParams );
		$changeOpAliases->apply( $item );
		$fingerprint = $item->getFingerprint();

		if ( $expectedAliases === null ) {
			$this->assertFalse( $fingerprint->hasAliasGroup( 'en' ) );
		} else {
			$this->assertEquals( $expectedAliases, $fingerprint->getAliasGroup( 'en' )->getAliases() );
		}
	}

	public static function changeOpAliasesAndResultProvider() {
		$enAliases = [ 'en-alias1', 'en-alias2', 'en-alias3' ];
		$existingEnAliases = [ 'en-existingAlias1', 'en-existingAlias2' ];
		$itemContent = new ItemContent( new EntityInstanceHolder( new Item() ) );
		$item = $itemContent->getEntity();
		$item->setAliases( 'en', $existingEnAliases );

		return [
			'add new aliases' => [
				$item->copy(),
				[ 'en', $enAliases, 'add' ],
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
				[ 'en', $enAliases, 'set' ],
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
				[ 'en', $existingEnAliases, 'remove' ],
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
				[ 'en', [ 'en-existingAlias1' ], 'set' ],
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
				[ 'en', [ 'en-NonExistingAlias1' ], 'remove' ],
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
				[ 'en', [], 'set' ],
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
				[ 'en', [], 'add' ],
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
				[ 'en', [], 'remove' ],
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
	 * @dataProvider changeOpAliasesAndResultProvider
	 */
	public function testApplyReturnsCorrectChangeOpResult(
		Item $item,
		array $changeOpAliasesParams,
		ChangeOpAliasesResult $expectedChangeOpAliasesResult
	) {
		$changeOpAliasesParams[] = $this->getTermValidatorFactory();

		$changeOpAliases = new ChangeOpAliases( ...$changeOpAliasesParams );
		$changeOpAliasesResult = $changeOpAliases->apply( $item );

		$this->assertEquals( $expectedChangeOpAliasesResult, $changeOpAliasesResult );
	}

	public static function validateProvider() {
		return [
			'set invalid alias' => [
				[ 'fr', [ 'INVALID' ], 'set' ],
			],
			'add invalid alias' => [
				[ 'fr', [ 'INVALID' ], 'add' ],
			],
			'set invalid language' => [
				[ 'INVALID', [ 'valid' ], 'set' ],
			],
			'add invalid language' => [
				[ 'INVALID', [ 'valid' ], 'add' ],
			],
			'remove invalid language' => [
				[ 'INVALID', [ 'valid' ], 'remove' ],
			],
		];
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( array $changeOpAliasesParams ) {
		$entity = new Item();
		$changeOpAliasesParams[] = $this->getTermValidatorFactory();
		$changeOpAliases = new ChangeOpAliases( ...$changeOpAliasesParams );

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
