<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;

/**
 * Options for Entity serializers.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntitySerializationOptions extends MultiLangSerializationOptions {

	const SORT_ASC = 'ascending';
	const SORT_DESC = 'descending';
	const SORT_NONE = 'none';

	/**
	 * The optional properties of the entity that should be included in the serialization.
	 *
	 * @since 0.2
	 *
	 * @var array of string
	 */
	protected $props = array(
		'aliases',
		'descriptions',
		'labels',
		'claims',
		// TODO: the following properties are not part of all entities, listing them here is not nice
		'datatype', // property specific
		'sitelinks', // item specific
	);

	/**
	 * Names of fields to sort on.
	 *
	 * @since 0.2
	 *
	 * @var array
	 */
	protected $sortFields = array();

	/**
	 * The direction the result should be sorted in.
	 *
	 * @since 0.2
	 *
	 * @var string Element of the EntitySerializationOptions::SORT_ enum
	 */
	protected $sortDirection = self::SORT_NONE;

	/**
	 * Sets the optional properties of the entity that should be included in the serialization.
	 *
	 * @since 0.2
	 *
	 * @param array $props
	 */
	public function setProps( array $props ) {
		$this->props = $props;
	}

	/**
	 * Gets the optional properties of the entity that should be included in the serialization.
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getProps() {
		return $this->props;
	}

	/**
	 * Adds a prop to the list of optionally included elements of the entity.
	 *
	 * @since 0.3
	 *
	 * @param string $name
	 */
	public function addProp( $name ) {
		$this->props[] = $name;
	}

	/**
	 * Removes a prop from the list of optionally included elements of the entity.
	 *
	 * @since 0.4
	 *
	 * @param string $name
	 */
	public function removeProp ( $name ) {
		$this->props = array_diff( $this->props, array( $name ) );
	}

	/**
	 * Sets the names of fields to sort on.
	 *
	 * @since 0.2
	 *
	 * @param array $sortFields
	 */
	public function setSortFields( array $sortFields ) {
		$this->sortFields = $sortFields;
	}

	/**
	 * Returns the names of fields to sort on.
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getSortFields() {
		return $this->sortFields;
	}

	/**
	 * Sets the direction the result should be sorted in.
	 *
	 * @since 0.2
	 *
	 * @param string $sortDirection Element of the EntitySerializationOptions::SORT_ enum
	 * @throws InvalidArgumentException
	 */
	public function setSortDirection( $sortDirection ) {
		if ( !in_array( $sortDirection, array( self::SORT_ASC, self::SORT_DESC, self::SORT_NONE ) ) ) {
			throw new InvalidArgumentException( 'Invalid sort direction provided' );
		}

		$this->sortDirection = $sortDirection;
	}

	/**
	 * Returns the direction the result should be sorted in.
	 *
	 * @since 0.2
	 *
	 * @return string Element of the EntitySerializationOptions::SORT_ enum
	 */
	public function getSortDirection() {
		return $this->sortDirection;
	}

}
