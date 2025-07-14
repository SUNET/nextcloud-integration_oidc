# integration_oidc
This app provides a generic integration engine for OIDC providers.
It provides a way to connect any oidc account to Nextcloud.

It works by running the oidc auth flow for a user, including getting consent,
against any oidc provider and collects a refersh token that is used to
periodically refesh access tokens allowing other apps to use them for integration,
such as email sync, calendar sync or anything else.

## Configuration
For configuring an oidc provider for gmail, this is a sensible start:
```
name: Google
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
auth_endpoint: https://login.microsoftonline.com/beb73af0-54c3-4c95-886a-3e6de3a76471/oauth2/v2.0/authorize
client_id: <Client ID from https://microsoft.com/>
client_secret: <Client Secret from https://microsoft.com/>
scope: https://outlook.office365.com/IMAP.AccessAsUser.All openid email profile offline_access Mail.ReadWrite Contacts.ReadWrite Calendars.ReadWrite
revoke_endpoint: https://login.microsoftonline.com/<Tenant from https://microsoft.com >/oauth2/v2.0/logout
token_endpoint: https://login.microsoftonline.com/<Tenant from https://microsoft.com >/oauth2/v2.0/token
user_endpoint: https://graph.microsoft.com/oidc/userinfo
include_granted_scopes: 
prompt: 
response_mode: form_post
response_type: code
tenant: <Tenant from https://microsoft.com/>
```

Note: scope is a space separated list and you need openid and profile to get user
info at all, and email if you want the users email.
