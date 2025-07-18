<template>
  <div id="ioidc_prefs" class="section">
    <div id="ioidc-content">
      <NcSettingsSection
        name="Integration OIDC"
        description="Generic OIDC integration engine."
        doc-url="https://github.com/SUNET/nextcloud-integration_oidc"
        @default="populate"
      >
        <p>
          Explanations for the parameters are
          <a :href="documentation_link" target="_blank">documented here</a>.
          Note that some parameters are provider specific.
        </p>
        <form id="ioidc-form" @submit.prevent="save">
          <div class="external-label">
            <label for="Provider">Provider</label>
            <select
              id="Provider"
              name="provider"
              @change="select"
              v-model="provider"
            >
              <option id="Generic" required value="Generic" selected>
                Generic
              </option>
              <option id="Google" required value="Google">Google</option>
              <option id="Microsoft" required value="Microsoft">
                Microsoft
              </option>
            </select>
          </div>
          <div class="external-label">
            <label for="Name">Name</label>
            <NcTextField
              id="Name"
              v-model="name"
              :label-outside="true"
              placeholder="Name"
            />
          </div>
          <div class="external-label">
            <label for="Tenant">Tenant (required for Microsoft)</label>
            <NcTextField
              id="Tenant"
              v-model="tenant"
              :label-outside="true"
              placeholder="Tenant"
              @update:value="set_ms_urls"
            />
          </div>
          <div class="external-label">
            <label for="AuthEndpoint">Auth Endpoint</label>
            <NcTextField
              id="AuthEndpoint"
              v-model="auth_endpoint"
              :label-outside="true"
              placeholder="Auth Endpoint"
            />
          </div>
          <div class="external-label">
            <label for="RevokeEndpoint">Revoke Endpoint</label>
            <NcTextField
              id="RevokeEndpoint"
              v-model="revoke_endpoint"
              :label-outside="true"
              placeholder="Revoke Endpoint"
            />
          </div>
          <div class="external-label">
            <label for="TokenEndpoint">Token Endpoint</label>
            <NcTextField
              id="TokenEndpoint"
              v-model="token_endpoint"
              :label-outside="true"
              placeholder="Token Endpoint"
            />
          </div>
          <div class="external-label">
            <label for="UserEndpoint">User Endpoint</label>
            <NcTextField
              id="UserEndpoint"
              v-model="user_endpoint"
              :label-outside="true"
              placeholder="User Endpoint"
            />
          </div>
          <div class="external-label">
            <label for="AccessType">Access Type (optional)</label>
            <NcTextField
              id="AccessType"
              v-model="access_type"
              :label-outside="true"
              placeholder="AccessType"
            />
          </div>
          <div class="external-label">
            <label for="ClientID">Client ID</label>
            <NcPasswordField
              id="ClientID"
              v-model="client_id"
              :label-outside="true"
              placeholder="Client ID"
            />
          </div>
          <div class="external-label">
            <label for="ClientSecret">Client Secret</label>
            <NcPasswordField
              id="ClientSecret"
              v-model="client_secret"
              :label-outside="true"
              placeholder="Client Secret"
            />
          </div>
          <div class="external-label">
            <label for="Display">Display (optional)</label>
            <NcTextField
              id="Display"
              v-model="display"
              :label-outside="true"
              placeholder="Display"
            />
          </div>
          <div class="external-label">
            <label for="HD">HD (optional)</label>
            <NcTextField
              id="HD"
              v-model="hd"
              :label-outside="true"
              placeholder="HD"
            />
          </div>
          <div class="external-label">
            <label for="IncludeGrantedScopes"
              >Include Granted Scopes (optional)</label
            >
            <NcTextField
              id="IncludeGrantedScopes"
              v-model="include_granted_scopes"
              :label-outside="true"
              placeholder="Include Granted Scopes"
            />
          </div>
          <div class="external-label">
            <label for="Prompt">Prompt (optional)</label>
            <NcTextField
              id="Prompt"
              v-model="prompt"
              :label-outside="true"
              placeholder="Prompt"
            />
          </div>
          <div class="external-label">
            <label for="ResponseMode">Response Mode (optional)</label>
            <NcTextField
              id="ResponseMode"
              v-model="response_mode"
              :label-outside="true"
              placeholder="Response Mode"
            />
          </div>
          <div class="external-label">
            <label for="ResponseType">Response Type</label>
            <NcTextField
              id="ResponseType"
              v-model="response_type"
              :label-outside="true"
              placeholder="Response Type"
            />
          </div>
          <div class="external-label">
            <label for="Scope">Scope</label>
            <NcTextField
              id="Scope"
              v-model="scope"
              :label-outside="true"
              placeholder="Scope"
            />
          </div>
          <NcButton
            :disabled="!isValid"
            :readonly="readonly"
            :wide="true"
            text="Save"
            @click="register"
            :nativeType="submit"
            id="Button"
          >
            <template #icon>
              <Check :size="20" id="Icon" />
            </template>
            Save
          </NcButton>
        </form>
        <div id="oidc-configured">
          <ul id="oidc-configured-list">
            <NcListItemIcon
              v-for="i in configured"
              :name="i.name"
              :subname="i.token_endpoint"
              v-bind:key="i.id"
            >
              <NcActions>
                <NcActionButton @click="(_) => remove(i.id)">
                  <template #icon>
                    <Delete :size="20" />
                  </template>
                  Delete
                </NcActionButton>
              </NcActions>
            </NcListItemIcon>
          </ul>
        </div>
      </NcSettingsSection>
    </div>
  </div>
</template>

<script>
// Icons
import Check from "vue-material-design-icons/Check.vue";
import Delete from "vue-material-design-icons/Delete.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";

// Nextcloud components
import {
  NcActionButton,
  NcActions,
  NcButton,
  NcListItemIcon,
  NcPasswordField,
  NcSettingsSection,
  NcTextField,
} from "@nextcloud/vue";

// Nextcloud API
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

export default {
  name: "AdminSettings",

  components: {
    Check,
    Delete,
    NcActionButton,
    NcActions,
    NcButton,
    NcListItemIcon,
    NcPasswordField,
    NcSettingsSection,
    NcTextField,
    Pencil,
  },
  props: [],
  data() {
    return {
      access_type: "",
      auth_endpoint: "",
      client_id: "",
      client_secret: "",
      configured: [],
      documentation_link:
        "https://openid.net/specs/openid-connect-core-1_0.html#AuthRequest",
      display: "",
      hd: "",
      include_granted_scopes: "",
      name: "",
      prompt: "",
      provider: "Generic",
      revoke_endpoint: "",
      response_mode: "",
      response_type: "",
      scope: "",
      tenant: "",
      type: "generic",
      token_endpoint: "",
      user_endpoint: "",
    };
  },
  mounted() {
    const url = generateUrl("/apps/integration_oidc/query");
    axios.get(url).then((result) => (this.configured = result.data));
  },
  computed: {
    isValid() {
      return (
        this.auth_endpoint !== "" &&
        this.client_id !== "" &&
        this.client_secret !== "" &&
        this.name !== "" &&
        this.response_type !== "" &&
        this.revoke_endpoint !== "" &&
        this.scope !== "" &&
        this.token_endpoint !== "" &&
        this.user_endpoint !== ""
      );
    },
  },
  methods: {
    async set_ms_urls() {
      if (this.type === "microsoft") {
        this.auth_endpoint =
          "https://login.microsoftonline.com/" +
          this.tenant +
          "/oauth2/v2.0/authorize";
        this.revoke_endpoint =
          "https://login.microsoftonline.com/" +
          this.tenant +
          "/oauth2/v2.0/logout";
        this.token_endpoint =
          "https://login.microsoftonline.com/" +
          this.tenant +
          "/oauth2/v2.0/token";
      }
    },
    select(e) {
      var selected = e.target.value;
      var hidden = [];
      switch (selected) {
        case "Google":
          hidden = ["ResponseMode", "Tenant"];
          this.auth_endpoint =
            this.auth_endpoint === ""
              ? "https://accounts.google.com/o/oauth2/v2/auth"
              : this.auth_endpoint;
          this.documentation_link =
            "https://developers.google.com/identity/openid-connect/openid-connect#authenticationuriparameters";
          this.prompt = this.prompt === "" ? "consent" : this.prompt;
          this.response_type =
            this.response_type === "" ? "code" : this.response_type;
          this.revoke_endpoint =
            this.revoke_endpoint == ""
              ? "https://oauth2.googleapis.com/revoke"
              : this.revoke_endpoint;
          this.scope =
            this.scope === ""
              ? "https://mail.google.com/ openid profile email"
              : this.scope;
          this.token_endpoint =
            this.token_endpoint === ""
              ? "https://oauth2.googleapis.com/token"
              : this.token_endpoint;
          this.user_endpoint =
            this.user_endpoint === ""
              ? "https://accounts.google.com/o/oauth2/v2/user"
              : this.user_endpoint;
          this.type = "google";
          break;
        case "Microsoft":
          hidden = ["AccessType", "Display", "HD", "IncludeGrantedScopes"];
          this.auth_endpoint =
            this.auth_endpoint === ""
              ? "https://login.microsoftonline.com/common/oauth2/v2.0/authorize"
              : this.auth_endpoint;
          this.documentation_link =
            "https://learn.microsoft.com/en-us/entra/identity-platform/v2-protocols-oidc#send-the-sign-in-request";
          this.prompt = this.prompt === "" ? "consent" : this.prompt;
          this.response_type =
            this.response_type === "" ? "code id_token" : this.response_type;
          this.revoke_endpoint =
            this.revoke_endpoint === ""
              ? "https://login.microsoftonline.com/common/oauth2/v2.0/logout"
              : this.revoke_endpoint;
          this.scope =
            this.scope === ""
              ? "openid profile email offline_access"
              : this.scope;
          this.token_endpoint =
            this.token_endpoint === ""
              ? "https://login.microsoftonline.com/common/oauth2/v2.0/token"
              : this.token_endpoint;
          this.user_endpoint =
            this.user_endpoint === ""
              ? "https://graph.microsoft.com/oidc/userinfo"
              : this.user_endpoint;
          this.type = "microsoft";
          break;
        default:
          this.documentation_link =
            "https://openid.net/specs/openid-connect-core-1_0.html#AuthRequest";
          this.type = "generic";
          hidden = [];
      }
      var form = document.getElementById("ioidc-form");
      for (var i = 0; i < form.length; i++) {
        var input = form[i];
        var label = document.querySelector('label[for="' + input.id + '"]');
        console.log(input.id);
        if (input.id) {
          if (hidden.includes(input.id)) {
            console.log("hidden");
            input.parentNode.style.display = "none";
            label.style.display = "none";
          } else {
            console.log("visible");
            input.parentNode.style.display = "block";
            label.style.display = "block";
          }
        }
      }
    },
    async remove(id) {
      const url = generateUrl("/apps/integration_oidc/remove");
      let res = await axios.post(url, { id: id });
      console.log(res, id);
      if (res.data.status == "success") {
        this.configured = this.configured.filter((item) => item.id != id);
      }
    },
    async register() {
      const url = generateUrl("/apps/integration_oidc/register");
      var payload = {
        access_type: this.access_type,
        auth_endpoint: this.auth_endpoint,
        client_id: this.client_id,
        client_secret: this.client_secret,
        display: this.display,
        hd: this.hd,
        include_granted_scopes: this.include_granted_scopes,
        name: this.name,
        prompt: this.prompt,
        revoke_endpoint: this.revoke_endpoint,
        response_endpoint: this.response_endpoint,
        response_mode: this.response_mode,
        response_type: this.response_type,
        scope: this.scope,
        tenant: this.tenant,
        token_endpoint: this.token_endpoint,
        user_endpoint: this.user_endpoint,
      };
      let res = await axios.post(url, payload);
      if (res.data.status == "success") {
        payload.id = res.data.id;
        this.access_type = "";
        this.auth_endpoint = "";
        this.client_id = "";
        this.client_secret = "";
        this.configured.push(payload);
        this.display = "";
        this.hd = "";
        this.include_granted_scopes = "";
        this.name = "";
        this.prompt = "";
        this.revoke_endpoint = "";
        this.response_endpoint = "";
        this.response_mode = "";
        this.response_type = "";
        this.scope = "";
        this.tenant = "";
        this.token_endpoint = "";
        this.user_endpoint = "";
      }
    },
  },
};
</script>
