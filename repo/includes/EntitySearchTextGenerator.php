<?php

namespace Wikibase\Repo;

use Wikibase\Entity;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntitySearchTextGenerator {

	/**
	 * @param Entity $entity
	 *
	 * @return string
	 */
	public function generate( Entity $entity ) {
		$labels = $entity->getLabels();
		$text = $this->getArrayAsText( $labels );

		$descriptions = $entity->getDescriptions();
		$text .= "\n" . $this->getArrayAsText( $descriptions );

		$allAliases = $entity->getAllAliases();
		$text .= $this->getAllAliasesText( $allAliases );

		return $text;
	}

	/**
	 * @param string[] $elements
	 *
	 * @return string
	 */
	protected function getArrayAsText( array $elements ) {
		return implode( "\n", $elements );
	}

	/**
	 * @param array $allAliases
	 *
	 * @return string
	 */
	protected function getAllAliasesText( array $allAliases ) {
		$text = '';

		foreach ( $allAliases as $aliases ) {
			$text .= "\n" . implode( "\n", $aliases );
		}

		return $text;
	}

}
