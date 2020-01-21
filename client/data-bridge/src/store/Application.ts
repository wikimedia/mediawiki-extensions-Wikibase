import {
	NS_ENTITY,
} from './namespaces';
import { InitializedEntityState } from '@/store/entity/EntityState';
import { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import Term from '@/datamodel/Term';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import Statement from '@/datamodel/Statement';
import ApplicationError from '@/definitions/ApplicationError';
import EditDecision from '@/definitions/EditDecision';

interface Application {
	editFlow: string;
	targetProperty: string;
	originalStatement: Statement|null;
	targetLabel: Term|null;
	applicationStatus: ValidApplicationStatus;
	applicationErrors: ApplicationError[];
	wikibaseRepoConfiguration: WikibaseRepoConfiguration|null;
	editDecision: EditDecision|null;
	entityTitle: string;
	originalHref: string;
}

export default Application;

export interface InitializedApplicationState extends Application {
	[ NS_ENTITY ]: InitializedEntityState;
	wikibaseRepoConfiguration: WikibaseRepoConfiguration;
}
