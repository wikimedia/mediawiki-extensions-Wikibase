import {
	NS_ENTITY,
} from './namespaces';
import { InitializedEntityState } from '@/store/entity/EntityState';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import Term from '@/datamodel/Term';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import Statement from '@/datamodel/Statement';

interface Application {
	editFlow: string;
	targetProperty: string;
	originalStatement: Statement|null;
	targetLabel: Term|null;
	applicationStatus: ApplicationStatus;
	wikibaseRepoConfiguration: WikibaseRepoConfiguration|null;
}

export default Application;

export interface InitializedApplicationState extends Application {
	[ NS_ENTITY ]: InitializedEntityState;
	wikibaseRepoConfiguration: WikibaseRepoConfiguration;
}
