<?php

namespace Wikibase\Repo;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
 */
class FingerprintSearchTextGenerator {

	/**
	 * @param EntityDocument $entity
	 *
	 * @return string
	 */
	public function generate( EntityDocument $entity ) {
		$text = '';

		if ( $entity instanceof LabelsProvider ) {
			$text .= implode( "\n", $entity->getLabels()->toTextArray() );
		}

		if ( $entity instanceof DescriptionsProvider ) {
			$text .= "\n" . implode( "\n", $entity->getDescriptions()->toTextArray() );
		}

		if ( $entity instanceof AliasesProvider ) {
			foreach ( $entity->getAliasGroups()->toArray() as $aliasGroup ) {
				$text .= "\n" . implode( "\n", $aliasGroup->getAliases() );
			}
		}

		return trim( $text, "\n" );
	}

}
