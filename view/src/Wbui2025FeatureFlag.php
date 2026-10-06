<?php

declare( strict_types = 1 );

namespace Wikibase\View;

use MediaWiki\Parser\ParserOutput;
use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 */
class Wbui2025FeatureFlag {
	public const EXTENSION_DATA_KEY = 'wikibase-mobile';
	public const WBMOBILE_WBUI2025_FLAG = 'wbui2025';
	public const PARSER_OPTION_NAME = 'wbMobile';

	private bool $wbui2025Enabled;

	public function __construct(
		SettingsArray $settings,
	) {
		$this->wbui2025Enabled = (bool)$settings->getSetting( 'tmpMobileEditingUI' );
	}

	public function generateWbMobileFlagValue( bool $isMobileSite ): bool|string {
		if ( !$isMobileSite ) {
			return false;
		}
		return $this->shouldRenderAsWbui2025() ? self::WBMOBILE_WBUI2025_FLAG : true;
	}

	public static function wbui2025EnabledForParserOutput( ParserOutput $parserOutput ): bool {
		$wbuiFlag = $parserOutput->getExtensionData( self::EXTENSION_DATA_KEY );
		return self::wbui2025EnabledForWbMobileValue( $wbuiFlag === null ? false : $wbuiFlag );
	}

	public static function wbui2025EnabledForViewOptions( array $viewOptions ): bool {
		if ( !array_key_exists( self::EXTENSION_DATA_KEY, $viewOptions ) ) {
			return false;
		}
		return self::wbui2025EnabledForWbMobileValue( $viewOptions[ self::EXTENSION_DATA_KEY ] );
	}

	private static function wbui2025EnabledForWbMobileValue( bool|string $wbMobile ): bool {
		return $wbMobile === 'wbui2025';
	}

	public function shouldRenderAsWbui2025(): bool {
		return $this->wbui2025Enabled;
	}
}
