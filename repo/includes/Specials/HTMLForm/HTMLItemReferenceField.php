<?php

namespace Wikibase\Repo\Specials\HTMLForm;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * Class representing generic form field referencing item by its id
 *
 * @license GPL-2.0+
 */
class HTMLItemReferenceField extends \HTMLTextField {
	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * - Can be used without placeholder - has some predefined value.
	 * - Doesn't accept `type` parameter.
	 *
	 * @inheritdoc
	 *
	 * @see \HTMLForm There is detailed description of the allowed $params (named $info there).
	 */
	public function __construct( array $params, EntityLookup $entityLookup = null ) {
		if ( isset( $params['type'] ) ) {
			throw new \InvalidArgumentException( "Cannot use `type` for item reference field" );
		}

		$defaultValues = [ 'placeholder-message' => 'wikibase-item-reference-edit-placeholder' ];

		// TODO Placeholder message

		$params['type'] = 'text';
		parent::__construct( array_merge( $defaultValues, $params ) );

		$this->entityLookup = $entityLookup ?: WikibaseRepo::getDefaultInstance()->getEntityLookup();
	}

	public function validate( $value, $alldata ) {
		$required = isset( $this->mParams['required'] ) && $this->mParams['required'] !== false;

		if ( !$required && $value === '' ) {
			return true;
		}

		if ( !preg_match( ItemId::PATTERN, $value ) ) {
			// FIXME add text in language files
			return $this->msg( 'wikibase-item-reference-edit-invalid-format' );
		}

		if ( !$this->entityLookup->hasEntity( new ItemId( $value ) ) ) {
			// FIXME add text in language files
			return $this->msg( 'wikibase-item-reference-edit-nonexistent-item' );
		}

		return parent::validate( $value, $alldata );
	}

}
