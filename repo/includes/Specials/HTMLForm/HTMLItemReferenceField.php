<?php

namespace Wikibase\Repo\Specials\HTMLForm;

use Message;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * Class representing generic form field referencing item by its ID.
 *
 * @license GPL-2.0-or-later
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
	 * @inheritDoc
	 *
	 * @see \HTMLForm There is detailed description of the allowed $params (named $info there).
	 */
	public function __construct( array $params, EntityLookup $entityLookup = null ) {
		if ( isset( $params['type'] ) ) {
			throw new \InvalidArgumentException( "Cannot use `type` for item reference field" );
		}

		$defaultValues = [
			'placeholder-message' => 'wikibase-item-reference-edit-placeholder',
			'type' => 'text',
		];

		parent::__construct( array_merge( $defaultValues, $params ) );

		$this->entityLookup = $entityLookup ?: WikibaseRepo::getEntityLookup();
	}

	/**
	 * @see \HTMLFormField::validate
	 *
	 * @param string $value
	 * @param array $alldata
	 *
	 * @return bool|string|Message
	 */
	public function validate( $value, $alldata ) {
		$required = isset( $this->mParams['required'] ) && $this->mParams['required'] !== false;

		if ( !$required && $value === '' ) {
			return true;
		}

		try {
			$itemId = new ItemId( $value );
		} catch ( \InvalidArgumentException $e ) {
			return $this->msg( 'wikibase-item-reference-edit-invalid-format' );
		}

		if ( !$this->entityLookup->hasEntity( $itemId ) ) {
			return $this->msg( 'wikibase-item-reference-edit-nonexistent-item' );
		}

		return parent::validate( $value, $alldata );
	}

}
