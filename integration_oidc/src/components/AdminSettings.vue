<template>
  <div id="ioidc_prefs" class="section">
    <div id="ioidc-content">
      <NcSettingsSection name="Integration OIDC" description="Generic OIDC integration engine."
        doc-url="https://github.com/SUNET/nextcloud-integration_oidc" @default="populate">
        <div class="external-label">
          <label for="Name">Name</label>
          <NcTextField id="Name" :value.sync="name" :label-outside="true" placeholder="Name" @update:value="check" />
        </div>
        <div class="external-label">
          <label for="AuthEndpoint">Auth Endpoint</label>
          <NcTextField id="AuthEndpoint" :value.sync="auth_endpoint" :label-outside="true" placeholder="Auth Endpoint"
            @update:value="check" />
        </div>
        <div class="external-label">
          <label for="TokenEndpoint">Token Endpoint</label>
          <NcTextField id="TokenEndpoint" :value.sync="token_endpoint" :label-outside="true"
            placeholder="Token Endpoint" @update:value="check" />
        </div>
        <div class="external-label">
          <label for="UserEndpoint">User Endpoint</label>
          <NcTextField id="UserEndpoint" :value.sync="user_endpoint" :label-outside="true" placeholder="User Endpoint"
            @update:value="check" />
        </div>
        <div class="external-label">
          <label for="Scope">Scope</label>
          <NcTextField id="Scope" :value.sync="scope" :label-outside="true" placeholder="Scope"
            @update:value="check" />
        </div>
        <div class="external-label">
          <label for="GrantType">GrantType</label>
          <NcTextField id="GrantType" :value.sync="grant_type" :label-outside="true" placeholder="Grant Type"
            @update:value="check" />
        </div>
        <div class="external-label">
          <label for="ClientID">Client ID</label>
          <NcPasswordField id="ClientID" :value.sync="client_id" :label-outside="true" placeholder="Client ID"
            @update:value="check" />
        </div>
        <div class="external-label">
          <label for="ClientSecret">Client Secret</label>
          <NcPasswordField id="ClientSecret" :value.sync="client_secret" :label-outside="true" @update:value="check"
            placeholder="Client Secret" />
        </div>
        <NcButton :disabled=true :readonly="readonly" :wide="true" text="Save" @click="register" :nativeType="submit"
          id="Button">
          <template #icon>
            <Check :size="20" id="Icon" />
          </template>
          Save
        </NcButton>
        <div id="oidc-configured">
          <ul id="oidc-configured-list">
            <NcListItemIcon v-for="i in configured" :name="i.name" :subname="i.token_endpoint">
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
</template>

<script>
// Icons
import Check from 'vue-material-design-icons/Check.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'

// Nextcloud components
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcListItemIcon from '@nextcloud/vue/dist/Components/NcListItemIcon.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

// Nextcloud API
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
  name: 'AdminSettings',

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
      auth_endpoint: "",
      client_id: "",
      client_secret: "",
      configured: [],
      grant_type: "",
      name: "",
      scope: "",
      token_endpoint: "",
      user_endpoint: "",
    }
  },
  mounted() {
    const url = generateUrl('/apps/integration_oidc/query');
    axios.get(url).then((result) => this.configured = result.data);
  },
  methods: {
    check() {
      var button = document.getElementById("Button");
      if (
        this.auth_endpoint != "" &&
        this.client_id != "" &&
        this.client_secret != "" &&
        this.grant_type != "" &&
        this.name != "" &&
        this.scope != "" &&
        this.token_endpoint != "" &&
        this.user_endpoint != ""
      ) {
        button.disabled = false;
      } else {
        button.disabled = true;
      }
    },
    async remove(id) {
      const url = generateUrl('/apps/integration_oidc/remove');
      let res = await axios.post(url, { "id": id });
      console.log(res, id);
      if (res.data.status == "success") {
        this.configured = this.configured.filter((item) => item.id != id);
      }
    },
    async register() {
      const url = generateUrl('/apps/integration_oidc/register');
      var payload = {
        'auth_endpoint': this.auth_endpoint,
        'client_id': this.client_id,
        'client_secret': this.client_secret,
        'grant_type': this.grant_type,
        'name': this.name,
        'scope': this.scope,
        'token_endpoint': this.token_endpoint,
        'user_endpoint': this.user_endpoint
      };
      let res = await axios.post(url, payload);
      if (res.data.status == "success") {
        payload.id = res.data.id;
        this.configured.push(payload);
        this.auth_endpoint = "";
        this.client_id = "";
        this.client_secret = "";
        this.grant_type = "";
        this.name = "";
        this.scope = "";
        this.token_endpoint = "";
        this.user_endpoint = "";
        this.check();
      }
    },
  },
}
</script>
