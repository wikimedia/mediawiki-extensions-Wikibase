import Application from '@/store/Application';
import ApplicationStatus from '@/definitions/ApplicationStatus';

export default function ( fields?: any ): Application {
	let AppState: Application = {
		targetProperty: '',
		targetLabel: null,
		editFlow: '',
		applicationStatus: ApplicationStatus.INITIALIZING,
	};

	if ( fields !== null ) {
		AppState = { ...AppState, ...fields };
	}

	return AppState;
}
