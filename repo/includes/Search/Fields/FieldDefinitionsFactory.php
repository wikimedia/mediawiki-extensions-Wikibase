<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Search\Fields;

use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 */
class FieldDefinitionsFactory {

	/** @var EntityTypeDefinitions */
	private $entityTypeDefinitions;

	/** @var ContentLanguages */
	private $termsLanguages;

	/** @var SettingsArray */
	private $repoSettings;

	public function __construct(
		EntityTypeDefinitions $entityTypeDefinitions,
		ContentLanguages $termsLanguages,
		SettingsArray $repoSettings
	) {
		$this->entityTypeDefinitions = $entityTypeDefinitions;
		$this->termsLanguages = $termsLanguages;
		$this->repoSettings = $repoSettings;
	}

	public function getFieldDefinitionsByType( string $entityType ): FieldDefinitions {
		$definitions = $this->entityTypeDefinitions
			->get( EntityTypeDefinitions::SEARCH_FIELD_DEFINITIONS );
		if ( isset( $definitions[$entityType] ) && is_callable( $definitions[$entityType] ) ) {
			return $definitions[$entityType](
				$this->termsLanguages->getLanguages(),
				$this->repoSettings
			);
		}
		return new NoFieldDefinitions();
	}

}
