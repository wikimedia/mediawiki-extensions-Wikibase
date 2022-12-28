<?php

namespace Wikibase\Client\Tests\Unit\DataAccess;

use DataValues\StringValue;
use MediaWiki\MediaWikiServices;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;

/**
 * @covers \Wikibase\Client\DataAccess\StatementTransclusionInteractor
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class StatementTransclusionInteractorTest extends \PHPUnit\Framework\TestCase {

	public function formatProvider() {
		return [
			[ SnakFormatter::FORMAT_PLAIN, 'a kitten!, two kittens!!' ],
			[ SnakFormatter::FORMAT_WIKI, '<span>a kitten!, two kittens!!</span>' ],
			[ SnakFormatter::FORMAT_HTML, '<span>a kitten!, two kittens!!</span>' ],
			[ SnakFormatter::FORMAT_HTML_DIFF, '<span>a kitten!, two kittens!!</span>' ],
		];
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testRender( $format, $expected ) {
		$propertyId = new NumericPropertyId( 'P1337' );
		$snaks = [
			'Q42$1' => new PropertyValueSnak( $propertyId, new StringValue( 'a kitten!' ) ),
			'Q42$2' => new PropertyValueSnak( $propertyId, new StringValue( 'two kittens!!' ) ),
			'Q42$3' => new PropertyValueSnak( $propertyId, new StringValue( '' ) ),
		];

		$usageAccumulator = new HashUsageAccumulator();
		$renderer = $this->getInteractor( $this->getPropertyIdResolver(), $snaks, $format, $usageAccumulator );
		$result = $renderer->render( new ItemId( 'Q42' ), 'p1337' );

		$this->assertSame( $expected, $result );

		$this->assertEquals(
			[ 'Q42#C.P1337' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testRender_PropertyLabelNotResolvedException() {
		$usageAccumulator = new HashUsageAccumulator();
		$renderer = $this->getInteractor(
			$this->getPropertyIdResolverForPropertyNotFound(),
			[],
			SnakFormatter::FORMAT_PLAIN,
			$usageAccumulator
		);

		$exceptionThrown = false;
		try {
			$renderer->render( new ItemId( 'Q42' ), 'blah' );
		} catch ( PropertyLabelNotResolvedException $ex ) {
			$exceptionThrown = true;
		}
		$this->assertTrue( $exceptionThrown, 'PropertyLabelNotResolvedException exception thrown' );

		$this->assertEquals(
			[],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testRender_empty( $format ) {
		$usageAccumulator = new HashUsageAccumulator();
		$renderer = $this->getInteractor( $this->getPropertyIdResolver(), [], $format, $usageAccumulator );
		$this->assertSame( '', $renderer->render( new ItemId( 'Q42' ), 'P1337' ) );

		$this->assertEquals(
			[ 'Q42#C.P1337' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testRender_unresolvedRedirect() {
		$usageAccumulator = new HashUsageAccumulator();
		$renderer = $this->getInteractor(
			$this->getPropertyIdResolver(),
			[],
			SnakFormatter::FORMAT_PLAIN,
			$usageAccumulator
		);

		$this->assertSame( '', $renderer->render( new ItemId( 'Q43' ), 'P1337' ) );

		$this->assertEquals(
			[ 'Q43#C.P1337' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testRender_unknownEntity() {
		$usageAccumulator = new HashUsageAccumulator();
		$renderer = $this->getInteractor(
			$this->getPropertyIdResolver(),
			[],
			SnakFormatter::FORMAT_PLAIN,
			$usageAccumulator
		);

		$this->assertSame( '', $renderer->render( new ItemId( 'Q43333' ), 'P1337' ) );

		$this->assertEquals(
			[ 'Q43333#C.P1337' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	/**
	 * @param PropertyIdResolver $propertyIdResolver
	 * @param Snak[] $snaks
	 * @param string $format One of the SnakFormatter::FORMAT_… constants
	 * @param HashUsageAccumulator|null $usageAccumulator
	 *
	 * @return StatementTransclusionInteractor
	 */
	private function getInteractor(
		PropertyIdResolver $propertyIdResolver,
		array $snaks = [],
		$format = SnakFormatter::FORMAT_PLAIN,
		HashUsageAccumulator $usageAccumulator = null
	) {
		$targetLanguage = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );

		return new StatementTransclusionInteractor(
			$targetLanguage,
			$propertyIdResolver,
			$this->getSnaksFinder( $snaks ),
			$this->getSnakFormatter( $format ),
			$this->getEntityLookup(),
			$usageAccumulator ?: new HashUsageAccumulator()
		);
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return SnaksFinder
	 */
	private function getSnaksFinder( array $snaks ) {
		$snaksFinder = $this->createMock( SnaksFinder::class );

		$snaksFinder->method( 'findSnaks' )
			->willReturn( $snaks );

		return $snaksFinder;
	}

	/**
	 * @return PropertyIdResolver
	 */
	private function getPropertyIdResolver() {
		$propertyIdResolver = $this->createMock( PropertyIdResolver::class );

		$propertyIdResolver->method( 'resolvePropertyId' )
			->willReturn( new NumericPropertyId( 'P1337' ) );

		return $propertyIdResolver;
	}

	/**
	 * @return PropertyIdResolver
	 */
	private function getPropertyIdResolverForPropertyNotFound() {
		$propertyIdResolver = $this->createMock( PropertyIdResolver::class );

		$propertyIdResolver->method( 'resolvePropertyId' )
			->willReturnCallback( function( $propertyLabelOrId, $languageCode ) {
				throw new PropertyLabelNotResolvedException( $propertyLabelOrId, $languageCode );
			} );

		return $propertyIdResolver;
	}

	private function getEntityLookup() {
		return new RevisionBasedEntityLookup( $this->getEntityRevisionLookup() );
	}

	/**
	 * @return EntityRevisionLookup
	 */
	private function getEntityRevisionLookup() {
		$lookup = $this->createMock( EntityRevisionLookup::class );

		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( function( EntityId $entityId ) {
				switch ( $entityId->getSerialization() ) {
					case 'Q42':
						return new EntityRevision( new Item( new ItemId( 'Q42' ) ) );
					case 'Q43':
						throw new UnresolvedEntityRedirectException(
							$entityId,
							new ItemId( 'Q404' )
						);
					default:
						return null;
				}
			} );

		return $lookup;
	}

	/**
	 * @param string $format One of the SnakFormatter::FORMAT_… constants
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter( $format ) {
		$snakFormatter = $this->createMock( SnakFormatter::class );

		$snakFormatter->method( 'formatSnak' )
			->willReturnCallback(
				function ( Snak $snak ) {
					if ( $snak instanceof PropertyValueSnak ) {
						$value = $snak->getDataValue();
						if ( $value instanceof StringValue ) {
							return $value->getValue();
						} elseif ( $value instanceof EntityIdValue ) {
							return $value->getEntityId()->getSerialization();
						} else {
							return '(' . $value->getType() . ')';
						}
					} else {
						return '(' . $snak->getType() . ')';
					}
				}
			);

		$snakFormatter->method( 'getFormat' )
			->willReturn( $format );

		return $snakFormatter;
	}

}
