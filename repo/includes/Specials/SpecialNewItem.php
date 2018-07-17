<?php

namespace Wikibase\Repo\Specials;

use OutputPage;
use SiteLookup;
use Status;
use WebRequest;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Term\Term;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Specials\HTMLForm\HTMLAliasesField;
use Wikibase\Repo\Specials\HTMLForm\HTMLTrimmedTextField;
use Wikibase\Repo\Specials\HTMLForm\HTMLContentLanguageField;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Page for creating new Wikibase items.
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialNewItem extends SpecialNewEntity {

	const FIELD_LANG = 'lang';
	const FIELD_LABEL = 'label';
	const FIELD_DESCRIPTION = 'description';
	const FIELD_ALIASES = 'aliases';
	const FIELD_SITE = 'site';
	const FIELD_PAGE = 'page';

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var TermValidatorFactory
	 */
	private $termValidatorFactory;

	public function __construct(
		SpecialPageCopyrightView $copyrightView,
		EntityNamespaceLookup $entityNamespaceLookup,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		EditEntityFactory $editEntityFactory,
		SiteLookup $siteLookup,
		TermValidatorFactory $termValidatorFactory
	) {
		parent::__construct(
			'NewItem',
			'createpage',
			$copyrightView,
			$entityNamespaceLookup,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory
		);
		$this->siteLookup = $siteLookup;
		$this->termValidatorFactory = $termValidatorFactory;
	}

	/**
	 * @see SpecialNewEntity::doesWrites
	 *
	 * @return bool
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * @param WebRequest $request
	 *
	 * @return bool
	 */
	private function isSiteLinkProvided( WebRequest $request ) {
		return $request->getVal( self::FIELD_SITE ) !== null
			   && $request->getVal( self::FIELD_PAGE ) !== null;
	}

	/**
	 * @see SpecialNewEntity::createEntityFromFormData
	 *
	 * @param array $formData
	 *
	 * @return Item
	 */
	protected function createEntityFromFormData( array $formData ) {
		$languageCode = $formData[ self::FIELD_LANG ];

		$item = new Item();
		$item->setLabel( $languageCode, $formData[ self::FIELD_LABEL ] );
		$item->setDescription( $languageCode, $formData[ self::FIELD_DESCRIPTION ] );

		$item->setAliases( $languageCode, $formData[ self::FIELD_ALIASES ] );

		if ( isset( $formData[ self::FIELD_SITE ] ) ) {
			$site = $this->siteLookup->getSite( $formData[ self::FIELD_SITE ] );
			$normalizedPageName = $site->normalizePageName( $formData[ self::FIELD_PAGE ] );

			$item->getSiteLinkList()->addNewSiteLink( $site->getGlobalId(), $normalizedPageName );
		}

		return $item;
	}

	/**
	 * @return array[]
	 */
	protected function getFormFields() {
		$formFields = [
			self::FIELD_LANG => [
				'name' => self::FIELD_LANG,
				'class' => HTMLContentLanguageField::class,
				'id' => 'wb-newentity-language',
			],
			self::FIELD_LABEL => [
				'name' => self::FIELD_LABEL,
				'default' => isset( $this->parts[0] ) ? $this->parts[0] : '',
				'class' => HTMLTrimmedTextField::class,
				'id' => 'wb-newentity-label',
				'placeholder-message' => 'wikibase-label-edit-placeholder',
				'label-message' => 'wikibase-newentity-label',
			],
			self::FIELD_DESCRIPTION => [
				'name' => self::FIELD_DESCRIPTION,
				'default' => isset( $this->parts[1] ) ? $this->parts[1] : '',
				'class' => HTMLTrimmedTextField::class,
				'id' => 'wb-newentity-description',
				'placeholder-message' => 'wikibase-description-edit-placeholder',
				'label-message' => 'wikibase-newentity-description',
			],
			self::FIELD_ALIASES => [
				'name' => self::FIELD_ALIASES,
				'class' => HTMLAliasesField::class,
				'id' => 'wb-newentity-aliases',
			],
		];

		if ( $this->isSiteLinkProvided( $this->getRequest() ) ) {
			$formFields[ self::FIELD_SITE ] = [
				'name' => self::FIELD_SITE,
				'default' => $this->getRequest()->getVal( self::FIELD_SITE ),
				'type' => 'text',
				'id' => 'wb-newitem-site',
				'readonly' => 'readonly',
				'validation-callback' => function ( $siteId, $formData ) {
					$site = $this->siteLookup->getSite( $siteId );

					if ( $site === null ) {
						return [ $this->msg( 'wikibase-newitem-not-recognized-siteid' )->text() ];
					}

					return true;
				},
				'label-message' => 'wikibase-newitem-site'
			];

			$formFields[ self::FIELD_PAGE ] = [
				'name' => self::FIELD_PAGE,
				'default' => $this->getRequest()->getVal( self::FIELD_PAGE ),
				'type' => 'text',
				'id' => 'wb-newitem-page',
				'readonly' => 'readonly',
				'validation-callback' => function ( $pageName, $formData ) {
					$siteId = $formData['site'];
					$site = $this->siteLookup->getSite( $siteId );
					if ( $site === null ) {
						return true;
					}

					$normalizedPageName = $site->normalizePageName( $pageName );
					if ( $normalizedPageName === false ) {
						return [
							$this->msg(
								'wikibase-newitem-no-external-page',
								$siteId,
								$pageName
							)->text(),
						];
					}

					return true;
				},
				'label-message' => 'wikibase-newitem-page'
			];
		}

		return $formFields;
	}

	/**
	 * @see SpecialNewEntity::getLegend
	 *
	 * @return string
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-newitem-fieldset' );
	}

	/**
	 * @see SpecialNewEntity::getWarnings
	 *
	 * @return string[]
	 */
	protected function getWarnings() {
		if ( $this->getUser()->isAnon() ) {
			return [
				$this->msg( 'wikibase-anonymouseditwarning', $this->msg( 'wikibase-entity-item' ) ),
			];
		}

		return [];
	}

	/**
	 * @param array $formData
	 *
	 * @return Status
	 */
	protected function validateFormData( array $formData ) {
		if ( $formData[ self::FIELD_LABEL ] == ''
			 && $formData[ self::FIELD_DESCRIPTION ] == ''
			 && $formData[ self::FIELD_ALIASES ] === []
		) {
			return Status::newFatal( 'wikibase-newitem-insufficient-data' );
		}

		if ( $formData[self::FIELD_LABEL] != '' ) {
			$validator = $this->termValidatorFactory->getLabelValidator( $this->getEntityType() );
			$result = $validator->validate( $formData[self::FIELD_LABEL] );
			if ( !$result->isValid() ) {
				return $this->createStatusFromValidatorError( $result->getErrors()[0] );
			}
		}

		if ( $formData[self::FIELD_DESCRIPTION] != '' ) {
			$validator = $this->termValidatorFactory->getDescriptionValidator();
			$result = $validator->validate( $formData[self::FIELD_DESCRIPTION] );
			if ( !$result->isValid() ) {
				return $this->createStatusFromValidatorError( $result->getErrors()[0] );
			}
		}

		if ( $formData[self::FIELD_ALIASES] !== [] ) {
			$validator = $this->termValidatorFactory->getAliasValidator( $this->getEntityType() );
			foreach ( $formData[self::FIELD_ALIASES] as $alias ) {
				$result = $validator->validate( $alias );
				if ( ! $result->isValid() ) {
					return $this->createStatusFromValidatorError( $result->getErrors()[0] );
				}
			}

			$result = $validator->validate( implode( '|', $formData[self::FIELD_ALIASES] ) );
			if ( !$result->isValid() ) {
				return $this->createStatusFromValidatorError( $result->getErrors()[0] );
			}
		}

		return Status::newGood();
	}

	private function createStatusFromValidatorError( $error ) {
		$params = array_merge( [ 'wikibase-validator-' . $error->getCode() ],  $error->getParameters() );
		return call_user_func_array( 'Status::newFatal', $params );
	}

	/**
	 * @param Item $item
	 *
	 * @return Summary
	 */
	protected function createSummary( EntityDocument $item ) {
		$uiLanguageCode = $this->getLanguage()->getCode();

		$summary = new Summary( 'wbeditentity', 'create' );
		$summary->setLanguage( $uiLanguageCode );
		/** @var Term|null $labelTerm */
		$labelTerm = $item->getLabels()->getIterator()->current();
		/** @var Term|null $descriptionTerm */
		$descriptionTerm = $item->getDescriptions()->getIterator()->current();
		$summary->addAutoSummaryArgs(
			$labelTerm ? $labelTerm->getText() : '',
			$descriptionTerm ? $descriptionTerm->getText() : ''
		);

		return $summary;
	}

	protected function displayBeforeForm( OutputPage $output ) {
		parent::displayBeforeForm( $output );
		$output->addModules( 'wikibase.special.languageLabelDescriptionAliases' );
	}

	/**
	 * @see SpecialNewEntity::getEntityType
	 */
	protected function getEntityType() {
		return Item::ENTITY_TYPE;
	}

}
