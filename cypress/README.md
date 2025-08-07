# Cypress tests

## Setup
To run Cypress browser tests against a local mediawiki install, set these environment variables. Depending on your local
setup, these might be different.
```bash
export MW_SERVER=http://default.mediawiki.mwdd.localhost:8080/
export MW_SCRIPT_PATH=w/
export MEDIAWIKI_USER=an_admin_username
export MEDIAWIKI_PASSWORD=the_password_for_that_user
```

Before running cypress for the first time, the binaries need to be installed in the cache folder:

```bash
npm run cypress:install
```

## Run the tests
Use this command to run the tests in a terminal:
```bash
npm run cypress:run
```

Or you can open Cypress's GUI with this command:
```bash
npm run cypress:open
```
