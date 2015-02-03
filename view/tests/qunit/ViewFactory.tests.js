( function( $, wikibase, ViewFactory ) {
	'use strict';

	QUnit.module( 'wikibase.view.ViewFactory', QUnit.newMwEnvironment() );

	QUnit.test( 'is constructable', function( assert ) {
		assert.ok( new ViewFactory() instanceof ViewFactory );
	} );

	function getEntityStub( type ) {
		return {
			getType: function() {
				return type;
			}
		};
	}

	QUnit.test( 'getEntityView constructs correct views', function( assert ) {
		var viewFactory = new ViewFactory(),
			fooView = {},
			$dom = {
				data: function( type ) {
					return fooView;
				}
			},
			FooView = $dom.fooview = $.wikibase.fooview = sinon.spy();

		var res = viewFactory.getEntityView( getEntityStub( 'foo' ), $dom );

		assert.strictEqual( res, fooView );
		sinon.assert.calledOnce( FooView );
	} );

	QUnit.test( 'getEntityView throws on incorrect views', function( assert ) {
		var viewFactory = new ViewFactory();

		assert.throws(
			function() {
				viewFactory.getEntityView( getEntityStub( 'unknown' ) );
			},
			new Error( 'View unknownview does not exist' )
		);
	} );

	QUnit.test( 'getEntityView passes correct options to views', function( assert ) {
		var dataTypeStore = {},
			entity = getEntityStub( 'foo' ),
			entityChangersFactory = {},
			entityStore = {},
			expertStore = {},
			formatterStore = {},
			messageProvider = {},
			parserStore = {},
			userLanguages = [],
			viewFactory = new ViewFactory(
				dataTypeStore,
				entityChangersFactory,
				entityStore,
				expertStore,
				formatterStore,
				messageProvider,
				parserStore,
				userLanguages
			),
			$dom = {
				data: function( type ) {}
			},
			FooView = $dom.fooview = $.wikibase.fooview = sinon.spy();

		sinon.spy( wb, 'ValueViewBuilder' );

		viewFactory.getEntityView( entity, $dom );

		sinon.assert.calledWith( wb.ValueViewBuilder,
			expertStore,
			formatterStore,
			parserStore,
			userLanguages[0],
			messageProvider
		);

		sinon.assert.calledWith( FooView, {
			dataTypeStore: dataTypeStore,
			entityChangersFactory: entityChangersFactory,
			entityStore: entityStore,
			languages: userLanguages,
			value: entity,
			valueViewBuilder: wb.ValueViewBuilder.thisValues[0]
		} );

		wb.ValueViewBuilder.restore();
	} );

}( jQuery, wikibase, wikibase.view.ViewFactory ) );
