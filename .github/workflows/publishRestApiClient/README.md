# @wmde/wikibase-rest-api

This is an auto-generated Wikibase REST API client library using the API’s [OpenAPI document](https://doc.wikimedia.org/Wikibase/master/js/rest-api/openapi.json). It is generated via [`OpenAPITools/openapi-generator`](https://github.com/OpenAPITools/openapi-generator) and WMDE does not provide any guarantees about the library functionality.

## Usage

This snippet shows a basic usage example, including how to set the `User-Agent` header, which should be configured according to the [Wikimedia User-Agent policy](https://foundation.wikimedia.org/wiki/Policy:User-Agent_policy).

```js
import { ApiClient, LabelsApi } from '@wmde/wikibase-rest-api';

const apiClient = new ApiClient( 'https://www.wikidata.org/w/rest.php/wikibase' );
apiClient.defaultHeaders[ 'User-Agent' ] = '[custom user agent]';

console.log( await new LabelsApi( apiClient ).getItemLabel( 'Q1', 'en' ) );
```

Below you can find the automatically generated documentation:
