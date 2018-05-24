<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use CirrusSearch;
use DataValues\UnboundedQuantityValue;
use MWException;
use SearchEngine;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * Additional field to index statements with their qualifiers and the qualifier value where the
 * qualifier is of type 'quantity'
 *
 * @license GPL-2.0-or-later
 */
class StatementQuantityField extends StatementsField implements WikibaseIndexField {

	/**
	 * Field name
	 */
	const NAME = 'statement_quantity';

	/**
	 * @param EntityDocument $entity
	 *
	 * @throws MWException
	 * @return mixed Get the value of the field to be indexed when a page/document
	 *               is indexed. This might be an array with nested data, if the field
	 *               is defined with nested type or an int or string for simple field types.
	 */
	public function getFieldData( EntityDocument $entity ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			return [];
		}

		$data = [];

		/** @var Statement $statement */
		foreach ( $entity->getStatements() as $statement ) {
			$snak = $statement->getMainSnak();
			$mainSnakString = $this->getWhitelistedSnakAsString( $snak );
			if ( is_null( $mainSnakString ) ) {
				continue;
			}
			foreach ( $statement->getQualifiers() as $qualifier ) {
				$propertyIdAndValue = $this->getSnakAsPropertyIdAndValue( $qualifier );
				if (
					!is_null( $propertyIdAndValue )
					&&
					$qualifier->getDataValue() instanceof UnboundedQuantityValue
				) {
						$data[] = $mainSnakString . '|' . $propertyIdAndValue[ 'value' ];
				}
			}
		}

		return $data;
	}

	/**
	 * @param SearchEngine $engine
	 *
	 * @return array
	 */
	public function getMapping( SearchEngine $engine ) {
		// Since we need a specially tuned field, we can not use
		// standard search engine types.
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return [];
		}

		$config = [
			'type' => 'text',
			'index_options' => 'freqs',
			'analyzer' => 'extract_wb_quantity',
			'search_analyzer' => 'keyword',
		];

		return $config;
	}

}
