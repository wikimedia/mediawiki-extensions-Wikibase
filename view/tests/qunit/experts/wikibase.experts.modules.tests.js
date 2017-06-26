( function () {
	'use strict';
	QUnit.module( 'wikibase.experts.modules' );

	QUnit.test(
		'module has correct dependencies and every registered property type exports expert',
		function ( assert ) {
			var modules = require( 'wikibase.experts.modules' );

			for ( var propertyType in modules ) {
				if ( modules.hasOwnProperty( propertyType ) ) {
					var caughtError = null;

					try {
						var module = require( modules[ propertyType ] );
						assert.equal(
							typeof module,
							'function',
							'Property type "' + propertyType + '" exports a constructor'
						);
					} catch ( e ) {
						caughtError = e;
					}

					assert.notOk(
						caughtError,
						'Property type "' + propertyType + '" expert is added as a dependency'
					);
				}
			}

		}
	);
}() );
