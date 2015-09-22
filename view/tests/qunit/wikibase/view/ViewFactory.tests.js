( function( $, sinon, QUnit, wb, ViewFactory ) {
	'use strict';

	QUnit.module( 'wikibase.view.ViewFactory' );

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
		var entityStore = new wb.store.EntityStore(),
			viewFactory = new ViewFactory( null, null, null, null, null, entityStore ),
			fooView = {},
			$dom = $( '<div/>' ),
			FooView = $dom.fooview = $.wikibase.fooview = sinon.spy();
		$dom.data = sinon.spy( function() { return fooView; } );

		var res = viewFactory.getEntityView( getEntityStub( 'foo' ), $dom );

		assert.strictEqual( res, fooView );
		sinon.assert.calledOnce( FooView );
	} );

	QUnit.test( 'getEntityView throws on incorrect views', function( assert ) {
		var entityStore = new wb.store.EntityStore(),
			viewFactory = new ViewFactory( null, null, null, null, null, entityStore );

		assert.throws(
			function() {
				viewFactory.getEntityView( getEntityStub( 'unknown' ) );
			},
			new Error( 'View unknownview does not exist' )
		);
	} );

	QUnit.test( 'getEntityView passes correct options to views', function( assert ) {
		var entity = getEntityStub( 'foo' ),
			viewFactory = new ViewFactory(),
			$dom = $( '<div/>' ),
			FooView = $dom.fooview = $.wikibase.fooview = sinon.spy();

		viewFactory.getEntityView( entity, $dom );

		sinon.assert.calledWith( FooView, sinon.match( {
			buildEntityTermsView: sinon.match.func,
			buildSitelinkGroupListView: sinon.match.func,
			buildStatementGroupListView: sinon.match.func,
			value: entity
		} ) );
	} );

	QUnit.test( 'getSitelinkGroupListView passes correct options to views', function( assert ) {
		var sitelinkSet = new wb.datamodel.SiteLinkSet( [] ),
			siteLinksChanger = {},
			entityChangersFactory = { getSiteLinksChanger: function() { return siteLinksChanger; } },
			entityIdPlainFormatter = {},
			viewFactory = new ViewFactory(
				null,
				null,
				entityChangersFactory,
				null,
				entityIdPlainFormatter
			),
			$dom = $( '<div/>' );

		sinon.spy( $.wikibase, 'sitelinkgrouplistview' );
		$dom.sitelinkgrouplistview = $.wikibase.sitelinkgrouplistview;

		viewFactory.getSitelinkGroupListView( sitelinkSet, $dom );

		sinon.assert.calledWith( $.wikibase.sitelinkgrouplistview, sinon.match( {
			value: sitelinkSet,
			entityIdPlainFormatter: entityIdPlainFormatter,
			siteLinksChanger: siteLinksChanger
		} ) );

		$.wikibase.sitelinkgrouplistview.restore();
	} );

	QUnit.test( 'getStatementGroupListView passes correct options to views', function( assert ) {
		var entity = new wb.datamodel.Item( 'Q1' ),
			viewFactory = new ViewFactory(),
			$dom = $( '<div/>' );

		$dom.statementgrouplistview = sinon.stub( $.wikibase, 'statementgrouplistview' );

		viewFactory.getStatementGroupListView( entity, $dom );

		sinon.assert.calledWith( $.wikibase.statementgrouplistview, sinon.match( {
			value: entity.getStatements(),
			listItemAdapter: sinon.match.instanceOf( $.wikibase.listview.ListItemAdapter )
		} ) );

		$.wikibase.statementgrouplistview.restore();
	} );

	QUnit.test( 'getListItemAdapterForStatementGroupView passes correct options to ListItemAdapter', function( assert ) {
		var entityId = 'Q1',
			entityIdHtmlFormatter = {},
			viewFactory = new ViewFactory( null, null, null, entityIdHtmlFormatter ),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' ),
			value = {};

		viewFactory.getListItemAdapterForStatementGroupView( entityId );

		sinon.assert.calledWith(
			ListItemAdapter,
			sinon.match( {
				listItemWidget: $.wikibase.statementgroupview,
				newItemOptionsFn: sinon.match.func
			} )
		);

		var result = ListItemAdapter.args[0][0].newItemOptionsFn( value );

		assert.deepEqual(
			result,
			{
				value: value,
				entityIdHtmlFormatter: entityIdHtmlFormatter,
				buildStatementListView: result.buildStatementListView // Hack
			}
		);

		assert.ok( result.buildStatementListView instanceof Function );

		$.wikibase.listview.ListItemAdapter.restore();
	} );

	QUnit.test( 'getStatementListView passes correct options to views', function( assert ) {
		var contentLanguages = {},
			entityId = 'Q1',
			value = new wb.datamodel.StatementList(),
			dataTypeStore = {},
			entityChangersFactory = {
				getClaimsChanger: function() {},
				getReferencesChanger: function() {}
			},
			entityIdHtmlFormatter = {},
			entityIdPlainFormatter = {},
			entityStore = {},
			expertStore = {},
			formatterStore = {},
			messageProvider = {},
			parserStore = {},
			userLanguages = [],
			viewFactory = new ViewFactory(
				contentLanguages,
				dataTypeStore,
				entityChangersFactory,
				entityIdHtmlFormatter,
				entityIdPlainFormatter,
				entityStore,
				expertStore,
				formatterStore,
				messageProvider,
				parserStore,
				userLanguages
			),
			$dom = $( '<div/>' );

		$dom.statementlistview = sinon.spy( $.wikibase, 'statementlistview' );
		sinon.spy( wb, 'ValueViewBuilder' );

		viewFactory.getStatementListView( entityId, value, $dom );

		sinon.assert.calledWith(
			$.wikibase.statementlistview,
			sinon.match( {
				value: value,
				claimGuidGenerator: sinon.match.instanceOf( wb.utilities.ClaimGuidGenerator ),
				dataTypeStore: dataTypeStore,
				entityStore: entityStore,
				entityIdHtmlFormatter: entityIdHtmlFormatter,
				entityIdPlainFormatter: entityIdPlainFormatter,
				valueViewBuilder: wb.ValueViewBuilder.returnValues[0],
				entityChangersFactory: entityChangersFactory
			} )
		);

		sinon.assert.calledWith( wb.ValueViewBuilder,
			expertStore,
			formatterStore,
			parserStore,
			userLanguages[0],
			messageProvider,
			contentLanguages
		);

		wb.ValueViewBuilder.restore();
		$.wikibase.statementlistview.restore();
	} );

	QUnit.test( 'getEntityTermsView passes correct options to views', function( assert ) {
		var contentLanguages = [],
			fingerprint = new wb.datamodel.Fingerprint(),
			entityChangersFactory = {},
			message = 'message',
			messageProvider = { getMessage: function() { return message; } },
			userLanguages = [],
			viewFactory = new ViewFactory(
				contentLanguages,
				null,
				entityChangersFactory,
				null,
				null,
				null,
				null,
				null,
				messageProvider,
				null,
				userLanguages
			),
			$dom = $( '<div/>' );

		sinon.spy( $.wikibase, 'entitytermsview' );
		$dom.entitytermsview = $.wikibase.entitytermsview;

		viewFactory.getEntityTermsView( fingerprint, $dom );

		sinon.assert.calledWith( $.wikibase.entitytermsview, sinon.match( {
			value: [],
			entityChangersFactory: entityChangersFactory,
			helpMessage: message
		} ) );

		$.wikibase.entitytermsview.restore();
	} );

}( jQuery, sinon, QUnit, wikibase, wikibase.view.ViewFactory ) );
