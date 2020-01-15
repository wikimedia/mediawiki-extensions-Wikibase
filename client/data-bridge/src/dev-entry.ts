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
	saveEntity( entity: EntityRevision ): Promise<EntityRevision> {
		console.log( 'save', entity ); // eslint-disable-line no-console
		return Promise.reject();
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

const messages = {
	[ MessageKeys.BRIDGE_DIALOG_TITLE ]: 'bridge dev',
	[ MessageKeys.SAVE_CHANGES ]: 'save changes',
	[ MessageKeys.CANCEL ]: 'cancel',
	[ MessageKeys.REFERENCES_HEADING ]: 'References',
	[ MessageKeys.REFERENCE_SNAK_SEPARATOR ]: '.&#32;',
} as { [ key in MessageKeys ]: string };

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
		console.info( `Tracking datatype: '${datatype}'` ); // eslint-disable-line no-console
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

launch(
	{
		containerSelector: '#data-bridge-container',
	},
	{
		pageTitle: 'Client_page',
		entityId: 'Q42',
		propertyId: getOrEnforceUrlParameter( 'propertyId', 'P349' ) as string,
		entityTitle: 'Q42',
		editFlow: EditFlow.OVERWRITE,
		client: {
			usePublish: getOrEnforceUrlParameter( 'usePublish', 'false' ) === 'true',
		},
	},
	services,
).on( Events.onSaved, () => {
	console.info( 'saved' ); // eslint-disable-line no-console
} ).on( Events.onCancel, () => {
	console.info( 'canceled' ); // eslint-disable-line no-console
} );
