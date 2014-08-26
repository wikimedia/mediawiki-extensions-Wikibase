<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use DOMDocument;
use Title;
use ValueFormatters\FormatterOptions;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\View\SnakHtmlGenerator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\View\ClaimsView
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @group Database
 *		^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ClaimsViewTest extends \MediaWikiLangTestCase {

	public function getTitleForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getPrefixedId();
		return Title::makeTitle( NS_MAIN, $name );
	}

	public function getHtmlProvider() {
		$claims = array(
			$this->makeClaim( new PropertyNoValueSnak(
				new PropertyId( 'P11' )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P11' ),
				new EntityIdValue( new ItemId( 'Q22' ) )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P23' ),
				new StringValue( 'test' )
			) ),
		);

		return array(
			array( $claims )
		);
	}

	/**
	 * @dataProvider getHtmlProvider
	 *
	 * @param Claim[] $claims
	 */
	public function testGetHtml( array $claims ) {
		$claimsView = $this->newClaimsView();

		// Using a DOM document to parse HTML output:
		$doc = new DOMDocument();

		// Disable default error handling in order to catch warnings caused by malformed markup:
		libxml_use_internal_errors( true );

		// Try loading the HTML:
		$this->assertTrue( $doc->loadHTML( $claimsView->getHtml( $claims ) ) );

		// Check if no warnings have been thrown:
		$errorString = '';
		foreach( libxml_get_errors() as $error ) {
			$errorString .= "\r\n" . $error->message;
		}

		$this->assertEmpty( $errorString, 'Malformed markup:' . $errorString );

		// Clear error cache and re-enable default error handling:
		libxml_clear_errors();
		libxml_use_internal_errors();
	}

	/**
	 * @return ClaimsView
	 */
	private function newClaimsView() {
		$formatterOptions = new FormatterOptions();
		$snakFormatter = WikibaseRepo::getDefaultInstance()->getSnakFormatterFactory()
			->getSnakFormatter( SnakFormatter::FORMAT_HTML_WIDGET, $formatterOptions );

		$entityTitleLookup = $this->getEntityTitleLookupMock();

		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$entityTitleLookup
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator,
			$entityTitleLookup
		);

		$mockRepo = $this->getMockRepo();

		$sectionEditLinkGenerator = new SectionEditLinkGenerator();

		return new ClaimsView(
			$mockRepo,
			$entityTitleLookup,
			$sectionEditLinkGenerator,
			$claimHtmlGenerator,
			'en'
		);
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookupMock() {
		$lookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

	/**
	 * @return MockRepository
	 */
	private function getMockRepo() {
		static $mockRepo;

		if ( !isset( $mockRepo ) ) {
			$mockRepo = new MockRepository();

			$mockRepo->putEntity( $this->makeItem( 'Q33' ) );
			$mockRepo->putEntity( $this->makeItem( 'Q22' ) );
			$mockRepo->putEntity( $this->makeItem( 'Q23' ) );
			$mockRepo->putEntity( $this->makeItem( 'Q24' ) );

			$mockRepo->putEntity( $this->makeProperty( 'P11', 'wikibase-item' ) );
			$mockRepo->putEntity( $this->makeProperty( 'P23', 'string' ) );
			$mockRepo->putEntity( $this->makeProperty( 'P42', 'url' ) );
			$mockRepo->putEntity( $this->makeProperty( 'P44', 'wikibase-item' ) );
		}

		return $mockRepo;
	}

	private function makeItem( $id, $claims = array() ) {
		if ( is_string( $id ) ) {
			$id = new ItemId( $id );
		}

		$item = Item::newEmpty();
		$item->setId( $id );
		$item->setLabel( 'en', "label:$id" );
		$item->setDescription( 'en', "description:$id" );

		foreach ( $claims as $claim ) {
			$item->addClaim( $claim );
		}

		return $item;
	}

	private function makeProperty( $id, $dataTypeId, $claims = array() ) {
		if ( is_string( $id ) ) {
			$id = new PropertyId( $id );
		}

		$property = Property::newFromType( $dataTypeId );
		$property->setId( $id );

		$property->setLabel( 'en', "label:$id" );
		$property->setDescription( 'en', "description:$id" );

		foreach ( $claims as $claim ) {
			$property->addClaim( $claim );
		}

		return $property;
	}

	protected function makeClaim( Snak $mainSnak, $guid = null ) {
		static $guidCounter = 0;

		if ( $guid === null ) {
			$guidCounter++;
			$guid = 'EntityViewTest$' . $guidCounter;
		}

		$claim = new Claim( $mainSnak );
		$claim->setGuid( $guid );

		return $claim;
	}

}
