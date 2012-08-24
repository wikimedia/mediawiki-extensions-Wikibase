<?php

namespace Wikibase;

/**
 * Lookup facility for terms using an SQL store.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermSqlLookup implements TermLookup {

	/**
	 * @since 0.1
	 *
	 * @var integer $db
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param integer $db
	 */
	public function __construct( $db = DB_SLAVE ) {
		$this->db = $db;
	}

	/**
	 * @since 0.1
	 *
	 * @return \DatabaseBase
	 */
	protected function getDB() {
		return wfGetDB( $this->db );
	}

	/**
	 * @see TermLookup::getItemIdsForLabel
	 *
	 * @since 0.1
	 *
	 * @param string $label
	 * @param string|null $languageCode
	 * @param string|null $description
	 *
	 * @return array of integer
	 */
	public function getItemIdsForLabel( $label, $languageCode = null, $description = null ) {
		$db = $this->getDB();

		$tables = array( 'terms0' => 'wb_terms' );

		$conds = array(
			'terms0.term_text' => $label,
			'terms0.term_type' => 'label',
		);

		$joinConds = array();

		if ( !is_null( $languageCode ) ) {
			$conds['terms0.term_language'] = $languageCode;
		}

		if ( !is_null( $description ) ) {
			$conds['terms1.term_text'] = $description;
			$conds['terms1.term_type'] = 'description';

			if ( !is_null( $languageCode ) ) {
				$conds['terms1.term_language'] = $languageCode;
			}

			$tables['terms1'] = 'wb_terms';

			$joinConds = array(
				'terms1' => array( 'LEFT OUTER JOIN', array( 'terms0.term_entity_id=terms1.term_entity_id', 'terms0.term_entity_type=terms1.term_entity_type' ) ),
			);
		}

		$items = $db->select(
			$tables,
			array( 'terms0.term_entity_id' ),
			$conds,
			__METHOD__,
			array( 'DISTINCT' ),
			$joinConds
		);

		return array_map( function( $item ) { return $item->term_entity_id; }, iterator_to_array( $items ) );
	}

}