/**
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
/* jshint nonew: false */
( function( $, vv, dv, vf, vp, sinon, QUnit ) {
	'use strict';

	var $vvElem;
	var vvInst;
	var stringValue = new dv.StringValue( 'STRING VALUE' );

	QUnit.module( 'jquery.valueview.valueview' );

	function initVv( opts ) {
		opts = $.extend( {}, initVv.defaultOpts, opts );

		$vvElem = opts.generateDom();
		vvInst = $vvElem.valueview( opts.vvArgs ).data( 'valueview' );

		if ( opts.withExpert ) {
			vvInst.startEditing();
		}
	}
	initVv.defaultOpts = {
		withExpert: false,
		generateDom: function() { return $( '<div/>' ); },
		vvArgs: {
			expertStore: new vv.ExpertStore(),
			htmlFormatter: new vf.NullFormatter(),
			plaintextFormatter: new vf.NullFormatter(),
			parserStore: new vp.ValueParserStore( vp.NullParser ),
			language: 'en'
		}
	};

	QUnit.test( 'Constructor', function( assert ) {
		assert.expect( 2 );
		initVv();

		assert.ok(
			vvInst instanceof vv,
			'Instantiated ValueView.'
		);

		assert.ok( $vvElem.hasClass( vvInst.widgetBaseClass ) );
	} );

	QUnit.test( 'destroy', function( assert ) {
		assert.expect( 1 );
		initVv();

		vvInst.destroy();

		assert.ok( !$vvElem.hasClass( vvInst.widgetBaseClass ) );
	} );

	QUnit.test( 'destroy with expert', function( assert ) {
		assert.expect( 1 );
		initVv( { withExpert: true } );

		vvInst.destroy();

		assert.ok( !vvInst.expert() );
	} );

	QUnit.test( 'startEditing', function( assert ) {
		assert.expect( 1 );
		initVv();

		vvInst.startEditing();

		assert.ok( vvInst.isInEditMode() );
	} );

	QUnit.test( 'stopEditing without startEditing', function( assert ) {
		assert.expect( 3 );
		initVv();
		assert.ok( !vvInst.isInEditMode() );

		vvInst.stopEditing();

		assert.ok( !vvInst.isInEditMode() );
		assert.ok( !vvInst.expert() );
	} );

	QUnit.test( 'stopEditing after startEditing', function( assert ) {
		assert.expect( 4 );
		initVv();

		vvInst.startEditing();
		vvInst.value( stringValue );
		vvInst.stopEditing();

		assert.ok( !vvInst.isInEditMode() );
		assert.ok( !vvInst.expert() );
		assert.equal( vvInst.value(), stringValue );
		assert.ok( !vvInst.isEmpty() );
	} );

	QUnit.test( 'cancelEditing without startEditing', function( assert ) {
		assert.expect( 3 );
		initVv();
		assert.ok( !vvInst.isInEditMode() );

		vvInst.cancelEditing();

		assert.ok( !vvInst.isInEditMode() );
		assert.ok( !vvInst.expert() );
	} );

	QUnit.test( 'cancelEditing after startEditing', function( assert ) {
		assert.expect( 4 );
		initVv();

		vvInst.startEditing();
		vvInst.value( stringValue );
		vvInst.cancelEditing();

		assert.ok( !vvInst.isInEditMode() );
		assert.ok( !vvInst.expert() );
		assert.notEqual( vvInst.value(), stringValue );
		assert.ok( vvInst.isEmpty() );
	} );

	QUnit.test( 'getFormattedValue with DOM', function( assert ) {
		assert.expect( 4 );
		var vvArgs = $.extend( {
			value: stringValue
		}, initVv.defaultOpts.vvArgs );
		sinon.spy( vvArgs.htmlFormatter, 'format' );
		sinon.spy( vvArgs.plaintextFormatter, 'format' );
		sinon.spy( vvArgs.parserStore, 'getParser' );
		initVv( {
			generateDom: function() {
				return $( '<div/>' ).append( 'FORMATTED VALUE' );
			},
			vvArgs: vvArgs
		} );

		return vvInst.draw()
		.done( function() {
			assert.equal( vvInst.getFormattedValue(), 'FORMATTED VALUE' );
			sinon.assert.notCalled( vvArgs.htmlFormatter.format );
			sinon.assert.notCalled( vvArgs.plaintextFormatter.format );
			sinon.assert.notCalled( vvArgs.parserStore.getParser );

			vvArgs.htmlFormatter.format.restore();
			vvArgs.plaintextFormatter.format.restore();
			vvArgs.parserStore.getParser.restore();
		} );
	} );

	QUnit.test( 'getFormattedValue without DOM', function( assert ) {
		assert.expect( 4 );
		var vvArgs = $.extend( {
			value: stringValue
		}, initVv.defaultOpts.vvArgs );
		sinon.spy( vvArgs.htmlFormatter, 'format' );
		sinon.spy( vvArgs.plaintextFormatter, 'format' );
		sinon.spy( vvArgs.parserStore, 'getParser' );
		initVv( {
			vvArgs: vvArgs
		} );

		return vvInst.draw()
		.done( function() {
			assert.equal( vvInst.getFormattedValue(), 'STRING VALUE' );
			sinon.assert.calledOnce( vvArgs.htmlFormatter.format );
			sinon.assert.notCalled( vvArgs.plaintextFormatter.format );
			sinon.assert.notCalled( vvArgs.parserStore.getParser );

			vvArgs.htmlFormatter.format.restore();
			vvArgs.plaintextFormatter.format.restore();
			vvArgs.parserStore.getParser.restore();
		} );
	} );

} )(
	jQuery,
	jQuery.valueview,
	dataValues,
	valueFormatters,
	valueParsers,
	sinon,
	QUnit
);
