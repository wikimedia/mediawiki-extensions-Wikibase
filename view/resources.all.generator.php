<?php

function path( $module, $path ) {
	$path = realpath( $module['localBasePath'] . '/' . $path );
	$path = str_replace( __DIR__ . '/', '', $path );
	return $path;
}

$more = include __DIR__ . '/resources/resources.php';
$allModuleKeys = array_keys( $more );

$mainModuleKey = 'wikibase.view';
if ( !isset( $more[$mainModuleKey] ) ) {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$more[$mainModuleKey] = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
		'scripts' => array(),
		'styles' => array(),
		'dependencies' => array(),
		'messages' => array(),
	);
}
foreach ( $more as $key => $module ) {
	if ( $key === $mainModuleKey ) {
		continue;
	}
	if ( !isset( $module['localBasePath'] )
		|| !isset( $module['remoteExtPath'] )
		|| isset( $module['class'] )
	) {
		continue;
	}

	$keys = array_keys( $module );
	if ( array_diff( $keys, array(
			'dependencies',
			'localBasePath',
			'messages',
			'position',
			'remoteExtPath',
			'scripts',
			'styles',
			'targets',
		) ) !== array() ) {
		var_dump( $key, $module );
		die();
	}
	if ( !isset( $module['scripts'] ) ) {
		$module['scripts'] = array();
	}
	if ( !isset( $module['styles'] ) ) {
		$module['styles'] = array();
	}
	if ( !isset( $module['dependencies'] ) ) {
		$module['dependencies'] = array();
	}
	if ( !isset( $module['messages'] ) ) {
		$module['messages'] = array();
	}

	if ( !is_array( $module['scripts'] ) ) {
		$module['scripts'] = (array)$module['scripts'];
	}

	foreach ( $module['scripts'] as &$path ) {
		$path = path( $module, $path );
	}
	foreach ( $module['styles'] as &$path ) {
		$path = path( $module, $path );
	}

	$more[$mainModuleKey]['scripts'] = array_merge(
		$more[$mainModuleKey]['scripts'],
		$module['scripts']
	);
	$more[$mainModuleKey]['styles'] = array_unique( $more[$mainModuleKey]['styles'] );
	sort( $more[$mainModuleKey]['styles'] );

	$more[$mainModuleKey]['styles'] = array_merge(
		$more[$mainModuleKey]['styles'],
		$module['styles']
	);
	$more[$mainModuleKey]['styles'] = array_unique( $more[$mainModuleKey]['styles'] );
	sort( $more[$mainModuleKey]['styles'] );

	$module['dependencies'] = array_diff( $module['dependencies'], $allModuleKeys );
	$more[$mainModuleKey]['dependencies'] = array_merge(
		$more[$mainModuleKey]['dependencies'],
		$module['dependencies']
	);
	$more[$mainModuleKey]['dependencies'] = array_unique( $more[$mainModuleKey]['dependencies'] );
	sort( $more[$mainModuleKey]['dependencies'] );

	$more[$mainModuleKey]['messages'] = array_merge(
		$more[$mainModuleKey]['messages'],
		$module['messages']
	);
	$more[$mainModuleKey]['messages'] = array_unique( $more[$mainModuleKey]['messages'] );
	sort( $more[$mainModuleKey]['messages'] );

	unset( $more[$key] );
}

var_dump( $more );
file_put_contents( 'resources.all.php', "<?php\n\nreturn " . var_export( $more, true ) . ";\n" );
die();
