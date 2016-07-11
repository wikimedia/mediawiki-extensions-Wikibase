/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.SiteLinkSetsChanger', QUnit.newMwEnvironment() );

	var SUBJECT = wikibase.entityChangers.SiteLinkSetsChanger;

	QUnit.test( 'is a function', function( assert ) {
		assert.expect( 1 );
		assert.equal(
			typeof SUBJECT,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', function( assert ) {
		assert.expect( 1 );
		assert.ok( new SUBJECT() instanceof SUBJECT );
	} );

	QUnit.test( 'save performs correct API call', function( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] ),
			new wb.datamodel.SiteLinkSet()
		);

		assert.ok( api.setSitelink.calledOnce );
	} );

	QUnit.test( 'save correctly handles API response', function( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function() {
				return $.Deferred().resolve( {
					entity: {
						sitelinks: {
							siteId: {
								title: 'pageName'
							},
							lastrevid: 'lastrevid'
						}
					}
				} ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function() { return 0; }, setSitelinksRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] ),
			new wb.datamodel.SiteLinkSet()
		).done( function( savedSiteLinkSet ) {
			QUnit.start();
			assert.ok( savedSiteLinkSet instanceof wb.datamodel.SiteLinkSet );
		} )
		.fail( function() {
			assert.ok( false, 'save failed' );
		} );
	} );

	QUnit.test( 'save correctly passes badges', function( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function() {
				return $.Deferred().resolve( {
					entity: {
						sitelinks: {
							siteId: {
								title: 'pageName',
								badges: [ 'Q2' ]
							},
							lastrevid: 'lastrevid'
						}
					}
				} ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function() { return 0; }, setSitelinksRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName', [ 'Q2' ] ) ] ),
			new wb.datamodel.SiteLinkSet()
		).done( function( savedSiteLinkSet ) {
			QUnit.start();
			assert.deepEqual( savedSiteLinkSet.getItemByKey( 'siteId' ).getBadges(), [ 'Q2' ] );
		} )
		.fail( function() {
			assert.ok( false, 'save failed' );
		} );
	} );

	QUnit.test( 'save correctly handles API failures', function( assert ) {
		assert.expect( 2 );
		var api = {
			setSitelink: sinon.spy( function() {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function() { return 0; }, setSitelinksRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] ),
			new wb.datamodel.SiteLinkSet()
		).done( function( savedSiteLinkSet ) {
			QUnit.start();
			assert.ok( false, 'save should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();
			assert.ok( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
		} );
	} );

	QUnit.test( 'save performs correct API call for removal', function( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet(),
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] )
		);

		assert.ok( api.setSitelink.calledOnce );
	} );

	QUnit.test( 'save correctly handles API response for removal', function( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function() {
				return $.Deferred().resolve( {
					entity: {
						sitelinks: {
							siteId: {
								removed: ''
							},
							lastrevid: 'lastrevid'
						}
					}
				} ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function() { return 0; }, setSitelinksRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet(),
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] )
		).done( function( savedSiteLinkSet ) {
			QUnit.start();
			assert.ok( savedSiteLinkSet instanceof wb.datamodel.SiteLinkSet );
		} )
		.fail( function() {
			assert.ok( false, 'save failed' );
		} );
	} );

	QUnit.test( 'save correctly passes badges for removal', function( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function() {
				return $.Deferred().resolve( {
					entity: {
						sitelinks: {
							siteId: {
								removed: ''
							},
							lastrevid: 'lastrevid'
						}
					}
				} ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function() { return 0; }, setSitelinksRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet(),
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName', [ 'Q2' ] ) ] )
		).done( function( savedSiteLinkSet ) {
			QUnit.start();
			assert.strictEqual( savedSiteLinkSet.getItemByKey( 'siteId' ), null );
		} )
		.fail( function() {
			assert.ok( false, 'save failed' );
		} );
	} );

	QUnit.test( 'save correctly handles API failures for removal', function( assert ) {
		assert.expect( 2 );
		var api = {
			setSitelink: sinon.spy( function() {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function() { return 0; }, setSitelinksRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet(),
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] )
		).done( function( savedSiteLinkSet ) {
			QUnit.start();
			assert.ok( false, 'save should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();
			assert.ok( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
		} );
	} );

} )( sinon, wikibase, jQuery );
