/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $, QUnit ) {
	'use strict';

QUnit.module( 'jquery.ui.EditableTemplatedWidget', QUnit.newMwEnvironment( {
	setup: function() {
		$.widget( 'test.editablewidget', {
			_create: function() {
				this._initialValue = this.options.value;
			},
			draw: function() {},
			value: function( value ) {
				if ( value === undefined ) {
					this.option( 'value', value );
				} else {
					return this.option( 'value' );
				}
			}
		} );
	},
	teardown: function() {
		delete( $.test.editablewidget );

		$( '.test_edittoolbar' ).each( function() {
			var $edittoolbar = $( this ),
				edittoolbar = $edittoolbar.data( 'edittoolbar' );

			if ( edittoolbar ) {
				edittoolbar.destroy();
			}

			$edittoolbar.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.expect( 2 );
	var testSets = [
		[
			'<div><span>$1</span></div>',
			{
				templateParams: ['test']
			}
		]
	];

	for ( var i = 0; i < testSets.length; i++ ) {
		mw.wbTemplates.store.set( 'templatedWidget-test', testSets[i][0] );

		var $subject = $( '<div/>' );

		$subject.editablewidget( $.extend( {
			template: 'templatedWidget-test'
		}, testSets[i][1] ) );

		assert.ok(
			$subject.data( 'editablewidget' ) instanceof $.test.editablewidget,
			'Test set #' + i + ': Initialized widget.'
		);

		$subject.data( 'editablewidget' ).destroy();

		assert.ok(
			$subject.data( 'editablewidget' ) === undefined,
			'Destroyed widget.'
		);
	}
} );

}( mediaWiki, jQuery, QUnit ) );
