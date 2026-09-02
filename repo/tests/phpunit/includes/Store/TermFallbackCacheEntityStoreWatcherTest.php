<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\TermFallbackCache;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Repo\Store\TermFallbackCacheEntityStoreWatcher;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\HashBagOStuff;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Store\TermFallbackCacheEntityStoreWatcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheEntityStoreWatcherTest extends MediaWikiIntegrationTestCase {

	private static BagOStuff $cache;
	private TermFallbackCacheEntityStoreWatcher $termFallbackCacheEntityStoreWatcher;
	private EntityRevisionLookup $entityRevisionLookup;
	private TermFallbackCacheFacade $termFallbackCacheFacade;

	private function getContentLanguages() {
		return new WikibaseContentLanguages(
			[ WikibaseContentLanguages::CONTEXT_TERM => WikibaseContentLanguages::getDefaultTermsLanguages() ]
		);
	}

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$cache = new HashBagOStuff();
	}

	public function setUp(): void {
		parent::setUp();
		$this->entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$this->termFallbackCacheFacade = $this->createMock( TermFallbackCacheFacade::class );
		// Passing in a class-static cache here so that we do not have to recreate the reverse
		// fallback mapping for every test. This spoils the isolation of the tests, but should be
		// okay in this case as the data is static.
		$this->termFallbackCacheEntityStoreWatcher = new TermFallbackCacheEntityStoreWatcher(
			$this->entityRevisionLookup,
			$this->termFallbackCacheFacade,
			$this->getContentLanguages(),
			self::$cache,
			WikibaseRepo::getLanguageFallbackChainFactory()
		);
	}

	public static function languagesFallingBackToDataProvider(): iterable {
		yield 'languages falling back to italian' => [
			'it',
			[ 'aae', 'co', 'egl', 'eml', 'fur', 'lij', 'lld', 'lmo', 'nap', 'pms', 'roa-tara',
				'rgn', 'scn', 'sdc', 'sro', 'vec' ],
		];
		yield 'languages falling back to german' => [
			'de',
			[ 'als', 'bar', 'de-at', 'de-ch', 'de-formal', 'dsb', 'frr', 'frs', 'gsw', 'hrx', 'hsb',
				'ksh', 'lb', 'nds', 'pdc', 'pdt', 'pfl', 'rm', 'sli', 'stq', 'vmf' ],
		];
		yield 'languages falling back to czech' => [
			'cs',
			[ 'sk' ],
		];
	}

	/**
	 * @dataProvider languagesFallingBackToDataProvider
	 */
	public function testGenerateFallbackLanguageList( string $targetLanguageCode, array $expectedLanguageCodesFallingBackToTarget ) {
		$termFallbackCacheEntityStoreWatcher = TestingAccessWrapper::newFromObject( $this->termFallbackCacheEntityStoreWatcher );
		$languageCodesFallingBackToTarget = $termFallbackCacheEntityStoreWatcher->getLanguagesFallingBackTo( $targetLanguageCode );
		$this->assertArrayEquals( $expectedLanguageCodesFallingBackToTarget, $languageCodesFallingBackToTarget );
	}

	private function setupEntityChangeFixture( EntityId $entityId, TermList $oldLabels, TermList $newLabels ): EntityRevision {
		$existingRevision = $this->createMock( EntityRevision::class );
		$existingEntity = $this->createMock( Property::class );
		$existingEntity->method( 'getId' )->willReturn( $entityId );
		$existingFingerprint = new Fingerprint( $oldLabels );
		$existingEntity->method( 'getFingerprint' )->willReturn( $existingFingerprint );
		$existingRevision->method( 'getEntity' )->willReturn( $existingEntity );
		$newRevision = $this->createMock( EntityRevision::class );
		$newEntity = $this->createMock( Property::class );
		$newEntity->method( 'getId' )->willReturn( $entityId );
		$newFingerprint = new Fingerprint( $newLabels );
		$newEntity->method( 'getFingerprint' )->willReturn( $newFingerprint );
		$newRevision->method( 'getEntity' )->willReturn( $newEntity );
		$this->entityRevisionLookup->expects( $this->once() )->method( 'getEntityRevision' )->willReturn( $existingRevision );
		return $newRevision;
	}

	public function testUpdateCacheEntry() {
		$propertyId = new NumericPropertyId( "P1" );
		$newRevision = $this->setupEntityChangeFixture(
			$propertyId,
			new TermList( [ new Term( 'cs', 'test' ) ] ),
			new TermList( [ new Term( 'cs', 'test changed' ) ] ),
		);
		$expectedTermFallbacks = [
			'cs' => new TermFallback( 'cs', 'test changed', 'cs', null ),
			'sk' => new TermFallback( 'sk', 'test changed', 'cs', null ),
		];
		$this->termFallbackCacheFacade->expects( $this->once() )->method( 'setMultiple' )->with(
			$expectedTermFallbacks, $propertyId, 0, TermTypes::TYPE_LABEL
		);
		$this->termFallbackCacheEntityStoreWatcher->entityUpdated( $newRevision, 1 );
	}

	public function testClearCacheEntryForLanguageThatNoLongerHasALabel() {
		$propertyId = new NumericPropertyId( "P1" );
		$newRevision = $this->setupEntityChangeFixture(
			$propertyId,
			new TermList( [
				new Term( 'cs', 'Czech' ),
				new Term( 'de-formal', 'Formal German' ),
				new Term( 'en', 'English fallback' ),
				new Term( 'sk', 'Slovak' ),
			] ),
			new TermList( [
				new Term( 'en', 'English fallback' ),
				new Term( 'sk', 'Slovak' ),
			] ),
		);
		$expectedTermFallbacks = [
			'cs' => new TermFallback( 'cs', 'Slovak', 'sk', null ),
			'de-formal' => new TermFallback( 'de-formal', 'English fallback', 'en', null ),
			'sk' => new TermFallback( 'sk', 'Slovak', 'sk', null ),
		];
		$this->termFallbackCacheFacade->expects( $this->once() )->method( 'setMultiple' )->with(
			$expectedTermFallbacks, $propertyId, 0, TermTypes::TYPE_LABEL
		);
		$this->termFallbackCacheEntityStoreWatcher->entityUpdated( $newRevision, 1 );
	}
}
