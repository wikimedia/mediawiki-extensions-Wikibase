<?php
/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. 'wikibase-data-values-value-view' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'experts';

	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, $dir, 2
	);
	$moduleTemplate = [
		'localBasePath' => $dir,
		'remoteExtPath' => $remoteExtPathParts[1],
	];

	return [

		'jquery.valueview.experts.CommonsMediaType' => $moduleTemplate + [
				'scripts' => [
					'CommonsMediaType.js',
				],
				'dependencies' => [
					'jquery.event.special.eachchange',
					'jquery.ui.commonssuggester',
					'jquery.valueview.experts',
					'jquery.valueview.experts.StringValue',
					'jquery.valueview.Expert',
				],
			],

		'jquery.valueview.experts.GeoShape' => $moduleTemplate + [
				'scripts' => [
					'GeoShape.js',
				],
				'dependencies' => [
					'jquery.event.special.eachchange',
					'jquery.ui.commonssuggester',
					'jquery.valueview.experts',
					'jquery.valueview.experts.StringValue',
					'jquery.valueview.Expert',
				],
			],

		'jquery.valueview.experts.TabularData' => $moduleTemplate + [
				'scripts' => [
					'TabularData.js',
				],
				'dependencies' => [
					'jquery.event.special.eachchange',
					'jquery.ui.commonssuggester',
					'jquery.valueview.experts',
					'jquery.valueview.experts.StringValue',
					'jquery.valueview.Expert',
				],
			],

		'jquery.valueview.experts.EmptyValue' => $moduleTemplate + [
				'scripts' => [
					'EmptyValue.js',
				],
				'styles' => [
					'EmptyValue.css',
				],
				'dependencies' => [
					'jquery.valueview.experts',
					'jquery.valueview.Expert',
				],
				'messages' => [
					'valueview-expert-emptyvalue-empty',
				],
			],

		'jquery.valueview.experts.GlobeCoordinateInput' => $moduleTemplate + [
				'scripts' => [
					'GlobeCoordinateInput.js',
				],
				'styles' => [
					'GlobeCoordinateInput.css',
				],
				'dependencies' => [
					'jquery.valueview.ExpertExtender',
					'jquery.valueview.ExpertExtender.Container',
					'jquery.valueview.ExpertExtender.Listrotator',
					'jquery.valueview.ExpertExtender.Preview',
					'jquery.valueview.experts',
					'jquery.valueview.experts.StringValue',
					'jquery.valueview.Expert',
					'util.MessageProvider',
				],
				'messages' => [
					'valueview-expert-globecoordinateinput-precision',
					'valueview-expert-globecoordinateinput-nullprecision',
					'valueview-expert-globecoordinateinput-customprecision',
				],
			],

		'jquery.valueview.experts.MonolingualText' => $moduleTemplate + [
				'scripts' => [
					'MonolingualText.js',
				],
				'dependencies' => [
					'jquery.valueview.Expert',
					'jquery.valueview.ExpertExtender',
					'jquery.valueview.ExpertExtender.LanguageSelector',
					'jquery.valueview.experts',
					'jquery.valueview.experts.StringValue',
				],
			],

		'jquery.valueview.experts.QuantityInput' => $moduleTemplate + [
				'scripts' => [
					'QuantityInput.js',
				],
				'dependencies' => [
					'jquery.valueview.Expert',
					'jquery.valueview.ExpertExtender',
					'jquery.valueview.ExpertExtender.UnitSelector',
					'jquery.valueview.experts',
					'jquery.valueview.experts.StringValue',
				],
			],

		'jquery.valueview.experts.StringValue' => $moduleTemplate + [
				'scripts' => [
					'StringValue.js',
				],
				'dependencies' => [
					'jquery.event.special.eachchange',
					'jquery.focusAt',
					'jquery.inputautoexpand',
					'jquery.valueview.experts',
					'jquery.valueview.Expert',
				],
			],

		'jquery.valueview.experts.SuggestedStringValue' => $moduleTemplate + [
				'scripts' => [
					'SuggestedStringValue.js',
				],
				'dependencies' => [
					'jquery.event.special.eachchange',
					'jquery.ui.suggester',
					'jquery.valueview.experts',
					'jquery.valueview.experts.StringValue',
					'jquery.valueview.Expert',
				],
			],

		'jquery.valueview.experts.TimeInput' => $moduleTemplate + [
				'scripts' => [
					'TimeInput.js',
				],
				'styles' => [
					'TimeInput.css',
				],
				'dependencies' => [
					'dataValues.TimeValue',
					'jquery.valueview.ExpertExtender',
					'jquery.valueview.ExpertExtender.Container',
					'jquery.valueview.ExpertExtender.Listrotator',
					'jquery.valueview.ExpertExtender.Preview',
					'jquery.valueview.experts',
					'jquery.valueview.Expert',
					'util.MessageProvider',
				],
				'messages' => [
					'valueview-expert-timeinput-calendar',
					'valueview-expert-timeinput-precision',
					'valueview-expert-timevalue-calendar-gregorian',
					'valueview-expert-timevalue-calendar-julian',
				],
			],

		'jquery.valueview.experts.UnDeserializableValue' => $moduleTemplate + [
				'scripts' => [
					'UnDeserializableValue.js'
				],
				'dependencies' => [
					'jquery.valueview.experts',
					'jquery.valueview.Expert',
				]
			],

		'jquery.valueview.experts.UnsupportedValue' => $moduleTemplate + [
				'scripts' => [
					'UnsupportedValue.js',
				],
				'styles' => [
					'UnsupportedValue.css',
				],
				'dependencies' => [
					'jquery.valueview.experts',
					'jquery.valueview.Expert',
				],
				'messages' => [
					'valueview-expert-unsupportedvalue-unsupporteddatatype',
					'valueview-expert-unsupportedvalue-unsupporteddatavalue',
				]
			],
	];
} );
