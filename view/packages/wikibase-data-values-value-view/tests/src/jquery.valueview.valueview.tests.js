/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */

( function( $, vv, dv, vf, vp, sinon, QUnit, CompletenessTest ) {
	'use strict';

	var $vvElem;
	var vvInst;
	var stringValue = new dv.StringValue( 'STRING VALUE' );

	QUnit.module( 'jquery.valueview.valueview' );

	if( QUnit.urlParams.completenesstest ) {
		new CompletenessTest( vv.prototype, function( cur, tester, path ) {
			// Don't check code coverage for options
			return path[path.length - 1] === 'options';
		} );
	}

	function initVv( opts ) {
		opts = $.extend( {}, initVv.defaultOpts, opts );

		$vvElem = opts.generateDom();
		vvInst = $vvElem.valueview( opts.vvArgs ).data( 'valueview' );

		if( opts.withExpert ) {
			vvInst.startEditing();
		}
	}
	initVv.defaultOpts = {
		withExpert: false,
		generateDom: function() { return $( '<div/>' ); },
		vvArgs: {
			expertStore: new vv.ExpertStore(),
			formatterStore: new vf.ValueFormatterStore( vf.NullFormatter ),
			parserStore: new vp.ValueParserStore( vp.NullParser )
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

		assert.ok( !$vvElem.hasClass( vvInst.widgetBaseClass ) );
	} );

	QUnit.test( 'destroy with expert', function( assert ) {
		initVv( { withExpert: true } );

		vvInst.destroy();

		assert.ok( !vvInst.expert() );
	} );

	QUnit.test( 'startEditing', function( assert ) {
		initVv();

		vvInst.startEditing();

		assert.ok( vvInst.isInEditMode() );
	} );

	QUnit.test( 'stopEditing without startEditing', function( assert ) {
		initVv();
		assert.ok( !vvInst.isInEditMode() );

		vvInst.stopEditing();

		assert.ok( !vvInst.isInEditMode() );
		assert.ok( !vvInst.expert() );
	} );

	QUnit.test( 'stopEditing after startEditing', function( assert ) {
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
		initVv();
		assert.ok( !vvInst.isInEditMode() );

		vvInst.cancelEditing();

		assert.ok( !vvInst.isInEditMode() );
		assert.ok( !vvInst.expert() );
	} );

	QUnit.test( 'cancelEditing after startEditing', function( assert ) {
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
		var vvArgs = $.extend( {
			value: stringValue
		}, initVv.defaultOpts.vvArgs );
		sinon.spy( vvArgs.formatterStore, 'getFormatter' );
		sinon.spy( vvArgs.parserStore, 'getParser' );
		initVv( {
			generateDom: function() {
				return jQuery( '<div/>' ).append( 'FORMATTED VALUE' );
			},
			vvArgs: vvArgs
		} );

		vvInst.draw();

		assert.equal( vvInst.getFormattedValue(), 'FORMATTED VALUE' );
		sinon.assert.notCalled( vvArgs.formatterStore.getFormatter );
		sinon.assert.notCalled( vvArgs.parserStore.getParser );

		vvArgs.formatterStore.getFormatter.restore();
		vvArgs.parserStore.getParser.restore();
	} );

	QUnit.test( 'getFormattedValue without DOM', function( assert ) {
		var vvArgs = $.extend( {
			value: stringValue
		}, initVv.defaultOpts.vvArgs );
		sinon.spy( vvArgs.formatterStore, 'getFormatter' );
		sinon.spy( vvArgs.parserStore, 'getParser' );
		initVv( {
			vvArgs: vvArgs
		} );

		vvInst.draw();

		assert.equal( vvInst.getFormattedValue(), 'STRING VALUE' );
		sinon.assert.calledOnce( vvArgs.formatterStore.getFormatter );
		sinon.assert.notCalled( vvArgs.parserStore.getParser );

		vvArgs.formatterStore.getFormatter.restore();
		vvArgs.parserStore.getParser.restore();
	} );

	QUnit.test( 'disable', function( assert ) {
		initVv();

		vvInst.disable();

		assert.ok( vvInst.isDisabled() );
		assert.ok( vvInst.option( 'disabled') );
	} );

	QUnit.test( 'enable', function( assert ) {
		initVv();

		vvInst.enable();

		assert.ok( !vvInst.isDisabled() );
		assert.ok( !vvInst.option( 'disabled') );
	} );

} )( jQuery, jQuery.valueview, dataValues, valueFormatters, valueParsers, sinon, QUnit, CompletenessTest );
