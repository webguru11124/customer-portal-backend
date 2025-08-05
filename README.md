# Customer Portal Backend

You can find additional documentation at https://aptive.atlassian.net/wiki/spaces/EN/pages/1519059007/Customer+Portal

## Project Setup

1) You need to get valid permissions for the projects. By default, you should have Guest permissions for all Aptive private repositories and Developer permission for Customer Portal Backend or Customer Portal Frontend. But Guest access does not allow to download composer packages, and you will get composer errors.

You need at least Reporter permissions for:
* customer-portal-backend
* customer-portal-frontend
* helpers
* laravel-json-api
* http-status
* jsonapi
* pestroutes-sdk

Please check https://aptive.atlassian.net/wiki/spaces/EN/pages/1610874881/How+to+set+up+Customer+Portal+Back-end for further information

2) Ensure that you have docker desktop and docker-compose tools installed:  
See [Docker Desktop](https://docs.docker.com/desktop/) and [Docker Compose](https://docs.docker.com/compose/)
3) Create personal access token in [GitLab](https://gitlab.com/-/profile/personal_access_tokens)
4) Copy `scr/auth.json.example` to `src/auth.json` and put you GitLab username and access token into the file
```json
{
  "gitlab-token": {
    "gitlab.com": {
      "username": "YourUsername",
      "token": "glpat-YourGitlabToken"
    }
  }
}
```
5) Copy `app.env` to `.env` and `src/.env`
6) Adjust environment variables as needed — e.g. set Gitlab username and token, Pestroutes API keys, AWS keys, etc.
7) Setup Auth0. See Auth0 Setup
8) Build and run docker containers.
```bash
cp src/auth.json.example src/auth.json
cp .env.example .env
# vim src/auth.json 
# vim .env
docker-compose build
docker-compose up
```
9) Open http://localhost:8080/api/v1/documentation in browser and check it works.

## AWS Access Setup

AWS access setup is required to use demo PestRoutes API.

* Request AWS Developer account from DevOps
* Create IAM access key and put key id and secret into `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` variables in `.env` file

## Auth0 Setup

* Sign up at [https://auth0.com](http://localhost:8000)
* Go to <u>Applications -> APIs</u> and create new API with a name of your choice and identifier matching the hostname you are using to access local API, e.g. `http://localhost:8080/api/v1/`
* Go to <u>Applications -> Applications</u>, a new application with name matching your API name should have been created for you already.
* Open application settings, write down the <u>Domain</u> and <u>Client ID</u> 
* In the application <u>Credentials</u> section change the <u>Authentication Methods</u> to `None` and confirm
* In the <u>Application URIs</u> section put in your local frontend application URL, `http://localhost:8000/dashboard` for <u>Allowed callback URLs</u>, `http://localhost:8000` for <u>Allowed Web Origins</u> and <u>Allowed Origins (CORS)</u>
* In <u>Advanced Settings -> Grant Types</u> select `Authorization Code`, `Refresh Token` 
* Don't forget to click save at the bottom
* Go to <u>Auth Pipeline -> Rules</u> and create a new rule, put the following code in it

```javascript
function addEmailToAccessToken(user, context, callback) {
  // This rule adds the authenticated user's email address to the access token.
  context.accessToken['email'] = user.email;
  return callback(null, user, context);
}
```

* update `AUTH0_*` variables in your `.env` file with the values you saved:
    * Put Domain from application settings into `AUTH0_DOMAIN`, don't add `http://` schema
    * Put Client ID and Client Secret into `AUTH0_CLIENT_ID` and `AUTH0_CLIENT_SECRET` respectively
    * Leave `AUTH0_STRATEGY` set to `api`
    * Put API identifier you set at step 2 into `AUTH0_AUDIENCE`
* Now you need to restart PHP container to load
* Update `app/.env` file of the CXP frontend application with the same values
* If the `CORS_ALLOWED_ORIGINS` key is not set in the .env file, add it and set the value to https://*.auth0.com for CORS.
* If the `CORS_ALLOWED_ORIGINS` key is already set in the .env file, add https://*.auth0.com after a comma if there is already another string present.

Please check https://aptive.atlassian.net/wiki/spaces/EN/pages/1604419801/Auth0+Setup+For+Local+Development

## Auth0/PestRoutes customer for Sign In/Sign Up

Please check https://aptive.atlassian.net/wiki/spaces/EN/pages/1819869241/How+to+sign+in+sign+up+into+Customer+Portal

## API Documentation
We store API documentation both locally and at Swaggerhub.
Open http://localhost:8080/api/v1/documentation or  http://localhost:8080/api/v2/documentation to view API documentation.
To update it:
1) Update API Documentation at https://app.swaggerhub.com/apis/Aptive-Environmental/Customer_Portal_API/
2) Export it as ***unresolved yaml*** from Swagger and replace backend/storage/api-docs/api-docs.yaml or backend/storage/api-docs/api-docs-v2.yaml with new Swagger file.

## Creating Unit/Integration Tests

Please make sure to extend your test classes from `PHPUnit\Framework\TestCase` unless you are creating integration tests. 
Extending from `Tests\TestCase` (effectively `Illuminate\Foundation\Testing\TestCase`) should only be necessary when you test API responses.  

Using `Illuminate\Foundation\Testing\TestCase` creates and destroys whole Laravel application for every test, therefore it might use 2-3 times more memory and would be 50-100 times slower than simple test that only uses PHPUnit.

# ENV Vars

| Variable                                | Description                                                                                                                                         | Possible values and examples                                                            | Application Defaults                          |
|-----------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------|-----------------------------------------------|
| APP_NAME                                | Application name.                                                                                                                                   | Any string                                                                              |                                               |
| APP_ENV                                 | Application environment                                                                                                                             | local, testing, production                                                              |                                               |
| APP_KEY                                 | This key is used by the Illuminate encrypter service and should be set to a random, 32 character string                                             | base64:A/qfweFj5lnobVjOZnRmxBKrOYR1K8AnOMkeqERGTe8=                                     |                                               |
| APP_DEBUG                               | Application Debug Mode. Enables detailed error message. Must be false at production.                                                                | true, false                                                                             |                                               |
| APP_URL                                 | Application URL. This URL is used by the application to properly generate URLs                                                                      | http://localhost                                                                        |                                               |
| AVAILABLE_SPOTS_MAX_DISTANCE            | Max distance for searching available spots for customer (km)                                                                                        | integer                                                                                 | 3                                             |
| NODE_PORT                               | Port for NodJJS frontend.                                                                                                                           | 8000                                                                                    |                                               |
| LOG_CHANNEL                             | This option defines the default log channel that gets used when writing messages to the logs.                                                       | single, daily, slack, syslog, errorlog, monolog, custom, stack                          |                                               |
| LOG_LEVEL                               | Log level                                                                                                                                           | debug, info, notice, warning, error, critical, alert, emergency                         |                                               |
| DATABASE_URL                            | DATABASE url                                                                                                                                        | localhost                                                                               |                                               |
| DB_HOST                                 | DB host                                                                                                                                             | localhost                                                                               |                                               |
| DB_DATABASE                             | Database name                                                                                                                                       | string                                                                                  |                                               |
| DB_USERNAME                             | Database username                                                                                                                                   | string                                                                                  |                                               |
| DB_PASSWORD                             | Database password                                                                                                                                   | string                                                                                  |                                               |
| BROADCAST_DRIVER                        | This option controls the default broadcaster that will be used by the framework when an event needs to be broadcast. Should be log at dev and stage | pusher, ably, redis, log, null                                                          |                                               |
| GLOBAL_RESERVICE_TYPE_ID                | ID of service type 'Reservice' common for all offices                                                                                               | integer                                                                                 | 3                                             |
| CACHE_DRIVER                            | This option controls the default cache connection that gets used while using this caching library. We use "file"                                    | apc, array, database, file, memcached, redis, dynamodb, octane, null                    |                                               |
| MAIL_MAILER                             | Default Mailer. We use "smtp"                                                                                                                       | smtp, sendmail, mailgun, ses, postmark, log, array, failover                            |                                               |
| MAIL_HOST                               | SMTP host                                                                                                                                           | mailhog at dev                                                                          |                                               |
| MAIL_PORT                               | SMTP port                                                                                                                                           | integer                                                                                 |                                               |
| MAIL_USERNAME                           | username                                                                                                                                            | string                                                                                  |                                               |
| MAIL_PASSWORD                           | password                                                                                                                                            | string                                                                                  |                                               |
| MAIL_ENCRYPTION                         | encryption protocol, TLS by default                                                                                                                 | string                                                                                  | tls                                           |
| MAIL_FROM_ADDRESS                       | from address                                                                                                                                        | email                                                                                   |                                               |
| MAIL_FROM_NAME                          | from name                                                                                                                                           | "${APP_NAME}"                                                                           |                                               |
| AWS_ACCESS_KEY_ID                       | AWS access key id.                                                                                                                                  | Empty now.                                                                              |                                               |
| AWS_SECRET_ACCESS_KEY                   | AWS access key.                                                                                                                                     | Empty now.                                                                              |                                               |
| AWS_DEFAULT_REGION                      | AWS region                                                                                                                                          | us-east-1                                                                               |                                               |
| AWS_BUCKET                              | AWS bucket. Empty now.                                                                                                                              |                                                                                         |                                               |
| AWS_USE_PATH_STYLE_ENDPOINT             | should be false                                                                                                                                     | false                                                                                   |                                               |
| SENDGRID_TOKEN                          | Sendgrid token                                                                                                                                      | string                                                                                  |                                               |
| PESTROUTES_API_URL                      | The URL for the pestroutes API (Excluding the specific endpoint)                                                                                    | "https://demoawsaptivepest.pestroutes.com/api" at dev                                   |                                               |
| PESTROUTES_API_TIMEOUT                  | HTTP request timeout in seconds                                                                                                                     | int                                                                                     | 10                                            |
| PESTROUTES_MAIN_OFFICE_ID               | pestroutes main office id                                                                                                                           | 1 at dev                                                                                |                                               |
| PESTROUTES_CREDENTIALS_TABLE_NAME       | AWS DynamoDB table name containing pestroutes credentials                                                                                           | string                                                                                  |                                               |
| PESTROUTES_CREDENTIALS_AWS_REGION       | AWS DynamoDB credentials table region                                                                                                               | string                                                                                  |                                               |
| RESERVICE_INTERVAL_LONG                 | If a customer has been serviced (completed appointment) within the last X days the appointment created will be as a "Reservice"                     | integer                                                                                 | 61                                            |
| RESERVICE_INTERVAL_SHORT                | If a customer has been serviced (completed appointment) within the last X days the appointment created will be as a "Reservice"                     | integer                                                                                 | 26                                            |
| WORLDPAY_API_SERVICE_URL                | Worldpay API service URL                                                                                                                            | 'https://certservices.elementexpress.com'                                               |                                               |
| WORLDPAY_API_TRANSACTION_URL            | Worldpay API transaction URL                                                                                                                        | 'https://certtransaction.elementexpress.com'                                            |                                               |
| WORLDPAY_API_TIMEOUT                    | HTTP request timeout in seconds                                                                                                                     | int                                                                                     | 10                                            |
| WORLDPAY_APPLICATION_ID                 | Worldpay application id                                                                                                                             | integer                                                                                 |                                               |
| WORLDPAY_CREDENTIALS_TABLE_NAME         | Worldpay credentials DynamoDB table name                                                                                                            | string                                                                                  |                                               |
| WORLDPAY_CREDENTIALS_AWS_REGION         | Worldpay credentials DynamoDB region                                                                                                                | string                                                                                  |                                               |
| WORLDPAY_TRANSACTION_SETUP_URL          | Worldpay transaction setup URL                                                                                                                      | 'https://certtransaction.hostedpayments.com/?TransactionSetupID={{TransactionSetupID}}' |                                               |
| WORLDPAY_TRANSACTION_SETUP_URL          | Worldpay transaction setup URL                                                                                                                      | 'https://certtransaction.hostedpayments.com/?TransactionSetupID={{TransactionSetupID}}' |                                               |
| WORLDPAY_TRANSACTION_SETUP_CALLBACK_URL | Worldpay transaction setup callback URL                                                                                                             | 'https://app.customer-portal.stg.goaptive.com/worldpay/transaction-setup-callback'      |                                               |
| TREATMENT_DURATION_STANDARD             | Standard treatment duration in minutes                                                                                                              | int                                                                                     |                                               |
| TREATMENT_DURATION_RESERVICE            | Reservice treatment duration in minutes                                                                                                             | int                                                                                     |                                               |
| TWILIO_ACCOUNT_SID                      | Twilio account id                                                                                                                                   | string                                                                                  |                                               |
| TWILIO_API_KEY                          | Twilio API key                                                                                                                                      | string                                                                                  |                                               |
| TWILIO_API_SECRET                       | Twilio API secret                                                                                                                                   | string                                                                                  |                                               |
| TWILIO_API_NUMBER                       | Twilio API number                                                                                                                                   | string like "+xxxxx"                                                                    |                                               |
| AUTH0_DOMAIN                            | Auth0 Application Domain                                                                                                                            | string like "dev-oh94tnwz.us.auth0.com"                                                 |                                               |
| AUTH0_CLIENT_ID                         | Auth0 Application Client ID                                                                                                                         | string like "uQn76minlpTThoM4qzxdLSP5wux8ZXHp"                                          |                                               |
| AUTH0_CLIENT_SECRET                     | Auth0 Application Secret                                                                                                                            | string                                                                                  |                                               |
| AUTH0_STRATEGY                          | Auth0 Authorization Strategy, we use "api" for the application                                                                                      | string "api"                                                                            |                                               |
| AUTH0_AUDIENCE                          | Auth0 Audience, Application URL                                                                                                                     | string, e.g. "http://localhost:8080/api/v1" for dev                                     |                                               |
| AUTH0_API_DOMAIN                        | Auth0 Management API Application Domain                                                                                                             | string like "dev-xxx.us.auth0.com"                                                      |                                               |
| AUTH0_API_CLIENT_ID                     | Auth0 Management API Application Client ID                                                                                                          | string like "uQn76minlpTThoM4qzxdLSP5wux8ZXHp"                                          |                                               |
| AUTH0_API_CLIENT_SECRET                 | Auth0 Management API Application Secret                                                                                                             | string                                                                                  |                                               |
| AUTH0_API_AUDIENCE                      | Auth0 Management API Audience, Auth0 Management API URL                                                                                             | string, e.g. "https://dev-xxx.us.auth0.com/api/v2/" for dev                             |                                               |
| AUTH0_API_TIMEOUT                       | HTTP request timeout in seconds                                                                                                                     | int                                                                                     | 10                                            |
| CORS_ALLOWED_ORIGINS                    | URLs to allow requests from                                                                                                                         | string, e.g. `http://localhost:8000,*.auth0.com`                                        |                                               |
| GITLAB_USER                             | Your gitlab username                                                                                                                                | string, e.g. `john.smith`                                                               |                                               |
| GITLAB_TOKEN                            | Your gitlab access token with `read_api` scope enabled                                                                                              | string, e.g. `exexe-XXXXfXx4xXxxy7xxxxXxx`                                              |                                               |
| REDIS_HOST                              | The host used to connect to redis enabled                                                                                                           | string                                                                                  |                                               |
| REDIS_PORT                              | the port that used to connect to redis enabled                                                                                                      | int                                                                                     | 6379                                          |
| REDIS_PASSWORD                          | the password required to connect to redis                                                                                                           | string                                                                                  |                                               |
| API_KEYS_ALLOWED                        | base64 encoded json containing the keys that will be authenticated against                                                                          | string                                                                                  |                                               |
| QUEUE_CONNECTION                        | the connection used for the queue                                                                                                                   | string                                                                                  | redis                                         |
| REDIS_QUEUE                             | the name of the queue to be used                                                                                                                    | string                                                                                  | {default}                                     |
| METRICS_BACKEND                         | the name of the backend to be used for sending metric events                                                                                        | `http` or `noop`                                                                        | noop                                          |
| METRICS_HTTP_URL                        | URL to send data to using HTTP backend                                                                                                              | https://api.example.com/endpoint                                                        |                                               |
| METRICS_HTTP_TOKEN                      | token used for token based authorization                                                                                                            | string                                                                                  |                                               |
| METRICS_HTTP_TIMEOUT                    | HTTP request timeout in seconds                                                                                                                     | int                                                                                     | 5                                             |
| APTIVE_PAYMENT_SERVICE_URL              | Aptive Payment Service Domain                                                                                                                       | string, e.g. `https://api.payment-service.tst.goaptive.com`                             | https://api.payment-service.tst.goaptive.com  |
| APTIVE_PAYMENT_SERVICE_API_KEY          | Aptive Payment Service API Key (Header: Api-Key)                                                                                                    | string, e.g. `123456`                                                                   |                                               |
| APTIVE_PAYMENT_SERVICE_TOKEN_SCHEME     | Aptive Payment Service Token Scheme                                                                                                                 | string, e.g. `PCI`                                                                      | PCI                                           |
| PB_API_URL                              | Plan Builder URL                                                                                                                                    | string e.g. https://api.plan-builder.stg.goaptive.com/api                               | https://api.plan-builder.stg.goaptive.com/api |
| PB_API_KEY                              | Plan Builder API Key                                                                                                                                | string e.g. '1                                                                          | api_kei'                                      |
| CLEO_CRM_API_URL                        | Cleo CRM API Url                                                                                                                                    | string e.g. https://crm.stg.goaptive.com                                                | https://crm.stg.goaptive.com                  |
| CLEO_CRM_API_AUTH_USER_ID               | Cleo CRM API auth user id (uuid)                                                                                                                    | string e.g. 9b2ad708-fd67-48b0-871d-d0561e3d1bab                                        |                                               |
| FUSIONAUTH_CLIENT_ID                    | FusionAuth Client Id                                                                                                                                | string e.g. b84ccaf9-c1c7-4ba8-82c9-e263bf9b152a                                        | b84ccaf9-c1c7-4ba8-82c9-e263bf9b152a          |
| FUSIONAUTH_URL                          | FusionAuth URL                                                                                                                                      | string e.g. aptive_fusionauth_io                                                        | aptive_fusionauth_io                          |
| JWT_ALGO                                | JWT algorithm                                                                                                                                       | HS256                                                                                   | HS256                                         |
| JWT_SECRET                              | Secret                                                                                                                                              | string e.g.FakeStringK2eA09+V4wvTxgh6tXVnN7FKDEPJ8MB+8A=                                |                                               |
| MAGICLINK_SECRET                        | Secret                                                                                                                                              | string e.g. S0meLongRand0mStr1ngTxgh6td0561ee263bf9b152a3d1bab                          |                                               |
| MAGICLINK_TTL                           | Time to live                                                                                                                                        | 24h                                                                                     | 24h                                           |
| MAGIC_URL                               | URL                                                                                                                                                 | string e.g. acme.com                                                                    |         acme.com                              |
| MAGIC_JWT_ALGO                          | algorythm                                                                                                                                           | HS256                                                                                   | HS256                                         |
| MAGIC_JWT_SECRET                        | Secret                                                                                                                                              | string e.g. S0meLongRand0mStr1ngTxgh6td0561ee263bf9b152a3d1bab                          |                                               |
