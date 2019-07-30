import lockState from './lockState';
import Application from '@/store/Application';
import ApplicationStatus from '@/definitions/ApplicationStatus';

export default function ( fields?: any ): Application {
	let AppState: Application = {
		targetProperty: '',
		editFlow: '',
		applicationStatus: ApplicationStatus.INITIALIZING,
	};

	if ( fields !== null ) {
		AppState = { ...AppState, ...fields };
		lockState( AppState );
	}

	return AppState;
}
