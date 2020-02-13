import {
	NS_ENTITY, NS_STATEMENTS,
} from './namespaces';
import { EntityState } from '@/store/entity';
import { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import Term from '@/datamodel/Term';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import DataValue from '@/datamodel/DataValue';
import Statement from '@/datamodel/Statement';
import ApplicationError from '@/definitions/ApplicationError';
import EditDecision from '@/definitions/EditDecision';
import { StatementState } from '@/store/statements';

interface Application {
	applicationErrors: ApplicationError[];
	applicationStatus: ValidApplicationStatus;
	editDecision: EditDecision|null;
	targetValue: DataValue|null;
	editFlow: string;
	entityTitle: string;
	originalHref: string;
	originalStatement: Statement|null;
	pageTitle: string;
	targetLabel: Term|null;
	targetProperty: string;
	wikibaseRepoConfiguration: WikibaseRepoConfiguration|null;
}

export default Application;

export interface InitializedApplicationState extends Application {
	[ NS_ENTITY ]: EntityState;
	[ NS_STATEMENTS ]: StatementState;
	wikibaseRepoConfiguration: WikibaseRepoConfiguration;
}
