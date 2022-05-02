( function ( wb, ViewFactory ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	QUnit.module( 'wikibase.view.ViewFactory' );

	function newViewFactory(
		structureEditorFactory,
		contentLanguages,
		dataTypeStore,
		entityIdHtmlFormatter,
		entityIdPlainFormatter,
		propertyDataTypeLookup,
		expertStore,
		formatterFactory,
		messageProvider,
		parserStore,
		userLanguages,
		vocabularyLookupApiUrl
	) {
		return new ViewFactory(
			structureEditorFactory || { getAdder: 'I am a getter' },
			contentLanguages,
			dataTypeStore,
			entityIdHtmlFormatter,
			entityIdPlainFormatter,
			propertyDataTypeLookup,
			expertStore,
			formatterFactory,
			messageProvider || { getMessage: 'I am a getter' },
			parserStore,
			userLanguages || [],
			vocabularyLookupApiUrl,
			'http://commons-api.url/'
		);
	}

	QUnit.test( 'is constructable', function ( assert ) {
		assert.true( newViewFactory() instanceof ViewFactory );
	} );

	function getEntityStub( type ) {
		return {
			getType: function () {
				return type;
			}
		};
	}

	QUnit.test( 'getEntityView constructs correct views', function ( assert ) {
		var viewFactory = newViewFactory(),
			fooView = {},
			$dom = $( '<div>' ),
			FooView = $dom.fooview = $.wikibase.fooview = sinon.spy();
		$dom.data = sinon.spy( function () {
			return fooView;
		} );

		var res = viewFactory.getEntityView( null, getEntityStub( 'foo' ), $dom );

		assert.strictEqual( res, fooView );
		sinon.assert.calledOnce( FooView );
	} );

	QUnit.test( 'getEntityView throws on incorrect views', function ( assert ) {
		var viewFactory = newViewFactory();

		assert.throws(
			function () {
				viewFactory.getEntityView( null, getEntityStub( 'unknown' ) );
			},
			new Error( 'View unknownview does not exist' )
		);
	} );

	QUnit.test( 'getEntityView passes correct options to views', function ( assert ) {
		var entity = getEntityStub( 'foo' ),
			viewFactory = newViewFactory(),
			$dom = $( '<div>' ),
			FooView = $dom.fooview = $.wikibase.fooview = sinon.spy();

		viewFactory.getEntityView( null, entity, $dom );

		sinon.assert.calledWith( FooView, sinon.match( {
			buildEntityTermsView: sinon.match.func,
			buildSitelinkGroupListView: sinon.match.func,
			buildStatementGroupListView: sinon.match.func,
			value: entity
		} ) );
	} );

	QUnit.test( 'getSitelinkGroupListView passes correct options to views', function ( assert ) {
		var sitelinkSet = new datamodel.SiteLinkSet( [] ),
			viewFactory = newViewFactory(),
			$dom = $( '<div>' );

		sinon.spy( $.wikibase, 'sitelinkgrouplistview' );
		$dom.sitelinkgrouplistview = $.wikibase.sitelinkgrouplistview;

		viewFactory.getSitelinkGroupListView( null, sitelinkSet, $dom );

		sinon.assert.calledWith( $.wikibase.sitelinkgrouplistview, sinon.match( {
			value: sitelinkSet
		} ) );

		$.wikibase.sitelinkgrouplistview.restore();
	} );

	QUnit.test( 'getSitelinkGroupView passes correct options to views', function ( assert ) {
		var groupName = 'groupid',
			siteLinks = new datamodel.SiteLinkSet( [] ),
			viewFactory = newViewFactory(),
			$dom = $( '<div>' );

		sinon.stub( $.wikibase, 'sitelinkgroupview' );
		$dom.sitelinkgroupview = $.wikibase.sitelinkgroupview;

		viewFactory.getSitelinkGroupView( null, groupName, siteLinks, $dom );

		sinon.assert.calledWith( $.wikibase.sitelinkgroupview, sinon.match( {
			groupName: groupName,
			value: siteLinks,
			getSiteLinkListView: sinon.match.func
		} ) );

		$.wikibase.sitelinkgroupview.restore();
	} );

	QUnit.test( 'getSiteLinkListView passes correct options to views', function ( assert ) {
		var siteLinks = [],
			viewFactory = newViewFactory(),
			$dom = $( '<div>' );

		sinon.spy( $.wikibase, 'sitelinklistview' );
		$dom.sitelinklistview = $.wikibase.sitelinklistview;

		viewFactory.getSiteLinkListView( null, siteLinks, $dom );

		sinon.assert.calledWith( $.wikibase.sitelinklistview, sinon.match( {
			value: siteLinks
		} ) );

		$.wikibase.sitelinklistview.restore();
	} );

	QUnit.test( 'getStatementGroupListView passes correct options to views', function ( assert ) {
		var entity = new datamodel.Item( 'Q1' ),
			viewFactory = newViewFactory(),
			$dom = $( '<div>' );

		$dom.statementgrouplistview = sinon.stub( $.wikibase, 'statementgrouplistview' );

		viewFactory.getStatementGroupListView( null, entity, $dom );

		sinon.assert.calledWith( $.wikibase.statementgrouplistview, sinon.match( {
			listItemAdapter: sinon.match.instanceOf( $.wikibase.listview.ListItemAdapter )
		} ) );

		$.wikibase.statementgrouplistview.restore();
	} );

	QUnit.test( 'getListItemAdapterForStatementGroupView passes correct options to ListItemAdapter', function ( assert ) {
		var entityId = 'Q1',
			entityIdHtmlFormatter = {},
			viewFactory = newViewFactory( null, null, null, entityIdHtmlFormatter ),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' ),
			value = new datamodel.StatementGroup( 'P1' ),
			htmlIdPrefix = 'X1-Y2';

		viewFactory.getListItemAdapterForStatementGroupView( null, entityId, null, htmlIdPrefix );

		sinon.assert.calledWith(
			ListItemAdapter,
			sinon.match( {
				listItemWidget: $.wikibase.statementgroupview,
				newItemOptionsFn: sinon.match.func
			} )
		);

		var result = ListItemAdapter.args[ 0 ][ 0 ].newItemOptionsFn( value );

		assert.deepEqual(
			result,
			{
				value: value,
				entityIdHtmlFormatter: entityIdHtmlFormatter,
				buildStatementListView: result.buildStatementListView, // Hack
				htmlIdPrefix: htmlIdPrefix
			}
		);

		assert.true( result.buildStatementListView instanceof Function );

		$.wikibase.listview.ListItemAdapter.restore();
	} );

	QUnit.test( 'getStatementListView passes correct options to views', function ( assert ) {
		var value = new datamodel.StatementList( [
				new datamodel.Statement( new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) ) )
			] ),
			entityId = 'entityId',
			viewFactory = newViewFactory(),
			$dom = $( '<div>' );

		sinon.stub( $.wikibase.listview, 'ListItemAdapter' );
		sinon.stub( viewFactory, '_getView' );

		viewFactory.getStatementListView( null, entityId, null, function () {}, value, $dom );

		sinon.assert.calledWith(
			viewFactory._getView,
			sinon.match(
				'statementlistview',
				$dom,
				{
					value: value,
					listItemAdapter: sinon.match.instanceOf( $.wikibase.listview.ListItemAdapter )
				}
			)
		);

		viewFactory._getView.restore();
		$.wikibase.listview.ListItemAdapter.restore();
	} );

	QUnit.test( 'getStatementListView passes null for an empty StatementList', function ( assert ) {
		var value = new datamodel.StatementList(),
			entityId = 'entityId',
			viewFactory = newViewFactory(),
			$dom = $( '<div>' );

		sinon.stub( $.wikibase.listview, 'ListItemAdapter' );
		$dom.statementlistview = sinon.stub( $.wikibase, 'statementlistview' );

		viewFactory.getStatementListView( null, entityId, null, function () {}, value, $dom );

		sinon.assert.calledWith(
			$.wikibase.statementlistview,
			sinon.match( {
				value: null
			} )
		);

		$.wikibase.statementlistview.restore();
		$.wikibase.listview.ListItemAdapter.restore();
	} );

	QUnit.test( 'getStatementListView: given property id, passes id to list adapter', function ( assert ) {
		var factory = newViewFactory(),
			value = new datamodel.StatementList(),
			expectedPropertyId = 'P123',
			$dom = $( '<div>' );

		sinon.stub( factory, 'getAdderWithStartEditing' );
		factory.getAdderWithStartEditing.returns( function () {} );

		sinon.spy( factory, 'getListItemAdapterForStatementView' );
		factory.getStatementListView( null, null, expectedPropertyId, function () {}, value, $dom );

		sinon.assert.calledWith( factory.getListItemAdapterForStatementView,
			sinon.match.any,
			sinon.match.any,
			sinon.match.any,
			expectedPropertyId,
			sinon.match.any
		);
	} );

	QUnit.test( 'getStatementListView: given no property id, gets id from parent data attribute', function ( assert ) {
		var factory = newViewFactory(),
			value = new datamodel.StatementList(),
			$parent = $( '<div>' ).addClass( 'wikibase-statementgroupview' ),
			expectedPropertyId = 'P123',
			$dom = $( '<div>' );

		sinon.stub( factory, 'getAdderWithStartEditing' );
		factory.getAdderWithStartEditing.returns( function () {} );

		$parent.data( 'property-id', expectedPropertyId );
		$parent.append( $dom );

		sinon.spy( factory, 'getListItemAdapterForStatementView' );
		factory.getStatementListView( null, null, null, function () {}, value, $dom );

		sinon.assert.calledWith( factory.getListItemAdapterForStatementView,
			sinon.match.any,
			sinon.match.any,
			sinon.match.any,
			expectedPropertyId,
			sinon.match.any
		);
	} );

	QUnit.test( 'getListItemAdapterForStatementView passes correct options to ListItemAdapter', function ( assert ) {
		var entityId = 'Q1',
			value = null,
			entityIdPlainFormatter = {},
			viewFactory = newViewFactory(
				null,
				null,
				null,
				null,
				entityIdPlainFormatter
			),
			ListItemAdapter = sinon.stub( $.wikibase.listview, 'ListItemAdapter' ),
			dom = null,
			statement = null;

		sinon.stub( viewFactory, '_getView' );

		viewFactory.getListItemAdapterForStatementView(
			null,
			entityId,
			function () {
				return statement;
			}
		);

		sinon.assert.calledWith(
			ListItemAdapter,
			sinon.match( {
				listItemWidget: $.wikibase.statementview,
				getNewItem: sinon.match.func
			} )
		);

		ListItemAdapter.args[ 0 ][ 0 ].getNewItem( value, dom );

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

				getQualifiersListItemAdapter: sinon.match.instanceOf( Function ),
				getReferenceListItemAdapter: sinon.match.instanceOf( Function ),
				buildSnakView: sinon.match.instanceOf( Function ),
				entityIdPlainFormatter: entityIdPlainFormatter,
				guidGenerator: sinon.match.instanceOf( wb.utilities.ClaimGuidGenerator )
			} )
		);

		$.wikibase.listview.ListItemAdapter.restore();
		viewFactory._getView.restore();
	} );

	QUnit.test( 'getListItemAdapterForStatementView passes correct options to views for pre-set property id', function ( assert ) {
		var entityId = 'Q1',
			propertyId = 'propertyId',
			value = null,
			viewFactory = newViewFactory(),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' ),
			dom = {};

		sinon.stub( viewFactory, '_getView' );

		viewFactory.getListItemAdapterForStatementView( null, entityId, function () {}, propertyId );

		ListItemAdapter.args[ 0 ][ 0 ].getNewItem( value, dom );

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

	QUnit.test( 'getListItemAdapterForStatementView passes correct options to views for non-empty StatementList', function ( assert ) {
		var propertyId = 'P1',
			value = new datamodel.Statement( new datamodel.Claim( new datamodel.PropertyNoValueSnak( propertyId ) ) ),
			viewFactory = newViewFactory(),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' ),
			dom = {};

		sinon.stub( viewFactory, '_getView' );

		viewFactory.getListItemAdapterForStatementView( null, 'Q1', function () {}, null );

		ListItemAdapter.args[ 0 ][ 0 ].getNewItem( value, dom );

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

	QUnit.test( 'getListItemAdapterForReferenceView passes correct options to ListItemAdapter', function ( assert ) {
		var viewFactory = newViewFactory(),
			removeCallback = function () {},
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' );

		viewFactory.getListItemAdapterForReferenceView( null, removeCallback );

		sinon.assert.calledWith(
			ListItemAdapter,
			sinon.match( {
				listItemWidget: $.wikibase.referenceview,
				getNewItem: sinon.match.func
			} )
		);

		ListItemAdapter.restore();
	} );

	QUnit.test( 'getReferenceView passes correct options to view', function ( assert ) {
		var value = null,
			viewFactory = newViewFactory(),
			removeCallback = function () {},
			$dom = $( '<div>' ),
			referenceview = sinon.stub( $dom, 'referenceview' );

		viewFactory.getReferenceView( null, removeCallback, value, $dom );

		sinon.assert.calledWith(
			referenceview,
			sinon.match( {
				getAdder: sinon.match.func,
				getListItemAdapter: sinon.match.func,
				getReferenceRemover: sinon.match.func,
				removeCallback: sinon.match.func,
				value: value || null
			} )
		);

		referenceview.restore();
	} );

	QUnit.test( 'getListItemAdapterForSnakListView passes correct options to ListItemAdapter', function ( assert ) {
		var viewFactory = newViewFactory(),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' );

		viewFactory.getListItemAdapterForSnakListView();

		sinon.assert.calledWith(
			ListItemAdapter,
			sinon.match( {
				listItemWidget: $.wikibase.snaklistview,
				getNewItem: sinon.match.func
			} )
		);

		ListItemAdapter.restore();
	} );

	QUnit.test( 'getSnakListView passes correct options to view', function ( assert ) {
		var value = null,
			viewFactory = newViewFactory(),
			$dom = $( '<div>' ),
			stub = sinon.stub( $dom, 'snaklistview' );

		viewFactory.getSnakListView( {}, null, $dom, value );

		sinon.assert.calledWith(
			stub,
			sinon.match( {
				getListItemAdapter: sinon.match.func,
				singleProperty: true,
				value: value || undefined
			} )
		);

		stub.restore();
	} );

	QUnit.test( 'getListItemAdapterForSnakView passes correct options to ListItemAdapter', function ( assert ) {
		var viewFactory = newViewFactory(),
			ListItemAdapter = sinon.spy( $.wikibase.listview, 'ListItemAdapter' );

		viewFactory.getListItemAdapterForSnakView();

		sinon.assert.calledWith(
			ListItemAdapter,
			sinon.match( {
				listItemWidget: $.wikibase.snakview,
				getNewItem: sinon.match.func
			} )
		);

		$.wikibase.listview.ListItemAdapter.restore();
	} );

	QUnit.test( 'getSnakView passes correct options to view', function ( assert ) {
		var contentLanguages = {},
			value = null,
			dataTypeStore = {},
			entityIdHtmlFormatter = {},
			entityIdPlainFormatter = {},
			propertyDataTypeStore = {},
			expertStore = {},
			formatterFactory = {},
			messageProvider = { getMessage: 'I am a getter' },
			parserStore = {},
			userLanguages = [],
			viewFactory = newViewFactory(
				null,
				contentLanguages,
				dataTypeStore,
				entityIdHtmlFormatter,
				entityIdPlainFormatter,
				propertyDataTypeStore,
				expertStore,
				formatterFactory,
				messageProvider,
				parserStore,
				userLanguages
			),
			options = {},
			$dom = $( '<div>' );

		$dom.snakview = sinon.stub( $.wikibase, 'snakview' );
		viewFactory.getSnakView( null, false, options, value, $dom );

		sinon.assert.calledWith(
			$.wikibase.snakview,
			sinon.match( {
				value: value || undefined,
				locked: options.locked,
				autoStartEditing: options.autoStartEditing,
				dataTypeStore: dataTypeStore,
				entityIdHtmlFormatter: entityIdHtmlFormatter,
				entityIdPlainFormatter: entityIdPlainFormatter,
				propertyDataTypeStore: propertyDataTypeStore,
				drawProperty: false
			} )
		);

		$.wikibase.snakview.restore();
	} );

	QUnit.test( 'getEntityTermsView passes correct options to views', function ( assert ) {
		var fingerprint = new datamodel.Fingerprint(),
			message = 'message',
			messageProvider = { getMessage: function () { return message; } },
			userLanguages = [],
			viewFactory = newViewFactory(
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				messageProvider,
				null,
				userLanguages
			),
			$dom = $( '<div>' );

		sinon.spy( $.wikibase, 'entitytermsview' );
		$dom.entitytermsview = $.wikibase.entitytermsview;

		viewFactory.getEntityTermsView( null, fingerprint, $dom );

		sinon.assert.calledWith( $.wikibase.entitytermsview, sinon.match( {
			value: fingerprint,
			userLanguages: userLanguages,
			helpMessage: message
		} ) );

		$.wikibase.entitytermsview.restore();
	} );

}( wikibase, wikibase.view.ViewFactory ) );
