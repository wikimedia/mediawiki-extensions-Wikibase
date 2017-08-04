/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.SiteLinkSetsChanger', QUnit.newMwEnvironment() );

	var SUBJECT = wikibase.entityChangers.SiteLinkSetsChanger;
	var API_RESPONSE = {
		entity: {
			sitelinks: {
				siteId: {
					title: 'pageName'
				},
				lastrevid: 'lastrevid'
			}
		}
	};

	QUnit.test( 'is a function', function ( assert ) {
		assert.expect( 1 );
		assert.equal(
			typeof SUBJECT,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', function ( assert ) {
		assert.expect( 1 );
		assert.ok( new SUBJECT() instanceof SUBJECT );
	} );

	QUnit.test( 'save performs correct API call', function ( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function () {
				return $.Deferred().resolve( API_RESPONSE ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () { return 0; }, setSitelinksRevision: function () {} },
			new wb.datamodel.Item( 'Q1' )
		);

		var done = assert.async();
		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] ),
			new wb.datamodel.SiteLinkSet()
		).always( function () {
			assert.ok( api.setSitelink.calledOnce );
			done();
		} );

	} );

	QUnit.test( 'save correctly handles API response', function ( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function () {
				return $.Deferred().resolve( API_RESPONSE ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () { return 0; }, setSitelinksRevision: function () {} },
			new wb.datamodel.Item( 'Q1' )
		);

		return siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] ),
			new wb.datamodel.SiteLinkSet()
		).done( function ( savedSiteLinkSet ) {
			assert.ok( savedSiteLinkSet instanceof wb.datamodel.SiteLinkSet );
		} );
	} );

	QUnit.test( 'save correctly passes badges', function ( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function () {
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
			{ getSitelinksRevision: function () { return 0; }, setSitelinksRevision: function () {} },
			new wb.datamodel.Item( 'Q1' )
		);

		return siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName', [ 'Q2' ] ) ] ),
			new wb.datamodel.SiteLinkSet()
		).done( function ( savedSiteLinkSet ) {
			assert.deepEqual( savedSiteLinkSet.getItemByKey( 'siteId' ).getBadges(), [ 'Q2' ] );
		} );
	} );

	QUnit.test( 'save correctly handles API failures', function ( assert ) {
		assert.expect( 2 );
		var api = {
			setSitelink: sinon.spy( function () {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () { return 0; }, setSitelinksRevision: function () {} },
			new wb.datamodel.Item( 'Q1' )
		);

		var done = assert.async();

		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] ),
			new wb.datamodel.SiteLinkSet()
		).done( function ( savedSiteLinkSet ) {
			assert.ok( false, 'save should have failed' );
			done();
		} )
		.fail( function ( error ) {
			assert.ok( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
			done();
		} );
	} );

	QUnit.test( 'save performs correct API call for removal', function ( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function () {
				return $.Deferred().resolve( API_RESPONSE ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () { return 0; }, setSitelinksRevision: function () {} },
			new wb.datamodel.Item( 'Q1' )
		);

		var done = assert.async();
		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet(),
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] )
		).always( function () {
			assert.ok( api.setSitelink.calledOnce );
			done();
		} );
	} );

	QUnit.test( 'save correctly handles API response for removal', function ( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function () {
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
			{ getSitelinksRevision: function () { return 0; }, setSitelinksRevision: function () {} },
			new wb.datamodel.Item( 'Q1' )
		);

		return siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet(),
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] )
		).done( function ( savedSiteLinkSet ) {
			assert.ok( savedSiteLinkSet instanceof wb.datamodel.SiteLinkSet );
		} );
	} );

	QUnit.test( 'save correctly passes badges for removal', function ( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function () {
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
			{ getSitelinksRevision: function () { return 0; }, setSitelinksRevision: function () {} },
			new wb.datamodel.Item( 'Q1' )
		);

		return siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet(),
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName', [ 'Q2' ] ) ] )
		).done( function ( savedSiteLinkSet ) {
			assert.strictEqual( savedSiteLinkSet.getItemByKey( 'siteId' ), null );
		} );
	} );

	QUnit.test( 'save correctly handles API failures for removal', function ( assert ) {
		assert.expect( 2 );
		var api = {
			setSitelink: sinon.spy( function () {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () { return 0; }, setSitelinksRevision: function () {} },
			new wb.datamodel.Item( 'Q1' )
		);

		var done = assert.async();

		siteLinksChanger.save(
			new wb.datamodel.SiteLinkSet(),
			new wb.datamodel.SiteLinkSet( [ new wb.datamodel.SiteLink( 'siteId', 'pageName' ) ] )
		).done( function ( savedSiteLinkSet ) {
			assert.ok( false, 'save should have failed' );
			done();
		} )
		.fail( function ( error ) {
			assert.ok( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
			done();
		} );
	} );

}( sinon, wikibase, jQuery ) );
