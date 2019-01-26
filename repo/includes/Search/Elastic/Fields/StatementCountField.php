<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\Search\Fields\WikibaseNumericField;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class StatementCountField extends WikibaseNumericField {

	/**
	 * Field name
	 */
	const NAME = 'statement_count';

	/**
	 * @see SearchIndexField::getFieldData
	 *
	 * @param EntityDocument $entity
	 *
	 * @return int
	 */
	public function getFieldData( EntityDocument $entity ) {
		if ( $entity instanceof StatementListProvider ) {
			return $entity->getStatements()->count();
		}

		return 0;
	}

}
