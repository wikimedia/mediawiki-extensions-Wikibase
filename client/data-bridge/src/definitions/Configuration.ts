import WikibaseClientConfiguration from '@/definitions/WikibaseClientConfiguration';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';

type Configuration = WikibaseClientConfiguration & WikibaseRepoConfiguration;
export default Configuration;
