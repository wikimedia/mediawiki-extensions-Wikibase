import lockState from './lockState';
import Application from '@/store/Application';

export default function ( fields?: any ): Application {
	let AppState: Application = {
		targetProperty: '',
		editFlow: '',
	};

	if ( fields !== null ) {
		AppState = { ...AppState, ...fields };
		lockState( AppState );
	}

	return AppState;
}
