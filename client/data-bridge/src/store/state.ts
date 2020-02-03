import Statement from '@/datamodel/Statement';
import Term from '@/datamodel/Term';
import ApplicationError from '@/definitions/ApplicationError';
import { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import EditDecision from '@/definitions/EditDecision';
import Application from '@/store/Application';

export class BaseState implements Application {
	public applicationErrors: ApplicationError[] = [];
	public applicationStatus: ValidApplicationStatus = ValidApplicationStatus.INITIALIZING;
	public editDecision: EditDecision|null = null;
	public editFlow = '';
	public entityTitle = '';
	public originalHref = '';
	public originalStatement: Statement|null = null;
	public pageTitle = '';
	public targetLabel: Term|null = null;
	public targetProperty = '';
	public wikibaseRepoConfiguration: WikibaseRepoConfiguration|null = null;
}
