/* eslint no-console: "off" */
import { Reference } from '@wmde/wikibase-datamodel-types';
import ApiReadingEntityRepository from '@/data-access/ApiReadingEntityRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import Entity from '@/datamodel/Entity';
import Entities from '@/mock-data/data/Q42.data.json';
import EditFlow from '@/definitions/EditFlow';
import getOrEnforceUrlParameter from '@/mock-data/getOrEnforceUrlParameter';
import ServiceContainer from '@/services/ServiceContainer';
import EntityRevision from '@/datamodel/EntityRevision';
import { initEvents } from '@/events';
import MessageKeys from '@/definitions/MessageKeys';
import clone from '@/store/clone';
import messages from '@/mock-data/messages';
import cssjanus from 'cssjanus';
import { Component, createApp, App } from 'vue';
import { launch } from '@/main';

const services = new ServiceContainer();

services.set( 'readingEntityRepository', new ApiReadingEntityRepository(
	{
		get: () => new Promise( ( resolve ) => {
			setTimeout( () => {
				resolve( Entities );
			}, 1100 );
		} ),
	},
) );

services.set( 'writingEntityRepository', {
	saveEntity( entity: Entity, base?: EntityRevision ): Promise<EntityRevision> {
		console.info( 'saving', entity );
		const result: EntityRevision = {
			entity: clone( entity ),
			revisionId: ( base?.revisionId || 0 ) + 1,
		};
		return new Promise( ( resolve ) => {
			setTimeout( () => {
				console.info( 'saved' );
				resolve( result );
			}, 2000 );
		} );
	},
} );

services.set( 'languageInfoRepository', new MwLanguageInfoRepository(
	{
		bcp47() {
			return 'de';
		},
	},
	{
		getDir() {
			return 'ltr';
		},
	},
) );

services.set( 'entityLabelRepository', {
	getLabel( _x ) {
		return Promise.resolve( { language: 'de', value: 'Kartoffel' } );
	},
} );

services.set( 'messagesRepository', {
	get( messageKey: MessageKeys ): string {
		return messages[ messageKey ] || `⧼${messageKey}⧽`;
	},
	getText( messageKey: MessageKeys ): string {
		return messages[ messageKey ] || `⧼${messageKey}⧽`;
	},
} );

services.set( 'wikibaseRepoConfigRepository', {
	async getRepoConfiguration() {
		return {
			dataTypeLimits: {
				string: {
					maxLength: 200,
				},
			},
			dataRightsUrl: 'https://creativecommons.org/publicdomain/zero/1.0/',
			dataRightsText: 'Creative Commons CC0',
			termsOfUseUrl: 'https://foundation.wikimedia.org/wiki/Terms_of_Use',
		};
	},
} );

services.set( 'propertyDatatypeRepository', {
	getDataType: async ( _id ) => 'string',
} );

services.set( 'tracker', {
	trackPropertyDatatype( datatype: string ) {
		console.info( `Tracking datatype: '${datatype}'` );
	},
	trackError( type: string ): void {
		console.info( `Tracking error: '${type}'` );
	},
	trackRecoveredError( type: string ): void {
		console.info( `Tracking recovered error: '${type}'` );
	},
	trackUnknownError( type: string ): void {
		console.info( `Tracking unknown error: '${type}'` );
	},
	trackSavingUnknownError( type: string ): void {
		console.info( `Tracking unknown error on save: '${type}'` );
	},
} );

services.set( 'editAuthorizationChecker', {
	canUseBridgeForItemAndPage: () => Promise.resolve( [] ),
} );

services.set( 'repoRouter', {
	getPageUrl: ( title, params? ) => {
		let url = `http://repo/${title}`;
		if ( params ) {
			url += '?' + new URLSearchParams( params as Record<string, string> ).toString();
		}
		return url;
	},
} );

services.set( 'clientRouter', {
	getPageUrl( title: string, params?: Record<string, unknown> ) {
		let url = `https://client.wiki.example/wiki/${title}`;
		if ( params ) {
			url += '?' + new URLSearchParams( params as Record<string, string> ).toString();
		}
		return url;
	},
} );

services.set( 'referencesRenderingRepository', {
	getRenderedReferences( references: Reference[] ): Promise<string[]> {
		return Promise.resolve( references.map( ( reference ) => {
			return `<span>${JSON.stringify( reference.snaks )}</span>`;
		} ) );
	},
} );

services.set( 'purgeTitles', {
	purge( titles: string[] ): Promise<void> {
		console.info( 'purging', titles );
		return new Promise( ( resolve ) => {
			setTimeout( () => {
				console.info( 'purged' );
				resolve();
			}, 1337 );
		} );
	},
} );

launch(
	createApp as unknown as ( rootComponent: Component, rootProps?: Record<string, unknown> | null ) => App,
	{
		containerSelector: '#data-bridge-container',
	},
	{
		pageTitle: 'Client_page',
		entityId: 'Q42',
		propertyId: getOrEnforceUrlParameter( 'propertyId', 'P373' ) as string,
		entityTitle: 'Q42',
		editFlow: EditFlow.SINGLE_BEST_VALUE,
		client: {
			usePublish: getOrEnforceUrlParameter( 'usePublish', 'false' ) === 'true',
			issueReportingLink: 'https://http.cat/404',
		},
		originalHref: 'https://example.com/index.php?title=Item:Q47&uselang=en#P20',
		pageUrl: 'https://client.example/wiki/Client_page',
		userName: null,
	},
	services,
).on( initEvents.saved, () => {
	console.info( 'Application event: saved' );
} ).on( initEvents.cancel, () => {
	console.info( 'Application event: canceled' );
} ).on( initEvents.reload, () => {
	console.info( 'Application event: reload' );
} );

// The EventEmittingButton uses the `dir` attribute set at the root level of the page
// to determine the style of some buttons (e.g. back button)
const direction = getOrEnforceUrlParameter( 'dir', 'ltr' ) as string;
( document.querySelector( 'html' ) as HTMLElement ).setAttribute( 'dir', direction );
if ( direction === 'rtl' ) {
	for ( const style of document.querySelectorAll( 'style' ) ) {
		style.innerHTML = cssjanus.transform( style.innerHTML );
	}
}
