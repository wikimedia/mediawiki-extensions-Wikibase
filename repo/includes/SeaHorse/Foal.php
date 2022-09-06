<?php

namespace Wikibase\Repo\SeaHorse;

use SpecialPage;
use Status;
use HTMLForm;
use Wikibase\Repo\Specials\HTMLForm\HTMLTrimmedTextField;

class Foal extends SpecialPage {

	public static function factory() {
		return new self();
	}

	public function __construct() {
		parent::__construct( 'Foal' );
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$form = $this->createForm();
		$form->prepareForm();

		/** @var Status|false $submitStatus `false` if form was not submitted */
		$submitStatus = $form->tryAuthorizedSubmit();

		if ( $submitStatus && $submitStatus->isGood() ) {
			$this->getOutput()->redirect( (\Title::newFromText( 'Special:RecentChanges' ))->getFullURL() );
			return;
		}

		$form->displayForm( $submitStatus ?: Status::newGood() );
	}

		/**
	 * @return HTMLForm
	 */
	private function createForm() {
		return HTMLForm::factory( 'ooui',
		[
			'content' => [
				'name' => 'content',
				'class' => HTMLTrimmedTextField::class,
				'id' => 'wb-newentity-content',
			]],
			$this->getContext()
			)
			->setId( 'mw-newentity-form1' )
			->setSubmitID( 'wb-newentity-submit' )
			->setSubmitName( 'submit' )
			->setSubmitTextMsg( 'wikibase-newentity-submit' )
			->setSubmitCallback(
				function ( $data, HTMLForm $form ) {
					$validationStatus = $this->validateFormData( $data );
					if ( !$validationStatus->isGood() ) {
						return $validationStatus;
					}

					$entity = new SeaHorse( new SeaHorseId(bin2hex(random_bytes(16))), $data['content'] );
					$summary = "Neigh";

					$entityRevision = \Wikibase\Repo\WikibaseRepo::getEntityStore()->saveEntity(
						$entity,
						$summary,
						$this->getContext()->getUser(),
						EDIT_NEW
					);

					return Status::newGood( $entity );
				}
			);
	}

	protected function validateFormData( array $formData ) {
		$status = Status::newGood();
		if ( $formData[ 'content' ] == '') {
			$status->fatal( 'insufficient-data' );
		}
		return $status;
	}

}
