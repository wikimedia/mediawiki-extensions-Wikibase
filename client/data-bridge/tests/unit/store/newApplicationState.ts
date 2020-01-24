import Application from '@/store/Application';
import ApplicationStatus from '@/definitions/ApplicationStatus';

export default function ( fields?: any ): Application {
	let AppState: Application = {
		targetProperty: '',
		targetLabel: null,
		originalStatement: null,
		editFlow: '',
		applicationStatus: ApplicationStatus.INITIALIZING,
		applicationErrors: [],
		wikibaseRepoConfiguration: null,
		editDecision: null,
		entityTitle: '',
		originalHref: '',
		pageTitle: '',
	};

	if ( fields !== null ) {
		AppState = { ...AppState, ...fields };
	}

	return AppState;
}
