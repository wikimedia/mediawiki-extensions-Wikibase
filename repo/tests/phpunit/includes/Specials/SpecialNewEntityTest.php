<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use RequestContext;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0+
 */
abstract class SpecialNewEntityTest extends SpecialPageTestBase {

	use HtmlAssertionHelpers;

	protected function setUp() {
		parent::setUp();

		$this->setUserLang( 'en' );
	}

	/**
	 * @dataProvider provideValidEntityCreationRequests
	 */
	public function testEntityIsBeingCreated_WhenValidInputIsGiven( array $formData ) {
		$formData['wpEditToken'] = RequestContext::getMain()->getUser()->getEditToken();
		$request = new FauxRequest( $formData, true );

		/** @var \FauxResponse $webResponse */
		list( , $webResponse ) = $this->executeSpecialPage( '', $request );

		$entityId = $this->extractEntityIdFromUrl( $webResponse->getHeader( 'location' ) );
		/* @var $entity EntityDocument */
		$entity = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $entityId );

		$this->assertEntityMatchesFormData( $formData, $entity );
	}

	/**
	 * Data provider method
	 *
	 * @return array[][]
	 */
	abstract public function provideValidEntityCreationRequests();

	/**
	 * @param string $url
	 * @return EntityId
	 */
	abstract protected function extractEntityIdFromUrl( $url );

	/**
	 * @param array $form
	 * @param EntityDocument $entity
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
