<?php

namespace Wikibase\Repo\Specials;

use Html;
use HTMLForm;
use OutputPage;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Page for creating new Wikibase entities that contain a Fingerprint.
 *
 * @license GPL-2.0-or-later
 */
abstract class SpecialNewEntity extends SpecialWikibaseRepoPage {

	/**
	 * Contains pieces of the sub-page name of this special page if a subpage was called.
	 * E.g. [ 'a', 'b' ] in case of 'Special:NewEntity/a/b'
	 * @var string[]|null
	 */
	protected $parts = null;

	/**
	 * @var EntityNamespaceLookup
	 */
	protected $entityNamespaceLookup;

	/**
	 * @param string $name Name of the special page, as seen in links and URLs.
	 * @param string $restriction User right required,
	 * @param SpecialPageCopyrightView $copyrightView
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EditEntityFactory $editEntityFactory
	 */
	public function __construct(
		$name,
		$restriction,
		SpecialPageCopyrightView $copyrightView,
		EntityNamespaceLookup $entityNamespaceLookup,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		EditEntityFactory $editEntityFactory
	) {
		parent::__construct(
			$name,
			$restriction,
			$copyrightView,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory
		);

		$this->entityNamespaceLookup = $entityNamespaceLookup;
	}

	/**
	 * @see SpecialPage::doesWrites
	 *
	 * @return bool
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialPage::isListed()
	 */
	public function isListed() {
		return $this->entityNamespaceLookup->getEntityNamespace( $this->getEntityType() ) !== null;
	}

	/**
	 * @return string Type id of the entity that will be created (eg: Item::ENTITY_TYPE value)
	 */
	abstract protected function getEntityType();

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->checkPermissions();
		$this->checkBlocked();
		$this->checkReadOnly();

		$this->parts = ( $subPage === '' ? [] : explode( '/', $subPage ) );

		$form = $this->createForm();

		$form->prepareForm();

		/** @var Status|false $submitStatus `false` if form was not submitted */
		$submitStatus = $form->tryAuthorizedSubmit();

		if ( $submitStatus && $submitStatus->isGood() ) {
			$this->redirectToEntityPage( $submitStatus->getValue() );

			return;
		}

		$out = $this->getOutput();

		$this->displayBeforeForm( $out );

		$form->displayForm( $submitStatus ?: Status::newGood() );
	}

	/**
	 * @return array[]
	 */
	abstract protected function getFormFields();

	/**
	 * @return string Legend for the fieldset
	 */
	abstract protected function getLegend();

	/**
	 * @return string[] Warnings that should be presented to the user
	 */
	abstract protected function getWarnings();

	/**
	 * @return HTMLForm
	 */
	private function createForm() {
		return HTMLForm::factory( 'ooui', $this->getFormFields(), $this->getContext() )
			->setId( 'mw-newentity-form1' )
			->setSubmitID( 'wb-newentity-submit' )
			->setSubmitName( 'submit' )
			->setSubmitTextMsg( 'wikibase-newentity-submit' )
			->setWrapperLegendMsg( $this->getLegend() )
			->setSubmitCallback(
				function ( $data, HTMLForm $form ) {
					$validationStatus = $this->validateFormData( $data );
					if ( !$validationStatus->isGood() ) {
						return $validationStatus;
					}

					$entity = $this->createEntityFromFormData( $data );

					$summary = $this->createSummary( $entity );

					$this->prepareEditEntity();
					$saveStatus = $this->saveEntity(
						$entity,
						$summary,
						$form->getRequest()->getVal( 'wpEditToken' ),
						EDIT_NEW
					);

					if ( !$saveStatus->isGood() ) {
						return $saveStatus;
					}

					return Status::newGood( $entity );
				}
			);
	}

	/**
	 * @param array $formData
	 *
	 * @return EntityDocument
	 */
	abstract protected function createEntityFromFormData( array $formData );

	/**
	 * @param array $formData
	 *
	 * @return Status
	 */
	abstract protected function validateFormData( array $formData );

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Summary
	 */
	abstract protected function createSummary( EntityDocument $entity );

	protected function displayBeforeForm( OutputPage $output ) {
		$output->addModules( 'wikibase.special.newEntity' );

		$output->addHTML( $this->getCopyrightHTML() );

		foreach ( $this->getWarnings() as $warning ) {
			$output->addHTML( Html::element( 'div', [ 'class' => 'warning' ], $warning ) );
		}
	}

	/**
	 * @param string|null $messageKey ignored here
	 *
	 * @return string HTML
	 */
	protected function getCopyrightHTML( $messageKey = null ) {
		return parent::getCopyrightHTML( 'wikibase-newentity-submit' );
	}

	private function redirectToEntityPage( EntityDocument $entity ) {
		$title = $this->getEntityTitle( $entity->getId() );
		$entityUrl = $title->getFullURL();
		$this->getOutput()->redirect( $entityUrl );
	}

}
