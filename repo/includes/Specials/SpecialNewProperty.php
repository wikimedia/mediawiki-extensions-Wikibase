<?php

namespace Wikibase\Repo\Specials;

use OutputPage;
use Status;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataTypeSelector;
use Wikibase\Repo\Specials\HTMLForm\HTMLAliasesField;
use Wikibase\Repo\Specials\HTMLForm\HTMLTrimmedTextField;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Page for creating new Wikibase properties.
 *
 * @since 0.2
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

	/**
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct( 'NewProperty', 'property-create' );
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

		$fingerprint = $property->getFingerprint();
		$fingerprint->setLabel( $languageCode, $formData[ self::FIELD_LABEL ] );
		$fingerprint->setDescription( $languageCode, $formData[ self::FIELD_DESCRIPTION ] );

		$fingerprint->setAliasGroup( $languageCode, $formData[ self::FIELD_ALIASES ] );

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
		$langCode = $this->getLanguage()->getCode();

		$formFields = [
			self::FIELD_LANG => [
				'name' => self::FIELD_LANG,
				'options' => $this->getLanguageOptions(),
				'default' => $langCode,
				'type' => 'combobox',
				'id' => 'wb-newentity-language',
				'filter-callback' => [ $this->stringNormalizer, 'trimToNFC' ],
				'validation-callback' => function ( $language ) {
					if ( !in_array( $language, $this->languageCodes ) ) {
						return [ $this->msg( 'wikibase-newitem-not-recognized-language' )->text() ];
					}

					return true;
				},
				'label-message' => 'wikibase-newentity-language'
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

		$formFields[ self::FIELD_DATATYPE ] = [
			'name' => self::FIELD_DATATYPE,
			'type' => 'select',
			'default' => isset( $this->parts[2] ) ? $this->parts[2] : 'string',
			'options' => $selector->getOptionsArray(),
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
	 * @see SpecialCreateEntity::getWarnings
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
	protected function createSummary( $property ) {
		$uiLanguageCode = $this->getLanguage()->getCode();

		$summary = new Summary( 'wbeditentity', 'create' );
		$summary->setLanguage( $uiLanguageCode );
		/** @var Term|null $labelTerm */
		$labelTerm = $property->getFingerprint()->getLabels()->getIterator()->current();
		/** @var Term|null $descriptionTerm */
		$descriptionTerm = $property->getFingerprint()->getDescriptions()->getIterator()->current();
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

}
