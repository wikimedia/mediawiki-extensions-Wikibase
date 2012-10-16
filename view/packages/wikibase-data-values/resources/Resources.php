<?php
/**
 * Definition of 'DataValues' resourceloader modules.
 * When included this returns an array with all the modules introduced by 'DataValues' extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' =>  'DataValues/DataValues/resources',
	);

	return array(
		'dataValues' => $moduleTemplate + array(
			'scripts' => array(
				'dataValues.js',
			),
		),

		'dataValues.values' => $moduleTemplate + array(
			'scripts' => array(
				'dataValues.Value.js',
				'dataValues.String.js',
				'dataValues.MonolingualText.js',
				'dataValues.MultilingualText.js',
			),
			'dependencies' => array(
				'dataValues',
				'dataValues.util'
			),
		),

		'dataValues.util' => $moduleTemplate + array(
			'scripts' => array(
				'dataValues.util/dataValues.util.js',
			),
			'dependencies' => array(
				'dataValues'
			),
		),
	);
} );
// @codeCoverageIgnoreEnd
