<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\View;

use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\FileModule;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Wbui2025ComponentsFactory;
use WMDE\VueJsTemplating\App;

/**
 * ResourceLoader module that serves styles extracted from .vue component <style> blocks.
 *
 * @license GPL-2.0-or-later
 * @author Mahmoud Abdelsattar <mahmoud.abdelsattar@wikimedia.de>
 */
class VueStylesModule extends FileModule {

	protected function getComponentsFactory(): Wbui2025ComponentsFactory {
		return WikibaseRepo::getWbui2025ComponentsFactory();
	}

	/** @inheritDoc */
	public function getStyles( Context $context ): array {
		$styles = parent::getStyles( $context );
		$factory = $this->getComponentsFactory();
		$app = new App( [] );
		$factory->registerComponentTemplates( $app );

		foreach ( $factory->getTemplateFiles() as $componentName => $relPath ) {
			$this->localFileRefs[] = $this->getLocalPath( $relPath );
			foreach ( $app->getComponentStyles( $componentName ) as $styleData ) {
				$styles['all'] = ( $styles['all'] ?? '' ) . "\n" .
					$this->processStyle( $styleData['content'], $styleData['lang'], $relPath, $context );
			}
		}

		return $styles;
	}

}
