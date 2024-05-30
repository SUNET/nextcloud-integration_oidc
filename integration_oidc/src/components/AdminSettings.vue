<template>
  <div id="ioidc_prefs" class="section">
    <div id="ioidc-content">
      <NcSettingsSection name="Integration OIDC" description="Generic OIDC integration engine."
        doc-url="https://github.com/SUNET/nextcloud-integration_oidc" @default="populate">
        <form id="ioidc-form" >
          <div class="external-label">
            <label for="Name">Name</label>
            <NcTextField id="Name" :value.sync="name" :label-outside="true" placeholder="Name" @update:value="check" />
          </div>
          <div class="external-label">
            <label for="TokenEndpoint">Token Endpoint</label>
            <NcTextField id="TokenEndpoint" :value.sync="token_endpoint" :label-outside="true"
              placeholder="Token Endpoint" @update:value="check" />
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
        </form>
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
      client_id: "",
      client_secret: "",
      configured: [],
      id: -1,
      name: "",
      token_endpoint: "",
    }
  },
  mounted() {
    const url = generateUrl('/apps/integration_oidc/query');
    axios.get(url).then((result) => this.configured = result.data);
  },
  methods: {
    check() {
      let name = document.getElementById("Name");
      let client_id = document.getElementById("ClientID");
      let client_secret = document.getElementById("ClientSecret");
      let token_endpoint = document.getElementById("TokenEndpoint");
      var button = document.getElementById("Button");
      if (name == null || client_id == null || client_secret == null || token_endpoint == null) {
        return;
      }
      if (name.value != "" && client_id.value != "" && client_secret.value != "" && token_endpoint.value != "") {
        button.type = "primary";
        button.disabled = false;
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
      var name = document.getElementById("Name").value;
      var client_id = document.getElementById("ClientID").value;
      var client_secret = document.getElementById("ClientSecret").value;
      var token_endpoint = document.getElementById("TokenEndpoint").value;
      var payload = { 'name': name, 'client_id': client_id, 'client_secret': client_secret, 'token_endpoint': token_endpoint };
      let res = await axios.post(url, payload);
      if (res.data.status == "success") {
        payload.id = res.data.id;
        this.configured.push(payload);
        document.getElementById("ioidc-form").reset();
      }
    },
  },
}
</script>
