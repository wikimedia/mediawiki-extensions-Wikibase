/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.SiteLinksChanger', QUnit.newMwEnvironment() );

	var SUBJECT = wikibase.entityChangers.SiteLinksChanger;

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

	QUnit.test( 'setSiteLink performs correct API call', function( assert ) {
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

		siteLinksChanger.setSiteLink( new wb.datamodel.SiteLink( 'siteId', 'pageName' ) );

		assert.ok( api.setSitelink.calledOnce );
	} );

	QUnit.test( 'setSiteLink correctly handles API response', function( assert ) {
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

		siteLinksChanger.setSiteLink( new wb.datamodel.SiteLink( 'siteId', 'pageName' ) )
		.done( function( savedSiteLink ) {
			QUnit.start();
			assert.ok( savedSiteLink instanceof wb.datamodel.SiteLink );
		} )
		.fail( function() {
			assert.ok( false, 'setSiteLink failed' );
		} );
	} );

	QUnit.test( 'setSiteLink correctly passes badges', function( assert ) {
		assert.expect( 1 );
		var api = {
			setSitelink: sinon.spy( function() {
				return $.Deferred().resolve( {
					entity: {
						sitelinks: {
							siteId: {
								title: 'pageName',
								badges: [ "Q2" ]
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

		siteLinksChanger.setSiteLink( new wb.datamodel.SiteLink( 'siteId', 'pageName', [ "Q2" ] ) )
		.done( function( savedSiteLink ) {
			QUnit.start();
			assert.deepEqual( savedSiteLink.getBadges(), [ "Q2" ] );
		} )
		.fail( function() {
			assert.ok( false, 'setSiteLink failed' );
		} );
	} );

	QUnit.test( 'setSiteLink correctly handles API failures', function( assert ) {
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

		siteLinksChanger.setSiteLink( new wb.datamodel.SiteLink( 'siteId', 'pageName' ) )
		.done( function( savedSiteLink ) {
			assert.ok( false, 'setSiteLink should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();
			assert.ok( error instanceof wb.api.RepoApiError, 'setSiteLink did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
		} );
	} );

} )( sinon, wikibase, jQuery );
