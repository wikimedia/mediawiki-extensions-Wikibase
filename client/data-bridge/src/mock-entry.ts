import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import Entities from '@/mock-data/data/Q42.data.json';
import EditFlow from '@/definitions/EditFlow';
import getOrEnforceUrlParameter from '@/mock-data/getOrEnforceUrlParameter';
import ServiceRepositories from '@/services/ServiceRepositories';
import { launch } from '@/main';
import EntityRevision from '@/datamodel/EntityRevision';
import Events from '@/events';
import MessageKeys from '@/definitions/MessageKeys';

const services = new ServiceRepositories();

services.setReadingEntityRepository(
	new SpecialPageReadingEntityRepository(
		{
			get() {
				return Entities;
			},
		} as any, // eslint-disable-line @typescript-eslint/no-explicit-any
		'',
	),
);

services.setWritingEntityRepository(
	{
		saveEntity( entity: EntityRevision ): Promise<EntityRevision> {
			console.log( 'save', entity ); // eslint-disable-line no-console
			return Promise.reject();
		},
	},
);

services.setLanguageInfoRepository(
	new MwLanguageInfoRepository(
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
	),
);

services.setEntityLabelRepository( {
	getLabel( _x ) {
		return Promise.resolve( { language: 'de', value: 'Kartoffel' } );
	},
} );

services.setMessagesRepository( {
	get( messageKey: string ): string {
		switch ( messageKey ) {
			case MessageKeys.BRIDGE_DIALOG_TITLE:
				return 'bridge dev';
			case MessageKeys.SAVE_CHANGES:
				return 'save changes';
			default:
				return `⧼${messageKey}⧽`;
		}
	},
} );

launch(
	{
		containerSelector: '#data-bridge-container',
		usePublish: getOrEnforceUrlParameter( 'usePublish', 'false' ) === 'true',
	},
	{
		entityId: 'Q42',
		propertyId: getOrEnforceUrlParameter( 'propertyId', 'P349' ) as string,
		editFlow: EditFlow.OVERWRITE,
	},
	services,
).on( Events.onSaved, () => {
	console.info( 'saved' ); // eslint-disable-line no-console
} ).on( Events.onCancel, () => {
	console.info( 'canceled' ); // eslint-disable-line no-console
} );
