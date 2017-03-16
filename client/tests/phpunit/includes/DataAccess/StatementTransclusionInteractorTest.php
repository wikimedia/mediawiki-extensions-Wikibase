<?php

namespace Wikibase\Client\Tests\DataAccess;

use DataValues\StringValue;
use Language;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Edrsf\EntityRevision;
use Wikibase\Edrsf\EntityRevisionLookup;
use Wikibase\Edrsf\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;

/**
 * @covers Wikibase\Client\DataAccess\StatementTransclusionInteractor
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class StatementTransclusionInteractorTest extends PHPUnit_Framework_TestCase {

	public function formatProvider() {
		return [
			[ SnakFormatter::FORMAT_PLAIN, 'a kitten!, two kittens!!' ],
			[ SnakFormatter::FORMAT_WIKI, '<span>a kitten!, two kittens!!</span>' ],
			[ SnakFormatter::FORMAT_HTML, '<span>a kitten!, two kittens!!</span>' ],
			[ SnakFormatter::FORMAT_HTML_WIDGET, '<span>a kitten!, two kittens!!</span>' ],
			[ SnakFormatter::FORMAT_HTML_DIFF, '<span>a kitten!, two kittens!!</span>' ],
		];
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testRender( $format, $expected ) {
		$propertyId = new PropertyId( 'P1337' );
		$snaks = [
			'Q42$1' => new PropertyValueSnak( $propertyId, new StringValue( 'a kitten!' ) ),
			'Q42$2' => new PropertyValueSnak( $propertyId, new StringValue( 'two kittens!!' ) ),
			'Q42$3' => new PropertyValueSnak( $propertyId, new StringValue( '' ) ),
		];

		$renderer = $this->getInteractor( $this->getPropertyIdResolver(), $snaks, $format );
		$result = $renderer->render( new ItemId( 'Q42' ), 'p1337' );

		$this->assertSame( $expected, $result );
	}

	public function testRender_PropertyLabelNotResolvedException() {
		$renderer = $this->getInteractor( $this->getPropertyIdResolverForPropertyNotFound() );

		$this->setExpectedException( PropertyLabelNotResolvedException::class );
		$renderer->render( new ItemId( 'Q42' ), 'blah' );
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testRender_empty( $format ) {
		$renderer = $this->getInteractor( $this->getPropertyIdResolver(), [], $format );
		$this->assertSame( '', $renderer->render( new ItemId( 'Q42' ), 'P1337' ) );
	}

	public function testRender_unresolvedRedirect() {
		$renderer = $this->getInteractor( $this->getPropertyIdResolver() );

		$this->assertSame( '', $renderer->render( new ItemId( 'Q43' ), 'P1337' ) );
	}

	public function testRender_unknownEntity() {
		$renderer = $this->getInteractor( $this->getPropertyIdResolver() );

		$this->assertSame( '', $renderer->render( new ItemId( 'Q43333' ), 'P1337' ) );
	}

	/**
	 * @param PropertyIdResolver $propertyIdResolver
	 * @param Snak[] $snaks
	 * @param string $format One of the SnakFormatter::FORMAT_… constants
	 *
	 * @return StatementTransclusionInteractor
	 */
	private function getInteractor(
		PropertyIdResolver $propertyIdResolver,
		array $snaks = [],
		$format = SnakFormatter::FORMAT_PLAIN
	) {
		$targetLanguage = Language::factory( 'en' );

		return new StatementTransclusionInteractor(
			$targetLanguage,
			$propertyIdResolver,
			$this->getSnaksFinder( $snaks ),
			$this->getSnakFormatter( $format ),
			$this->getEntityLookup()
		);
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return SnaksFinder
	 */
	private function getSnaksFinder( array $snaks ) {
		$snaksFinder = $this->getMockBuilder( SnaksFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$snaksFinder->expects( $this->any() )
			->method( 'findSnaks' )
			->will( $this->returnValue( $snaks ) );

		return $snaksFinder;
	}

	/**
	 * @return PropertyIdResolver
	 */
	private function getPropertyIdResolver() {
		$propertyIdResolver = $this->getMockBuilder( PropertyIdResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyIdResolver->expects( $this->any() )
			->method( 'resolvePropertyId' )
			->will( $this->returnValue( new PropertyId( 'P1337' ) ) );

		return $propertyIdResolver;
	}

	/**
	 * @return PropertyIdResolver
	 */
	private function getPropertyIdResolverForPropertyNotFound() {
		$propertyIdResolver = $this->getMockBuilder( PropertyIdResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyIdResolver->expects( $this->any() )
			->method( 'resolvePropertyId' )
			->will( $this->returnCallback( function( $propertyLabelOrId, $languageCode ) {
				throw new PropertyLabelNotResolvedException( $propertyLabelOrId, $languageCode );
			} )
		);

		return $propertyIdResolver;
	}

	private function getEntityLookup() {
		return new RevisionBasedEntityLookup( $this->getEntityRevisionLookup() );
	}

	/**
	 * @return \Wikibase\Edrsf\EntityRevisionLookup
	 */
	private function getEntityRevisionLookup() {
		$lookup = $this->getMock( EntityRevisionLookup::class );

		$lookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				switch ( $entityId->getSerialization() ) {
					case 'Q42':
						return new EntityRevision( new Item( new ItemId( 'Q42' ) ) );
					case 'Q43':
						throw new RevisionedUnresolvedRedirectException(
							$entityId,
							new ItemId( 'Q404' )
						);
					default:
						return null;
				}
			} )
		);

		return $lookup;
	}

	/**
	 * @param string $format One of the SnakFormatter::FORMAT_… constants
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter( $format ) {
		$snakFormatter = $this->getMock( SnakFormatter::class );

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnCallback(
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
			) );

		$snakFormatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( $format ) );

		return $snakFormatter;
	}

}
