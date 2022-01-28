import { Store } from 'vuex';
import Application from './Application';
import { TrackFunction } from '@/@types/TrackingOptions';
import actions from '@/store/actions';
import { mutations } from '@/store/mutations';
import { getters } from '@/store/getters';

export function createStore( metricTracker: TrackFunction ): Store<Application> {
	return new Store( {
		state(): Application {
			return {
				statementsTaintedState: { },
				statementsPopperIsOpen: { },
				statementsEditState: { },
				helpLink: '',
			};
		},
		actions: actions( metricTracker ),
		mutations,
		getters,
	} );
}
