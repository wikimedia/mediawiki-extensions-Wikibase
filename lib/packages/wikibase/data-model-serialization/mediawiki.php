<?php

if ( defined( 'MEDIAWIKI' ) && function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseDataModelSerialization', __DIR__ . '/mediawiki-extension.json' );
}
