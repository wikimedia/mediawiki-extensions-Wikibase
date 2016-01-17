<?php

if ( defined( 'MEDIAWIKI' ) && function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseInternalSerialization', __DIR__ . '/mediawiki-extension.json' );
}
