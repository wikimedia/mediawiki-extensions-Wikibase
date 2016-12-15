<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use RequestContext;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\WikibaseRepo;

abstract class SpecialNewEntityTest extends SpecialPageTestBase {

	use HtmlAssertionHelpers;
	/**
	 * @dataProvider validEntityCreationRequests
	 */
	public function testEntityIsBeingCreated_WhenValidInputIsGiven( array $formData ) {
		$formData['wpEditToken'] = RequestContext::getMain()->getUser()->getEditToken();
		$request = new FauxRequest( $formData, true );

		/** @var \FauxResponse $webResponse */
		list( $output, $webResponse ) = $this->executeSpecialPage( '', $request );

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
	abstract public function validEntityCreationRequests();

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
	 * @dataProvider invalidEntityCreationRequests
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
	abstract public function invalidEntityCreationRequests();

}
