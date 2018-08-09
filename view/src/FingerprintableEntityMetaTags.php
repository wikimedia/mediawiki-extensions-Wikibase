<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\LanguageFallbackChain;
use Wikimedia\Assert\Assert;

/**
 * Class for creating meta tags (i.e. title and description) for kinds of Fingerprint Provider
 *
 * @license GPL-2.0-or-later
 */
class FingerprintableEntityMetaTags implements EntityMetaTags {

	private $languageFallbackChain;

	public function __construct( languageFallbackChain $languageFallbackChain ) {
		$this->languageFallbackChain = $languageFallbackChain;
	}

	public function getMetaTags( EntityDocument $entity ) {
		Assert::parameterType( FingerprintProvider::class, $entity, '$entity' );

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
			$entityId = $entity->getId();

			if ( $entityId instanceof EntityId ) {
				$titleText = $entityId->getSerialization();
			}

		}

		return $titleText;
	}

}
