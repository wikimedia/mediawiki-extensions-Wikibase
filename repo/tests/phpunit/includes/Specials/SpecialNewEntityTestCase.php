<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use RequestContext;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\SpecialNewEntity
 *
 * @license GPL-2.0-or-later
 */
abstract class SpecialNewEntityTestCase extends SpecialPageTestBase {

	use HtmlAssertionHelpers;

	/**
	 * @var SpecialPageCopyrightView|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $copyrightView;

	protected function setUp() {
		parent::setUp();

		$this->setUserLang( 'en' );

		$this->copyrightView = $this->getMockBuilder( SpecialPageCopyrightView::class )
			->disableOriginalConstructor()
			->getMock();
	}

	//TODO: Add test checking copyright rendering

	/**
	 * @dataProvider provideValidEntityCreationRequests
	 */
	public function testEntityIsBeingCreated_WhenValidInputIsGiven( array $formData ) {
		$formData['wpEditToken'] = RequestContext::getMain()->getUser()->getEditToken();
		$request = new FauxRequest( $formData, true );

		/** @var \FauxResponse $webResponse */
		list( , $webResponse ) = $this->executeSpecialPage( '', $request );

		$entityId = $this->extractEntityIdFromUrl( $webResponse->getHeader( 'location' ) );
		$entity = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $entityId );

		$this->assertEntityMatchesFormData( $formData, $entity );
	}

	/**
	 * Data provider method
	 *
	 * @return array[]
	 */
	abstract public function provideValidEntityCreationRequests();

	/**
	 * @param string $url
	 *
	 * @return EntityId
	 */
	abstract protected function extractEntityIdFromUrl( $url );

	/**
	 * @param array $form
	 * @param EntityDocument $entity
	 *
	 * @return void
	 * @throws \Exception
	 */
	abstract protected function assertEntityMatchesFormData( array $form, EntityDocument $entity );

	/**
	 * @dataProvider provideInvalidEntityCreationRequests
	 * @param array $formData
	 * @param string $errorMessageText
	 */
	public function testErrorBeingDisplayed_WhenInvalidInputIsGiven(
		array $formData,
		$errorMessageText
	) {
		$formData['wpEditToken'] = RequestContext::getMain()->getUser()->getEditToken();
		$request = new FauxRequest( $formData, true );

		/** @var \FauxResponse $webResponse */
		list( $html ) = $this->executeSpecialPage( '', $request );

		$this->assertHtmlContainsErrorMessage( $html, $errorMessageText );
	}

	/**
	 * Data provider method
	 *
	 * @return array[]
	 */
	abstract public function provideInvalidEntityCreationRequests();

}
