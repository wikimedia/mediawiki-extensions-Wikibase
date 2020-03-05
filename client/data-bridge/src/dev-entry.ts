/* eslint no-console: "off" */
import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import Entities from '@/mock-data/data/Q42.data.json';
import EditFlow from '@/definitions/EditFlow';
import getOrEnforceUrlParameter from '@/mock-data/getOrEnforceUrlParameter';
import ServiceContainer from '@/services/ServiceContainer';
import { launch } from '@/main';
import EntityRevision from '@/datamodel/EntityRevision';
import Events from '@/events';
import MessageKeys from '@/definitions/MessageKeys';
import clone from '@/store/clone';

const services = new ServiceContainer();

services.set( 'readingEntityRepository', new SpecialPageReadingEntityRepository(
	{
		get: () => new Promise( ( resolve ) => {
			setTimeout( () => {
				resolve( Entities );
			}, 1100 );
		} ),
	} as any, // eslint-disable-line @typescript-eslint/no-explicit-any
	'',
) );

services.set( 'writingEntityRepository', {
	saveEntity( entityRevision: EntityRevision ): Promise<EntityRevision> {
		console.info( 'saving', entityRevision );
		const result: EntityRevision = {
			entity: clone( entityRevision.entity ),
			revisionId: entityRevision.revisionId + 1,
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

/* eslint-disable max-len */
const messages = {
	[ MessageKeys.BRIDGE_DIALOG_TITLE ]: 'bridge dev',
	[ MessageKeys.SAVE_CHANGES ]: 'save changes',
	[ MessageKeys.CANCEL ]: 'cancel',
	[ MessageKeys.EDIT_DECISION_HEADING ]: 'Please select the type of edit that you made:',
	[ MessageKeys.EDIT_DECISION_REPLACE_LABEL ]: '<strong>I corrected</strong> a wrong value',
	[ MessageKeys.EDIT_DECISION_REPLACE_DESCRIPTION ]: 'The previous value was not correct and has never been.',
	[ MessageKeys.EDIT_DECISION_UPDATE_LABEL ]: '<strong>I updated</strong> an outdated value',
	[ MessageKeys.EDIT_DECISION_UPDATE_DESCRIPTION ]: 'The previous value used to be correct but is now outdated.',
	[ MessageKeys.REFERENCES_HEADING ]: 'References',
	[ MessageKeys.REFERENCE_SNAK_SEPARATOR ]: '. ',
	[ MessageKeys.BAILOUT_HEADING ]: 'Instead you could do the following:',
	[ MessageKeys.BAILOUT_SUGGESTION_GO_TO_REPO ]: 'Edit the value on repo. Click the button below to edit the value directly (link opens in a new tab).',
	[ MessageKeys.BAILOUT_SUGGESTION_GO_TO_REPO_BUTTON ]: 'Edit the value on the repo',
	[ MessageKeys.BAILOUT_SUGGESTION_EDIT_ARTICLE ]: 'Depending on the template used, it might be possible to overwrite the value locally using <a href="https://example.com">the article editor</a>. If at all possible, we recommend that you instead add the value to repo via the button above.',
	[ MessageKeys.UNSUPPORTED_DATATYPE_ERROR_HEAD ]: 'Editing the value for $1 is currently not supported',
	[ MessageKeys.UNSUPPORTED_DATATYPE_ERROR_BODY ]: '$1 is of the datatype $2 on repo. Editing this datatype is currently not supported.',
	[ MessageKeys.PERMISSIONS_HEADING ]: 'You do not have permission to edit this value, for the following reason:',
	[ MessageKeys.PERMISSIONS_CASCADE_PROTECTED_HEADING ]: '<strong>This value is currently cascade protected on repo and can be edited only by <a href="https://example.com">administrators</a>.</strong>',
	[ MessageKeys.PERMISSIONS_CASCADE_PROTECTED_BODY ]: '<p><strong>Why is this value protected?</strong></p>\n<p>This value is transcluded in the following pages, which are protected with the "cascading" option:</p>\n$2',
} as { [ key in MessageKeys ]: string };
/* eslint-enable max-len */

services.set( 'messagesRepository', {
	get( messageKey: MessageKeys ): string {
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

launch(
	{
		containerSelector: '#data-bridge-container',
	},
	{
		pageTitle: 'Client_page',
		entityId: 'Q42',
		propertyId: getOrEnforceUrlParameter( 'propertyId', 'P373' ) as string,
		entityTitle: 'Q42',
		editFlow: EditFlow.OVERWRITE,
		client: {
			usePublish: getOrEnforceUrlParameter( 'usePublish', 'false' ) === 'true',
		},
		originalHref: 'https://example.com/index.php?title=Item:Q47&uselang=en#P20',
	},
	services,
).on( Events.onSaved, () => {
	console.info( 'saved' );
} ).on( Events.onCancel, () => {
	console.info( 'canceled' );
} );
