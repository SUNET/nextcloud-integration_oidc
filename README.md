# integration_oidc
This app provides a generic integration engine for OIDC providers.
It provides a way to connect any oidc account to Nextcloud.

It works by running the OIDC authorization code flow for a user, including getting consent,
against an OIDC provider and collects a refresh token that is used to
periodically refresh access tokens allowing other apps to use them for integration,
such as email sync, calendar sync or anything else.

***NOTE***: To be able to use this app, you need to be able to get tokens from a database
table and use it for something sensible, most likely from another app. This is not
the app you want if you are looking to login to Nextcloud using a OIDC Provider, or
use Nextcloud as a OIDC provider for something else.

## Configuration
For configuring an oidc provider for gmail, this is a sensible start:
```
name: Google
issuer: https://accounts.google.com
auth_endpoint: https://accounts.google.com/o/oauth2/v2/auth
client_id: <Client ID from https://console.cloud.google.com/apis/credentials>
client_secret: <Client Secret from https://console.cloud.google.com/apis/credentials>
scope: https://mail.google.com/ openid profile email
revoke_endpoint: https://oauth2.googleapis.com/revoke
token_endpoint: https://oauth2.googleapis.com/token
user_endpoint: https://accounts.google.com/o/oauth2/v2/user
```
And for M365, you need to add the following:
```
name: Microsoft
issuer: https://login.microsoftonline.com/beb73af0-54c3-4c95-886a-3e6de3a76471/v2.0
auth_endpoint: https://login.microsoftonline.com/beb73af0-54c3-4c95-886a-3e6de3a76471/oauth2/v2.0/authorize
client_id: <Client ID from https://microsoft.com/>
client_secret: <Client Secret from https://microsoft.com/>
scope: https://outlook.office365.com/IMAP.AccessAsUser.All openid email profile offline_access Mail.ReadWrite Contacts.ReadWrite Calendars.ReadWrite
revoke_endpoint:
token_endpoint: https://login.microsoftonline.com/<Tenant from https://microsoft.com >/oauth2/v2.0/token
user_endpoint: https://graph.microsoft.com/oidc/userinfo
include_granted_scopes: 
prompt: 
response_type: code
tenant: <Tenant from https://microsoft.com/>
```

Note: scope is a space separated list and you need openid and profile to get user
info at all, and email if you want the users email.

The issuer must be the provider's exact OIDC issuer. When a provider is saved,
the app fetches its `/.well-known/openid-configuration` document, verifies that
the returned issuer is an exact match, and stores the discovered authorization,
token, userinfo, JWKS, and revocation endpoints. Only HTTPS endpoints are
accepted. Private-network providers additionally require Nextcloud's
`allow_local_remote_servers` system setting.

Client secrets are write-only after creation. Leave the field blank while
editing to preserve the current secret, or enter a new value to rotate it.

After upgrading from a release before 0.1.11, an administrator must edit and
save every existing provider to add and validate its issuer. Existing linked
accounts are marked for reauthorization and are not refreshed against changed
or unvalidated provider metadata.
