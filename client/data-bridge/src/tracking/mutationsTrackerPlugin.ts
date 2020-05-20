import ApplicationError from '@/definitions/ApplicationError';
import BridgeTracker from '@/definitions/data-access/BridgeTracker';
import { Store } from 'vuex';
import Application from '@/store/Application';

function isAddApplicationErrorsMutation( type: string, _payload: unknown ): _payload is ApplicationError[] {
	return type === 'addApplicationErrors';
}

export default function mutationsTrackerPlugin( tracker: BridgeTracker ): ( store: Store<Application> ) => void {
	return ( store ): void => {
		store.subscribe( ( { type, payload }: { type: string; payload: unknown } ): void => {
			if ( !isAddApplicationErrorsMutation( type, payload ) ) {
				return;
			}

			payload.forEach( ( error ) => tracker.trackError( error.type ) );
		} );
	};
}
