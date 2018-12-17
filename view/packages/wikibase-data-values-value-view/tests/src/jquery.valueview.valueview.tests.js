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
		initVv();

		assert.ok(
			vvInst instanceof vv,
			'Instantiated ValueView.'
		);

		assert.ok( $vvElem.hasClass( vvInst.widgetBaseClass ) );
	} );

	QUnit.test( 'destroy', function( assert ) {
		initVv();

		vvInst.destroy();

		assert.strictEqual( $vvElem.hasClass( vvInst.widgetBaseClass ), false );
	} );

	QUnit.test( 'destroy with expert', function( assert ) {
		initVv( { withExpert: true } );

		vvInst.destroy();

		assert.strictEqual( vvInst.expert(), null );
	} );

	QUnit.test( 'startEditing', function( assert ) {
		initVv();

		vvInst.startEditing();

		assert.ok( vvInst.isInEditMode() );
	} );

	QUnit.test( 'stopEditing without startEditing', function( assert ) {
		initVv();
		assert.strictEqual( vvInst.isInEditMode(), false );

		vvInst.stopEditing();

		assert.strictEqual( vvInst.isInEditMode(), false );
		assert.strictEqual( vvInst.expert(), null );
	} );

	QUnit.test( 'stopEditing after startEditing', function( assert ) {
		initVv();

		vvInst.startEditing();
		vvInst.value( stringValue );
		vvInst.stopEditing();

		assert.strictEqual( vvInst.isInEditMode(), false );
		assert.strictEqual( vvInst.expert(), null );
		assert.strictEqual( vvInst.value(), stringValue );
		assert.strictEqual( vvInst.isEmpty(), false );
	} );

	QUnit.test( 'cancelEditing without startEditing', function( assert ) {
		initVv();
		assert.notOk( vvInst.isInEditMode() );

		vvInst.cancelEditing();

		assert.notOk( vvInst.isInEditMode() );
		assert.notOk( vvInst.expert() );
	} );

	QUnit.test( 'cancelEditing after startEditing', function( assert ) {
		initVv();

		vvInst.startEditing();
		vvInst.value( stringValue );
		vvInst.cancelEditing();

		assert.notOk( vvInst.isInEditMode() );
		assert.notOk( vvInst.expert() );
		assert.notEqual( vvInst.value(), stringValue );
		assert.ok( vvInst.isEmpty() );
	} );

	QUnit.test( 'getFormattedValue with DOM', function( assert ) {
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
			assert.strictEqual( vvInst.getFormattedValue(), 'FORMATTED VALUE' );
			sinon.assert.notCalled( vvArgs.htmlFormatter.format );
			sinon.assert.notCalled( vvArgs.plaintextFormatter.format );
			sinon.assert.notCalled( vvArgs.parserStore.getParser );

			vvArgs.htmlFormatter.format.restore();
			vvArgs.plaintextFormatter.format.restore();
			vvArgs.parserStore.getParser.restore();
		} );
	} );

	QUnit.test( 'getFormattedValue without DOM', function( assert ) {
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
			assert.strictEqual( vvInst.getFormattedValue(), 'STRING VALUE' );
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
