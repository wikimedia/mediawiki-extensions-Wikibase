import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import Vue from 'vue';
import App from '@/presentation/App.vue';
import AppInformation from '@/definitions/AppInformation';
import AppConfiguration from '@/definitions/AppConfiguration';
import { createStore } from '@/store';
import ServiceContainer from '@/services/ServiceContainer';
import Events from '@/events';
import { EventEmitter } from 'events';
import repeater from '@/events/repeater';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import DataType from '@/datamodel/DataType';
import createServices from '@/services/createServices';

Vue.config.productionTip = false;

export function launch(
	config: AppConfiguration,
	information: AppInformation,
	services: ServiceContainer,
): EventEmitter {
	extendVueEnvironment(
		services.get( 'languageInfoRepository' ),
		services.get( 'messagesRepository' ),
		information.client,
	);

	const store = createStore( services );
	store.dispatch( BRIDGE_INIT, information );

	services.get( 'propertyDatatypeRepository' ).getDataType( information.propertyId )
		.then( ( dataType: DataType ) => {
			services.get( 'tracker' ).trackPropertyDatatype( dataType );
		} );

	const app = new App( {
		store,
	} ).$mount( config.containerSelector );

	const emitter = new EventEmitter();
	repeater( app, emitter, Object.values( Events ) );

	return emitter;
}

export { createServices };
