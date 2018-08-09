<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\LanguageFallbackChain;

/**
 * Base class for creating meta tags (i.e. title and description) for kinds of Fingerprint Provider
 *
 * @license GPL-2.0-or-later
 */
class FingerprintableEntityMetaTags extends EntityMetaTags {

	private $languageFallbackChain;

	public function __construct( languageFallbackChain $languageFallbackChain ) {
		$this->languageFallbackChain = $languageFallbackChain;
	}

	public function getMetaTags( FingerprintProvider $entity ) {
		$metaTags = [
			'title' => $this->getTitleText( $entity ),
		];

		if ( $entity instanceof DescriptionsProvider ) {
			$descriptions = $entity->getDescriptions()->toTextArray();
			$preferred = $this->languageFallbackChain->extractPreferredValue( $descriptions );

			if ( is_array( $preferred ) ) {
				$metaTags['description'] = $preferred['value'];
			}
		}

		return $metaTags;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return string|null
	 */
	protected function getTitleText( EntityDocument $entity ) {
		$titleText = null;

		$labels = $entity->getLabels()->toTextArray();
		$preferred = $this->languageFallbackChain->extractPreferredValue( $labels );

		if ( is_array( $preferred ) ) {
			$titleText = $preferred['value'];
		}

		if ( !is_string( $titleText ) ) {
			$titleText = parent::getTitleText( $entity );
		}

		return $titleText;
	}

}
