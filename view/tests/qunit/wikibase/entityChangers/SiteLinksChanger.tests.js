/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.SiteLinksChanger', QUnit.newMwEnvironment() );

	var SUBJECT = wikibase.entityChangers.SiteLinksChanger,
		datamodel = require( 'wikibase.datamodel' );

	QUnit.test( 'is a function', function ( assert ) {
		assert.strictEqual(
			typeof SUBJECT,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', function ( assert ) {
		assert.true( new SUBJECT() instanceof SUBJECT );
	} );

	QUnit.test( 'setSiteLink performs correct API call', function ( assert ) {
		var api = {
			setSitelink: sinon.spy( function () {
				return $.Deferred().promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () { return 0; } },
			new datamodel.Item( 'Q1' )
		);

		siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', 'pageName' ) );

		assert.true( api.setSitelink.calledOnce );
	} );

	QUnit.test( 'setSiteLink correctly handles API response', function ( assert ) {
		var api = {
			setSitelink: sinon.spy( function () {
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
			{ getSitelinksRevision: function () { return 0; }, setSitelinksRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		return siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', 'pageName' ) )
		.done( function ( savedSiteLink ) {
			assert.true( savedSiteLink instanceof datamodel.SiteLink );
		} );
	} );

	QUnit.test( 'setSiteLink correctly passes badges', function ( assert ) {
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
			new datamodel.Item( 'Q1' )
		);

		return siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', 'pageName', [ 'Q2' ] ) )
		.done( function ( savedSiteLink ) {
			assert.deepEqual( savedSiteLink.getBadges(), [ 'Q2' ] );
		} );
	} );

	QUnit.test( 'setSiteLink correctly handles API failures', function ( assert ) {
		var api = {
			setSitelink: sinon.spy( function () {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () { return 0; }, setSitelinksRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		var done = assert.async();

		siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', 'pageName' ) )
		.done( function ( savedSiteLink ) {
			assert.true( false, 'setSiteLink should have failed' );
		} )
		.fail( function ( error ) {
			assert.true( error instanceof wb.api.RepoApiError, 'setSiteLink did not fail with a RepoApiError' );
			assert.strictEqual( error.code, 'errorCode' );
		} )
		.always( done );
	} );

	QUnit.test( 'setSiteLink performs correct API call for remove', function ( assert ) {
		var api = {
			setSitelink: sinon.spy( function () {
				return $.Deferred().promise();
			} )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () { return 0; } },
			new datamodel.Item( 'Q1' )
		);

		siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', '' ) );

		assert.true( api.setSitelink.calledOnce );
	} );

	QUnit.test( 'setSiteLink correctly handles API response for remove', function ( assert ) {
		var api = {
			setSitelink: sinon.spy( function () {
				return $.Deferred().resolve( {
					entity: {
						sitelinks: {
							siteId: {
								title: 'pageName',
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
			new datamodel.Item( 'Q1' )
		);

		return siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', '' ) )
		.done( function ( savedSiteLink ) {
			assert.strictEqual( savedSiteLink, null );
		} );
	} );

}( wikibase ) );
