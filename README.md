# nextcloud-integration_oidc

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

Note: scope is a space separated list and you need openid and profile to get user
info at all, and email if you want the users email.
