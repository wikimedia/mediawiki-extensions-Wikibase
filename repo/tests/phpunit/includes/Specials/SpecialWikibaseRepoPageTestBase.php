<?php

namespace Wikibase\Repo\Tests\Specials;

use DataValues\DataValue;
use HashSiteStore;
use Language;
use SiteLookup;
use SpecialPageTestBase;
use Status;
use TestSites;
use Title;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\EntityId\PlainEntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
abstract class SpecialWikibaseRepoPageTestBase extends SpecialPageTestBase {

	/**
	 * @var MockRepository
	 */
	protected $mockRepository;

	protected function setUp(): void {
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
	 * @return EntityTitleStoreLookup
	 */
	protected function getEntityTitleLookup() {
		$titleLookup = $this->createMock( EntityTitleStoreLookup::class );

		$titleLookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				return Title::makeTitle( NS_MAIN, $id->getEntityType() . ':' . $id->getSerialization() );
			} );

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
		$permissionChecker = $this->createMock( EntityPermissionChecker::class );

		$ok = Status::newGood();

		$permissionChecker->method( 'getPermissionStatusForEntity' )
			->willReturn( $ok );

		$permissionChecker->method( 'getPermissionStatusForEntityId' )
			->willReturn( $ok );

		return $permissionChecker;
	}

	/**
	 * @return SiteLookup
	 */
	protected function getSiteLookup() {
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
		$formatter = $this->createMock( ValueFormatter::class );

		$formatter->method( 'format' )
			->willReturnCallback( [ $this, 'formatValueAsText' ] );

		return $formatter;
	}

	/**
	 * @return SnakFormatter
	 */
	protected function getSnakFormatter() {
		$formatter = $this->createMock( SnakFormatter::class );

		$formatter->method( 'formatSnak' )
			->willReturnCallback( [ $this, 'formatSnakAsText' ] );

		$formatter->method( 'getFormat' )
			->willReturn( 'text/plain' );

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
		return $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );
	}

	/**
	 * @return EntityIdParser
	 */
	protected function getIdParser() {
		return new ItemIdParser();
	}

}
