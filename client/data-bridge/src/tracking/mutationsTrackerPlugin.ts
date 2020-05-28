import BridgeTracker from '@/definitions/data-access/BridgeTracker';
import { Store } from 'vuex';
import Application from '@/store/Application';
import { RootMutations } from '@/store/mutations';

function isAddApplicationErrorsMutation(
	type: string,
	_payload: unknown,
): _payload is Parameters<typeof RootMutations.prototype.addApplicationErrors>[0] {
	return type === RootMutations.prototype.addApplicationErrors.name;
}

export default function mutationsTrackerPlugin( tracker: BridgeTracker ): ( store: Store<Application> ) => void {
	return ( store: Store<Application> ): void => {
		store.subscribe( ( { type, payload }: { type: string; payload: unknown } ): void => {
			if ( !isAddApplicationErrorsMutation( type, payload ) ) {
				return;
			}

			payload.forEach( ( error ) => tracker.trackError( error.type ) );
		} );
	};
}
