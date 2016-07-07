( function( $, sinon, QUnit, wb, ViewFactory ) {
	'use strict';

	QUnit.module( 'wikibase.view.ViewFactory' );

	QUnit.test( 'is constructable', function( assert ) {
		assert.expect( 1 );
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
		assert.expect( 2 );
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
		assert.expect( 1 );
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
		assert.expect( 1 );
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
		assert.expect( 1 );
		var sitelinkSet = new wb.datamodel.SiteLinkSet( [] ),
			viewFactory = new ViewFactory(),
			$dom = $( '<div/>' );

		sinon.spy( $.wikibase, 'sitelinkgrouplistview' );
		$dom.sitelinkgrouplistview = $.wikibase.sitelinkgrouplistview;

		viewFactory.getSitelinkGroupListView( sitelinkSet, $dom );

		sinon.assert.calledWith( $.wikibase.sitelinkgrouplistview, sinon.match( {
			value: sitelinkSet
		} ) );

		$.wikibase.sitelinkgrouplistview.restore();
	} );

	QUnit.test( 'getSitelinkGroupView passes correct options to views', function( assert ) {
		assert.expect( 1 );
		var groupName = 'groupid',
			siteLinks = [],
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
			eventSingletonManager = new $.util.EventSingletonManager(),
			$dom = $( '<div/>' );

		sinon.spy( $.wikibase, 'sitelinkgroupview' );
		$dom.sitelinkgroupview = $.wikibase.sitelinkgroupview;

		viewFactory.getSitelinkGroupView( eventSingletonManager, groupName, siteLinks, $dom );

		sinon.assert.calledWith( $.wikibase.sitelinkgroupview, sinon.match( {
			groupName: groupName,
			value: siteLinks,
			entityIdPlainFormatter: entityIdPlainFormatter,
			eventSingletonManager: eventSingletonManager,
			siteLinksChanger: siteLinksChanger
		} ) );

		$.wikibase.sitelinkgroupview.restore();
	} );

	QUnit.test( 'getStatementGroupListView passes correct options to views', function( assert ) {
		assert.expect( 1 );
		var entity = new wb.datamodel.Item( 'Q1' ),
			viewFactory = new ViewFactory(),
			$dom = $( '<div/>' );

		$dom.statementgrouplistview = sinon.stub( $.wikibase, 'statementgrouplistview' );

		viewFactory.getStatementGroupListView( entity, $dom );

		sinon.assert.calledWith( $.wikibase.statementgrouplistview, sinon.match( {
			listItemAdapter: sinon.match.instanceOf( $.wikibase.listview.ListItemAdapter )
		} ) );

		$.wikibase.statementgrouplistview.restore();
	} );

	QUnit.test( 'getListItemAdapterForStatementGroupView passes correct options to ListItemAdapter', function( assert ) {
		assert.expect( 3 );
		var entityId = 'Q1',
			entityIdHtmlFormatter = {},
			viewFactory = new ViewFactory( null, null, null, entityIdHtmlFormatter ),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' ),
			value = new wb.datamodel.StatementGroup( 'P1' );

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
		assert.expect( 1 );
		var value = new wb.datamodel.StatementList( [
				new wb.datamodel.Statement( new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ) )
			] ),
			entityId = 'entityId',
			claimsChanger = {},
			entityChangersFactory = {
				getClaimsChanger: function() { return claimsChanger; }
			},
			viewFactory = new ViewFactory( null, null, entityChangersFactory ),
			$dom = $( '<div/>' );

		sinon.stub( $.wikibase.listview, 'ListItemAdapter' );
		sinon.stub( viewFactory, '_getView' );

		viewFactory.getStatementListView( entityId, null, function () {}, value, $dom );

		sinon.assert.calledWith(
			viewFactory._getView,
			sinon.match(
				'statementlistview',
				$dom,
				{
					value: value,
					claimsChanger: claimsChanger,
					listItemAdapter: sinon.match.instanceOf( $.wikibase.listview.ListItemAdapter )
				}
			)
		);

		viewFactory._getView.restore();
		$.wikibase.listview.ListItemAdapter.restore();
	} );

	QUnit.test( 'getStatementListView passes null for an empty StatementList', function( assert ) {
		assert.expect( 1 );
		var value = new wb.datamodel.StatementList(),
			entityId = 'entityId',
			claimsChanger = {},
			entityChangersFactory = {
				getClaimsChanger: function() { return claimsChanger; }
			},
			viewFactory = new ViewFactory( null, null, entityChangersFactory ),
			$dom = $( '<div/>' );

		sinon.stub( $.wikibase.listview, 'ListItemAdapter' );
		$dom.statementlistview = sinon.stub( $.wikibase, 'statementlistview' );

		viewFactory.getStatementListView( entityId, null, function () {}, value, $dom );

		sinon.assert.calledWith(
			$.wikibase.statementlistview,
			sinon.match( {
				value: null
			} )
		);

		$.wikibase.statementlistview.restore();
		$.wikibase.listview.ListItemAdapter.restore();
	} );

	QUnit.test( 'getListItemAdapterForStatementView passes correct options to ListItemAdapter', function( assert ) {
		assert.expect( 2 );
		var entityId = 'Q1',
			value = null,
			claimsChanger = {},
			entityChangersFactory = {
				getClaimsChanger: function() { return claimsChanger; }
			},
			entityIdPlainFormatter = {},
			viewFactory = new ViewFactory( null, null, entityChangersFactory, null, entityIdPlainFormatter ),
			ListItemAdapter = sinon.stub( $.wikibase.listview, 'ListItemAdapter' ),
			dom = null,
			statement = {};

		sinon.stub( viewFactory, '_getView' );

		viewFactory.getListItemAdapterForStatementView( entityId, function () { return statement; } );

		sinon.assert.calledWith(
			ListItemAdapter,
			sinon.match( {
				listItemWidget: $.wikibase.statementview,
				getNewItem: sinon.match.func
			} )
		);

		ListItemAdapter.args[0][0].getNewItem( value, dom );

		sinon.assert.calledWith(
			viewFactory._getView,
			'statementview',
			sinon.match.instanceOf( $ ),
			sinon.match( {
				value: statement,
				predefined: {
					mainSnak: {
						property: undefined
					}
				},
				locked: {
					mainSnak: {
						property: false
					}
				},

				buildReferenceListItemAdapter: sinon.match.instanceOf( Function ),
				buildSnakView: sinon.match.instanceOf( Function ),
				claimsChanger: claimsChanger,
				entityIdPlainFormatter: entityIdPlainFormatter,
				guidGenerator: sinon.match.instanceOf( wb.utilities.ClaimGuidGenerator ),
				qualifiersListItemAdapter: sinon.match.instanceOf( ListItemAdapter )
			} )
		);

		$.wikibase.listview.ListItemAdapter.restore();
		viewFactory._getView.restore();
	} );

	QUnit.test( 'getListItemAdapterForStatementView passes correct options to views for pre-set property id', function( assert ) {
		assert.expect( 1 );
		var entityId = 'Q1',
			propertyId = 'propertyId',
			value = null,
			entityChangersFactory = {
				getClaimsChanger: function() { return {}; }
			},
			viewFactory = new ViewFactory( null, null, entityChangersFactory ),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' ),
			dom = {};

		sinon.stub( viewFactory, '_getView' );

		viewFactory.getListItemAdapterForStatementView( entityId, function () {}, propertyId );

		ListItemAdapter.args[0][0].getNewItem( value, dom );

		sinon.assert.calledWith(
			viewFactory._getView,
			'statementview',
			sinon.match.instanceOf( $ ),
			sinon.match( {
				predefined: { mainSnak: { property: propertyId } },
				locked: { mainSnak: { property: true } }
			} )
		);

		$.wikibase.listview.ListItemAdapter.restore();
		viewFactory._getView.restore();
	} );

	QUnit.test( 'getListItemAdapterForStatementView passes correct options to views for non-empty StatementList', function( assert ) {
		assert.expect( 1 );
		var entityId = new wb.datamodel.EntityId( 'type', 1 ),
			propertyId = 'P1',
			value = new wb.datamodel.Statement( new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( propertyId ) ) ),
			entityChangersFactory = {
				getClaimsChanger: function() { return {}; }
			},
			viewFactory = new ViewFactory( null, null, entityChangersFactory ),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' ),
			dom = {};

		sinon.stub( viewFactory, '_getView' );

		viewFactory.getListItemAdapterForStatementView( entityId, function () {}, null );

		ListItemAdapter.args[0][0].getNewItem( value, dom );

		sinon.assert.calledWith(
			viewFactory._getView,
			'statementview',
			sinon.match.instanceOf( $ ),
			sinon.match( {
				predefined: { mainSnak: { property: propertyId } },
				locked: { mainSnak: { property: true } }
			} )
		);

		$.wikibase.listview.ListItemAdapter.restore();
		viewFactory._getView.restore();
	} );

	QUnit.test( 'getListItemAdapterForReferenceView passes correct options to ListItemAdapter', function( assert ) {
		assert.expect( 3 );
		var contentLanguages = {},
			value = null,
			claimsChanger = {},
			entityChangersFactory = {
				getClaimsChanger: function() { return claimsChanger; }
			},
			entityIdHtmlFormatter = {},
			entityIdPlainFormatter = {},
			entityStore = {},
			expertStore = {},
			formatterFactory = {},
			messageProvider = {},
			parserStore = {},
			userLanguages = [],
			viewFactory = new ViewFactory(
				contentLanguages,
				null,
				entityChangersFactory,
				entityIdHtmlFormatter,
				entityIdPlainFormatter,
				entityStore,
				expertStore,
				formatterFactory,
				messageProvider,
				parserStore,
				userLanguages
			),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' );

		viewFactory.getListItemAdapterForReferenceView();

		sinon.assert.calledWith(
			ListItemAdapter,
			sinon.match( {
				listItemWidget: $.wikibase.referenceview,
				newItemOptionsFn: sinon.match.func
			} )
		);

		var result = ListItemAdapter.args[0][0].newItemOptionsFn( value );

		assert.deepEqual(
			result,
			{
				value: value || null,
				listItemAdapter: result.listItemAdapter // Hack
			}
		);

		assert.ok( result.listItemAdapter instanceof $.wikibase.listview.ListItemAdapter );

		$.wikibase.listview.ListItemAdapter.restore();
	} );

	QUnit.test( 'getListItemAdapterForSnakListView passes correct options to ListItemAdapter', function( assert ) {
		assert.expect( 3 );
		var value = null,
			viewFactory = new ViewFactory(),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' );

		viewFactory.getListItemAdapterForSnakListView();

		sinon.assert.calledWith(
			ListItemAdapter,
			sinon.match( {
				listItemWidget: $.wikibase.snaklistview,
				newItemOptionsFn: sinon.match.func
			} )
		);

		var result = ListItemAdapter.args[0][0].newItemOptionsFn( value );

		assert.deepEqual(
			result,
			{
				value: value || undefined,
				singleProperty: true,
				listItemAdapter: result.listItemAdapter // Hack
			}
		);

		assert.ok( result.listItemAdapter instanceof $.wikibase.listview.ListItemAdapter );

		$.wikibase.listview.ListItemAdapter.restore();
	} );

	QUnit.test( 'getListItemAdapterForSnakView passes correct options to ListItemAdapter', function( assert ) {
		assert.expect( 3 );
		var contentLanguages = {},
			value = null,
			dataTypeStore = {},
			claimsChanger = {},
			entityChangersFactory = {
				getClaimsChanger: function() { return claimsChanger; }
			},
			entityIdHtmlFormatter = {},
			entityIdPlainFormatter = {},
			entityStore = {},
			expertStore = {},
			formatterFactory = {},
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
				formatterFactory,
				messageProvider,
				parserStore,
				userLanguages
			),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' );

		viewFactory.getListItemAdapterForSnakView();

		sinon.assert.calledWith(
			ListItemAdapter,
			sinon.match( {
				listItemWidget: $.wikibase.snakview,
				newItemOptionsFn: sinon.match.func
			} )
		);

		sinon.spy( wb, 'ValueViewBuilder' );

		var result = ListItemAdapter.args[0][0].newItemOptionsFn( value );

		assert.deepEqual(
			result,
			{
				value: value || {
					property: null,
					snaktype: 'value'
				},
				autoStartEditing: undefined,
				dataTypeStore: dataTypeStore,
				drawProperty: true,
				entityIdHtmlFormatter: entityIdHtmlFormatter,
				entityIdPlainFormatter: entityIdPlainFormatter,
				entityStore: entityStore,
				locked: {
					property: false
				},
				valueViewBuilder: wb.ValueViewBuilder.returnValues[0]
			}
		);

		sinon.assert.calledWith( wb.ValueViewBuilder,
			expertStore,
			formatterFactory,
			parserStore,
			userLanguages[0],
			messageProvider,
			contentLanguages
		);

		wb.ValueViewBuilder.restore();
		$.wikibase.listview.ListItemAdapter.restore();
	} );

	QUnit.test( 'getSnakView passes correct options to ListItemAdapter', function( assert ) {
		assert.expect( 2 );
		var contentLanguages = {},
			value = null,
			dataTypeStore = {},
			claimsChanger = {},
			entityChangersFactory = {
				getClaimsChanger: function() { return claimsChanger; }
			},
			entityIdHtmlFormatter = {},
			entityIdPlainFormatter = {},
			entityStore = {},
			expertStore = {},
			formatterFactory = {},
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
				formatterFactory,
				messageProvider,
				parserStore,
				userLanguages
			),
			options = {},
			$dom = $( '<div/>' );

		$dom.snakview = sinon.stub( $.wikibase, 'snakview' );

		sinon.spy( wb, 'ValueViewBuilder' );

		viewFactory.getSnakView( false, options, value, $dom );

		sinon.assert.calledWith(
			$.wikibase.snakview,
			sinon.match( {
				value: value || undefined,
				locked: options.locked,
				autoStartEditing: options.autoStartEditing,
				dataTypeStore: dataTypeStore,
				entityIdHtmlFormatter: entityIdHtmlFormatter,
				entityIdPlainFormatter: entityIdPlainFormatter,
				entityStore: entityStore,
				valueViewBuilder: wb.ValueViewBuilder.returnValues[0],
				drawProperty: false
			} )
		);

		sinon.assert.calledWith( wb.ValueViewBuilder,
			expertStore,
			formatterFactory,
			parserStore,
			userLanguages[0],
			messageProvider,
			contentLanguages
		);

		wb.ValueViewBuilder.restore();

		$.wikibase.snakview.restore();
	} );

	QUnit.test( 'getEntityTermsView passes correct options to views', function( assert ) {
		assert.expect( 1 );
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
			value: fingerprint,
			userLanguages: userLanguages,
			entityChangersFactory: entityChangersFactory,
			helpMessage: message
		} ) );

		$.wikibase.entitytermsview.restore();
	} );

}( jQuery, sinon, QUnit, wikibase, wikibase.view.ViewFactory ) );
