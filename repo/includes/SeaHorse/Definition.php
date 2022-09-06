<?php

namespace Wikibase\Repo\SeaHorse;

use Wikibase\Lib\EntityTypeDefinitions as Def;
use Wikibase\Repo\WikibaseRepo;

return [
	Def::CONTENT_HANDLER_FACTORY_CALLBACK => function() {
		$services = \MediaWiki\MediaWikiServices::getInstance();
		return new Groom(
			SeaHorseSaddle::CONTENT_ID,
			null, // unused
			WikibaseRepo::getEntityContentDataCodec( $services ),
			WikibaseRepo::getEntityConstraintProvider( $services ),
			WikibaseRepo::getValidatorErrorLocalizer( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getFieldDefinitionsFactory( $services )
			->getFieldDefinitionsByType( SeaHorseSaddle::ENTITY_TYPE ),
			null
		);
	},
];
