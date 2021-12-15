import Application, { InitializedApplicationState } from '@/store/Application';
import ApplicationStatus from '@/definitions/ApplicationStatus';

type RecursivePartial<T> = {
	[P in keyof T]?: RecursivePartial<T[P]>;
};

export default function ( fields?: RecursivePartial<InitializedApplicationState> ): Application {
	let AppState: any = {
		targetProperty: '',
		targetLabel: null,
		renderedTargetReferences: [],
		editFlow: '',
		applicationStatus: ApplicationStatus.INITIALIZING,
		applicationErrors: [],
		editDecision: null,
		targetValue: null,
		entityTitle: '',
		originalHref: '',
		pageTitle: '',
		assertUserWhenSaving: true,
		config: {},
	};

	if ( fields !== null ) {
		AppState = { ...AppState, ...fields };
	}

	return AppState;
}
