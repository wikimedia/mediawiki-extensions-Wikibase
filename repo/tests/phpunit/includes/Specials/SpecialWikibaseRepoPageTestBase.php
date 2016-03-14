<?php

namespace Wikibase\Repo\Tests\Specials;

use DataValues\DataValue;
use HashSiteStore;
use Language;
use SiteStore;
use SpecialPageTestBase;
use Status;
use TestSites;
use Title;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\PlainEntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\SummaryFormatter;
use Wikibase\Test\MockRepository;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
abstract class SpecialWikibaseRepoPageTestBase extends SpecialPageTestBase {

	/**
	 * @var MockRepository
	 */
	protected $mockRepository;

	protected function setUp() {
		parent::setUp();

		$this->mockRepository = new MockRepository();
	}

	protected function getSummaryFormatter() {
		return new SummaryFormatter(
			$this->getIdFormatter(),
			$this->getValueFormatter(),
			$this->getSnakFormatter(),
			$this->getLanguage(),
			$this->getIdParser()
		);
	}

	/**
	 * @return EntityRevisionLookup
	 */
	protected function getEntityRevisionLookup() {
		return $this->mockRepository;
	}

	/**
	 * @return EntityTitleLookup
	 */
	protected function getEntityTitleLookup() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::makeTitle( NS_MAIN, $id->getEntityType() . ':' . $id->getSerialization() );
			} ) );

		return $titleLookup;
	}

	/**
	 * @return EntityStore
	 */
	protected function getEntityStore() {
		return $this->mockRepository;
	}

	/**
	 * @return EntityPermissionChecker
	 */
	protected function getEntityPermissionChecker() {
		$permissionChecker = $this->getMock( 'Wikibase\Repo\Store\EntityPermissionChecker' );

		$ok = Status::newGood();

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntity' )
			->will( $this->returnValue( $ok ) );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnValue( $ok ) );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityType' )
			->will( $this->returnValue( $ok ) );

		return $permissionChecker;
	}

	/**
	 * @return SiteStore
	 */
	protected function getSiteStore() {
		return new HashSiteStore( TestSites::getSites() );
	}

	/**
	 * @return EntityIdFormatter
	 */
	protected function getIdFormatter() {
		return new PlainEntityIdFormatter();
	}

	/**
	 * @return ValueFormatter
	 */
	protected function getValueFormatter() {
		$formatter = $this->getMock( 'ValueFormatters\ValueFormatter' );

		$formatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnCallback( array( $this, 'formatValueAsText' ) ) );

		return $formatter;
	}

	/**
	 * @return SnakFormatter
	 */
	protected function getSnakFormatter() {
		$formatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$formatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnCallback( array( $this, 'formatSnakAsText' ) ) );

		$formatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( 'text/plain' ) );

		return $formatter;
	}

	public function formatSnakAsText( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak ) {
			return $this->formatValueAsText( $snak->getDataValue() );
		} else {
			return $snak->getType();
		}
	}

	public function formatValueAsText( DataValue $value ) {
		return print_r( $value->getValue(), true );
	}

	/**
	 * @return Language
	 */
	protected function getLanguage() {
		return Language::factory( 'qqx' );
	}

	/**
	 * @return EntityIdParser
	 */
	protected function getIdParser() {
		return new BasicEntityIdParser();
	}

}
