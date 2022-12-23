<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Languages\LanguageNameUtils;
use Wikibase\DataAccess\NullPrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryTest extends ServiceWiringTestCase {

	/** @dataProvider provideSettingsWithLengthLimit3 */
	public function testLimitsLengthTo3( array $settings ): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( $settings ) );
		$this->mockService( 'WikibaseRepo.TermsLanguages',
			new StaticContentLanguages( [] ) );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );
		$this->mockService( 'WikibaseRepo.TermsCollisionDetectorFactory',
			$this->createMock( TermsCollisionDetectorFactory::class ) );
		$this->mockService( 'WikibaseRepo.TermLookup',
			new NullPrefetchingTermLookup() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageNameUtils' )
			->willReturn( $this->createMock( LanguageNameUtils::class ) );

		/** @var TermValidatorFactory $termValidatorFactory */
		$termValidatorFactory = $this->getService( 'WikibaseRepo.TermValidatorFactory' );

		$this->assertInstanceOf( TermValidatorFactory::class, $termValidatorFactory );
		$itemLabelValidator = $termValidatorFactory->getLabelValidator( Item::ENTITY_TYPE );
		$this->assertTrue( $itemLabelValidator->validate( '123' )->isValid() );
		$this->assertFalse( $itemLabelValidator->validate( '1234' )->isValid() );
	}

	public function provideSettingsWithLengthLimit3(): iterable {
		yield 'modern settings' => [ [
			'string-limits' => [
				'multilang' => [ 'length' => 3 ],
			],
		] ];

		yield 'deprecated settings' => [ [
			'multilang-limits' => [ 'length' => 3 ],
		] ];

		yield 'both settings (deprecated should take precedence)' => [ [
			'string-limits' => [
				'multilang' => [ 'length' => 30 ],
			],
			'multilang-limits' => [ 'length' => 3 ],
		] ];
	}

}
