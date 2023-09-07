<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class ValidatingRequestFieldDeserializerFactory {

	public function newItemIdRequestValidatingDeserializer(): ItemIdRequestValidatingDeserializer {
		return new ItemIdRequestValidatingDeserializer( new ItemIdValidator() );
	}

	public function newPropertyIdRequestValidatingDeserializer(): PropertyIdRequestValidatingDeserializer {
		return new PropertyIdRequestValidatingDeserializer( new PropertyIdValidator() );
	}

	public function newStatementIdRequestValidatingDeserializer(): StatementIdRequestValidatingDeserializer {
		$entityIdParser = new BasicEntityIdParser();

		return new StatementIdRequestValidatingDeserializer(
			new StatementIdValidator( $entityIdParser ),
			new StatementGuidParser( $entityIdParser )
		);
	}

}
