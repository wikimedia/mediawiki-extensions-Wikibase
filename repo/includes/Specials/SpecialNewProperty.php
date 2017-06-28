<?php

namespace Wikibase\Repo\Specials;

use OutputPage;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataTypeSelector;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Specials\HTMLForm\HTMLAliasesField;
use Wikibase\Repo\Specials\HTMLForm\HTMLTrimmedTextField;
use Wikibase\Repo\Specials\HTMLForm\HTMLContentLanguageField;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Page for creating new Wikibase properties.
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialNewProperty extends SpecialNewEntity {
	const FIELD_LANG = 'lang';
	const FIELD_DATATYPE = 'datatype';
	const FIELD_LABEL = 'label';
	const FIELD_DESCRIPTION = 'description';
	const FIELD_ALIASES = 'aliases';

	public function __construct(
		SpecialPageCopyrightView $specialPageCopyrightView,
		EntityNamespaceLookup $entityNamespaceLookup,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		EditEntityFactory $editEntityFactory
	) {
		parent::__construct(
			'NewProperty',
			'property-create',
			$specialPageCopyrightView,
			$entityNamespaceLookup,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory
		);
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
	 * @see SpecialNewEntity::createEntityFromFormData
	 *
	 * @param array $formData
	 *
	 * @return Property
	 */
	protected function createEntityFromFormData( array $formData ) {
		$languageCode = $formData[ self::FIELD_LANG ];

		$property = Property::newFromType( $formData[ self::FIELD_DATATYPE ] );

		$property->setLabel( $languageCode, $formData[ self::FIELD_LABEL ] );
		$property->setDescription( $languageCode, $formData[ self::FIELD_DESCRIPTION ] );

		$property->setAliases( $languageCode, $formData[ self::FIELD_ALIASES ] );

		return $property;
	}

	/**
	 * @param string $dataType
	 *
	 * @return bool
	 */
	private function dataTypeExists( $dataType ) {
		$dataTypeFactory = WikibaseRepo::getDefaultInstance()->getDataTypeFactory();

		return in_array( $dataType, $dataTypeFactory->getTypeIds() );
	}

	/**
	 * @see SpecialNewEntity::getFormFields()
	 *
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
				'label-message' => 'wikibase-newentity-label'
			],
			self::FIELD_DESCRIPTION => [
				'name' => self::FIELD_DESCRIPTION,
				'default' => isset( $this->parts[1] ) ? $this->parts[1] : '',
				'class' => HTMLTrimmedTextField::class,
				'id' => 'wb-newentity-description',
				'placeholder-message' => 'wikibase-description-edit-placeholder',
				'label-message' => 'wikibase-newentity-description'
			],
			self::FIELD_ALIASES => [
				'name' => self::FIELD_ALIASES,
				'class' => HTMLAliasesField::class,
				'id' => 'wb-newentity-aliases',
			]
		];

		$dataTypeFactory = WikibaseRepo::getDefaultInstance()->getDataTypeFactory();
		$selector = new DataTypeSelector(
			$dataTypeFactory->getTypes(),
			$this->getLanguage()->getCode()
		);

		$options = [
			$this->msg( 'wikibase-newproperty-pick-data-type' )->text() => ''
		];
		$formFields[ self::FIELD_DATATYPE ] = [
			'name' => self::FIELD_DATATYPE,
			'type' => 'select',
			'default' => isset( $this->parts[2] ) ? $this->parts[2] : '',
			'options' => array_merge( $options, $selector->getOptionsArray() ),
			'id' => 'wb-newproperty-datatype',
			'validation-callback' => function ( $dataType, $formData, $form ) {
				if ( !$this->dataTypeExists( $dataType ) ) {
					return [ $this->msg( 'wikibase-newproperty-invalid-datatype' )->text() ];
				}

				return true;
			},
			'label-message' => 'wikibase-newproperty-datatype'
		];

		return $formFields;
	}

	/**
	 * @see SpecialNewEntity::getLegend()
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-newproperty-fieldset' );
	}

	/**
	 * @see SpecialNewEntity::getWarnings
	 *
	 * @return string[]
	 */
	protected function getWarnings() {
		if ( $this->getUser()->isAnon() ) {
			return [
				$this->msg(
					'wikibase-anonymouseditwarning',
					$this->msg( 'wikibase-entity-property' )
				),
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
			return Status::newFatal( 'wikibase-newproperty-insufficient-data' );
		}

		return Status::newGood();
	}

	/**
	 * @param Property $property
	 *
	 * @return Summary
	 */
	protected function createSummary( EntityDocument $property ) {
		$uiLanguageCode = $this->getLanguage()->getCode();

		$summary = new Summary( 'wbeditentity', 'create' );
		$summary->setLanguage( $uiLanguageCode );
		/** @var Term|null $labelTerm */
		$labelTerm = $property->getLabels()->getIterator()->current();
		/** @var Term|null $descriptionTerm */
		$descriptionTerm = $property->getDescriptions()->getIterator()->current();
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
		return Property::ENTITY_TYPE;
	}

}
