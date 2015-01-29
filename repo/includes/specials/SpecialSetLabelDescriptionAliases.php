<?php

namespace Wikibase\Repo\Specials;

use Html;
use InvalidArgumentException;
use Language;
use PermissionsError;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\Utils;

/**
 * Special page for setting label, description and aliases of a Wikibase Entity that features a
 * Fingerprint.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Bene* < benestar.wikimedia@googlemail.com >
 * @author Thiemo Mättig
 */
class SpecialSetLabelDescriptionAliases extends SpecialModifyEntity {

	/**
	 * @var FingerprintChangeOpFactory
	 */
	protected $fingerprintChangeOpFactory;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var string|null
	 */
	private $label = null;

	/**
	 * @var string|null
	 */
	private $description = null;

	/**
	 * @var string[]
	 */
	private $aliases = array();

	public function __construct() {
		parent::__construct( 'SetLabelDescriptionAliases', 'edit' );

		$changeOpFactoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();
		$this->fingerprintChangeOpFactory
			= $changeOpFactoryProvider->getFingerprintChangeOpFactory();
	}

	/**
	 * @see SpecialModifyEntity::validateInput
	 *
	 * @return bool
	 */
	protected function validateInput() {
		if ( !parent::validateInput()
			|| !$this->isValidLanguageCode( $this->languageCode )
			|| !$this->entityRevision
		) {
			return false;
		}

		try {
			$this->checkTermChangePermissions( $this->entityRevision->getEntity() );
		} catch ( PermissionsError $e ) {
			$this->showErrorHTML( $this->msg( 'permissionserrors' ) . ': ' . $e->permission );
			return false;
		}

		$entity = $this->entityRevision->getEntity();
		return $entity instanceof FingerprintProvider;
	}

	/**
	 * @param Entity $entity
	 *
	 * @throws PermissionsError
	 * @throws InvalidArgumentException
	 */
	private function checkTermChangePermissions( Entity $entity ) {
		if ( $entity instanceof Item ) {
			$type = 'item';
		} else if ( $entity instanceof Property ) {
			$type = 'property';
		} else {
			throw new InvalidArgumentException( 'Unexpected Entity type when checking special page term change permissions' );
		}
		$restriction = $type . '-term';
		if ( !$this->getUser()->isAllowed( $restriction ) ) {
			throw new PermissionsError( $restriction );
		}
	}

	/**
	 * @see SpecialModifyEntity::getFormElements()
	 *
	 * @param Entity $entity
	 * @return string
	 */
	protected function getFormElements( Entity $entity = null ) {
		if ( $this->languageCode === null ) {
			$this->languageCode = $this->getLanguage()->getCode();
		}

		$label = $this->getRequest()->getVal( 'label' );
		$description = $this->getRequest()->getVal( 'description' );
		$aliases = $this->getRequest()->getVal( 'aliases' );

		$fingerprint = $entity instanceof FingerprintProvider
			? $entity->getFingerprint()
			: new Fingerprint();

		if ( $label === null && $fingerprint->hasLabel( $this->languageCode ) ) {
			$label = $fingerprint->getLabel( $this->languageCode )->getText();
		}

		if ( $description === null && $fingerprint->hasDescription( $this->languageCode ) ) {
			$description = $fingerprint->getDescription( $this->languageCode )->getText();
		}

		if ( $aliases === null && $fingerprint->hasAliasGroup( $this->languageCode ) ) {
			$aliases = implode( '|', $fingerprint->getAliasGroup( $this->languageCode )->getAliases() );
		}

		$languageName = Language::fetchLanguageName(
			$this->languageCode, $this->getLanguage()->getCode()
		);

		if ( $entity !== null && $this->languageCode !== null && $languageName !== '' ) {
			$html = Html::rawElement(
				'p',
				array(),
				$this->msg(
					'wikibase-setlabeldescriptionaliases-introfull',
					$this->getEntityTitle( $entity->getId() )->getPrefixedText(),
					$languageName
				)->parse()
			)
			. Html::input( 'language', $this->languageCode, 'hidden' )
			. Html::input( 'id', $entity->getId()->getSerialization(), 'hidden' );
		} else {
			$html = Html::rawElement(
				'p',
				array(),
				$this->msg( 'wikibase-setlabeldescriptionaliases-intro' )->parse()
			)
			. parent::getFormElements( $entity )
			. Html::element(
				'label',
				array(
					'for' => 'wikibase-setlabeldescriptionaliases-language',
					'class' => 'wb-label'
				),
				$this->msg( 'wikibase-modifyterm-language' )->text()
			)
			. Html::input(
				'language',
				$this->languageCode,
				'text',
				array(
					'class' => 'wb-input',
					'id' => 'wikibase-setlabeldescriptionaliases-language'
				)
			);
		}

		$html .= $this->getLabel( 'label' )
			. $this->getTextInput( 'label', $label )
			. $this->getLabel( 'description' )
			. $this->getTextInput( 'description', $description )
			. $this->getLabel( 'aliases' )
			. $this->getTextInput( 'aliases', $aliases );

		return $html;
	}

	/**
	 * Returns HTML text input element for a specific term (label, description, aliases).
	 *
	 * @param string $name
	 * @param string $value Text to fill the input element with
	 * @return string
	 */
	private function getTextInput( $name, $value ) {
		return Html::input(
			$name,
			$value,
			'text',
			array(
				'class' => 'wb-input',
				'id' => 'wikibase-setlabeldescriptionaliases-' . $name,
				'size' => 50
			)
		)
		. Html::element( 'br' );
	}

	/**
	 * Returns a HTML label element for a specific term (label, description, aliases).
	 *
	 * @param string $name
	 * @return string
	 */
	private function getLabel( $name ) {
		return Html::element( 'br' )
		. Html::element(
			$name,
			array(
				'for' => 'wikibase-setlabeldescriptionaliases-' . $name,
				'class' => 'wb-label'
			),
			// Messages:
			// wikibase-setlabeldescriptionaliases-label-label
			// wikibase-setlabeldescriptionaliases-description-label
			// wikibase-setlabeldescriptionaliases-aliases-label
			$this->msg( 'wikibase-setlabeldescriptionaliases-' . $name . '-label' )->text()
		);
	}

	/**
	 * @see SpecialModifyEntity::prepareArguments()
	 *
	 * @param string $subPage
	 */
	protected function prepareArguments( $subPage ) {
		parent::prepareArguments( $subPage );

		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );

		$this->languageCode = $request->getVal( 'language', isset( $parts[1] ) ? $parts[1] : '' );

		if ( $this->languageCode === '' ) {
			$this->languageCode = null;
		}

		if ( $this->languageCode !== null && !$this->isValidLanguageCode( $this->languageCode ) ) {
			$errorMessage = $this->msg(
				'wikibase-wikibaserepopage-invalid-langcode',
				$this->languageCode
			)->parse();

			$this->showErrorHTML( $errorMessage );
		}

		$this->label = $request->getVal( 'label' );
		$this->description = $request->getVal( 'description' );
		$this->aliases = explode( '|', $request->getVal( 'aliases' ) );

		if ( $this->entityRevision !== null ) {
			$entity = $this->entityRevision->getEntity();

			if ( $entity instanceof FingerprintProvider ) {
				$fingerprint = $entity->getFingerprint();

				if ( $this->label === null && $fingerprint->hasLabel( $this->languageCode ) ) {
					$this->label = $fingerprint->getLabel( $this->languageCode )->getText();
				}

				if ( $this->description === null && $fingerprint->hasLabel( $this->languageCode ) ) {
					$this->description = $fingerprint->getDescription( $this->languageCode )->getText();
				}

				if ( $this->aliases === null && $fingerprint->hasLabel( $this->languageCode ) ) {
					$this->aliases = $fingerprint->getAliasGroup( $this->languageCode )->getAliases();
				}
			}
		}
	}

	/**
	 * Checks if the language code is valid.
	 *
	 * @param $languageCode string the language code
	 *
	 * @return bool
	 */
	private function isValidLanguageCode( $languageCode ) {
		return $languageCode !== null
			&& Language::isValidBuiltInCode( $languageCode )
			&& in_array( $languageCode, Utils::getLanguageCodes() );
	}

	/**
	 * @see SpecialModifyEntity::modifyEntity()
	 *
	 * @param Entity $entity
	 * @return Summary[]|bool
	 */
	protected function modifyEntity( Entity $entity ) {
		$changeOps = array();

		$changeOps[] = $this->fingerprintChangeOpFactory->newSetLabelOp(
			$this->languageCode,
			$this->label
		);
		$changeOps[] = $this->fingerprintChangeOpFactory->newSetDescriptionOp(
			$this->languageCode,
			$this->description
		);

		$changeOps[] = $this->fingerprintChangeOpFactory->newSetAliasesOp(
			$this->languageCode,
			$this->aliases
		);

		$success = true;

		foreach ( $changeOps as $changeOp ) {
			try {
				$this->applyChangeOp( $changeOp, $entity );
			} catch ( ChangeOpException $e ) {
				$this->showErrorHTML( $e->getMessage() );
				$success = false;
			}
		}

		if ( !$success ) {
			return false;
		}

		return $this->getSummary( 'wbeditentity' );
	}

}
