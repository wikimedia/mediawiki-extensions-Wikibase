<?php declare( strict_types=1 );

namespace Wikibase\Repo\GraphQLPrototype;

use GraphQL\Utils\SchemaPrinter;
use MediaWiki\Maintenance\Maintenance;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\WikibaseRepo;

/**
 * Maintenance script to generate the Wikibase GraphQL schema SDL
 *
 * @license GPL-2.0-or-later
 */
class GenerateSDL extends Maintenance {

	public static function schema(): Schema {
		$entityLookup = WikibaseRepo::getEntityLookup();

		return new Schema(
			// Limiting this to a handful of language to on one hand keep the SDL file small and readable, and on the other
			// avoid getting different versions of the same schema across wikis depending on the language config.
			new StaticContentLanguages( [ 'ar', 'de', 'en', 'he' ] ),
			new LabelsResolver( WikibaseRepo::getPrefetchingTermLookup() ),
			new StatementsResolver( $entityLookup ),
			new ItemResolver( $entityLookup )
		);
	}

	public function execute() {
		file_put_contents(
			__DIR__ . '/schema.graphql',
			SchemaPrinter::doPrint( self::schema() )
		);
	}
}

$maintClass = GenerateSDL::class;
