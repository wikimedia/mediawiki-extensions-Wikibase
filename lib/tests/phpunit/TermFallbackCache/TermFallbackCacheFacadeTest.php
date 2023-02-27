<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\TermFallbackCache;

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermFallbackCache\TermFallbackSerializerTrait;

/**
 * @covers \Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheFacadeTest extends TestCase {

	use TermFallbackSerializerTrait;

	public const REVISION_ID = 1;
	public const CACHE_TTL = 1;

	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|CacheInterface
	 */
	private $cache;

	protected function setUp(): void {
		parent::setUp();
		$this->cache = $this->createMock( CacheInterface::class );
	}

	public function getTermFallbackCacheFacade(): TermFallbackCacheFacade {
		return new TermFallbackCacheFacade(
			$this->cache,
			self::CACHE_TTL
		);
	}

	public function testGettingNoValue() {
		$facade = $this->getTermFallbackCacheFacade();
		$entityId = new ItemId( 'Q1' );

		$this->cache->expects( $this->once() )
			->method( 'get' )
			->with( 'Q1_1_en_label' )
			->willReturn( TermFallbackCacheFacade::NO_VALUE );

		$value = $facade->get( $entityId, self::REVISION_ID, 'en', TermTypes::TYPE_LABEL );
		$this->assertEquals( TermFallbackCacheFacade::NO_VALUE, $value );
	}

	public function setProvider() {
		return [
			'TermFallback' => [ new TermFallback( 'en', 'label', 'en', null ) ],
			'null' => [ null ],
		];
	}

	/**
	 * @dataProvider setProvider
	 */
	public function testSettingCache( $value ) {
		$facade = $this->getTermFallbackCacheFacade();
		$entityId = new ItemId( 'Q1' );

		$this->cache->expects( $this->once() )
			->method( 'set' )
			->with( 'Q1_1_en_label', $this->serialize( $value ), self::CACHE_TTL );

		$facade->set( $value, $entityId, self::REVISION_ID, 'en', TermTypes::TYPE_LABEL );
	}

	public function testGettingCache() {
		$facade = $this->getTermFallbackCacheFacade();
		$entityId = new ItemId( 'Q1' );
		$value = new TermFallback( 'en', 'label', 'en', null );

		$this->cache->expects( $this->once() )
			->method( 'get' )
			->with( 'Q1_1_en_label' )
			->willReturn( $this->serialize( $value ) );

		$actual = $facade->get( $entityId, self::REVISION_ID, 'en', TermTypes::TYPE_LABEL );

		$this->assertEquals( $value, $actual );
	}

}
