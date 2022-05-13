/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {Object} [options]
	 * @param {jQuery} [$node]
	 * @return {jQuery}
	 */
	var createPropertyview = function ( options, $node ) {
		options = $.extend( {
			value: new datamodel.Property( 'P1', 'someDataType' ),
			buildEntityTermsView: function () {},
			buildStatementGroupListView: function () {}
		}, options || {} );

		$node = $node || $( '<div>' ).appendTo( document.body );

		var $propertyview = $node
			.addClass( 'test_propertyview' )
			.propertyview( options );

		return $propertyview;
	};

	QUnit.module( 'jquery.wikibase.propertyview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_propertyview' ).each( function () {
				var $propertyview = $( this ),
					propertyview = $propertyview.data( 'propertyview' );

				if ( propertyview ) {
					propertyview.destroy();
				}

				$propertyview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		assert.throws(
			function () {
				createPropertyview( { value: null } );
			},
			'Throwing error when trying to initialize widget without a value.'
		);

		var $propertyview = createPropertyview(),
			propertyview = $propertyview.data( 'propertyview' );

		assert.true(
			propertyview instanceof $.wikibase.propertyview,
			'Created widget.'
		);

		propertyview.destroy();

		assert.strictEqual(
			$propertyview.data( 'propertyview' ),
			undefined,
			'Destroyed widget.'
		);

	} );

}() );
