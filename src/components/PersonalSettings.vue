<template>
  <div id="ioidc_prefs" class="section">
    <div id="ioidc-content">
      <NcSettingsSection
        name="Integration OIDC"
        description="Connect these services to your Nextcloud account."
        doc-url="https://github.com/SUNET/nextcloud-integration_oidc"
        @default="populate"
      >
        <div id="oidc-unconfigured">
          <NcButton
            :disabled="false"
            :readonly="readonly"
            :wide="true"
            :nativeType="submit"
            v-bind:key="i.id"
            v-for="i in unconfigured"
            :text="i.name"
            :id="i.id"
            @click="(_) => register(i.id)"
          >
            {{ i.name }}
          </NcButton>
        </div>
        <div id="oidc-configured">
          <ul id="oidc-configured-list">
            <NcListItemIcon
              v-for="i in configured"
              :name="i.name"
              v-bind:key="i.id"
              :subname="i.token_endpoint"
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
import Delete from "vue-material-design-icons/Delete.vue";

// Nextcloud components
import {
  NcActionButton,
  NcActions,
  NcButton,
  NcListItemIcon,
  NcSettingsSection,
} from "@nextcloud/vue";

// Nextcloud API
import axios from "@nextcloud/axios";
import { generateUrl, getBaseUrl } from "@nextcloud/router";
import { getCurrentUser } from "@nextcloud/auth";

export default {
  name: "PersonalSettings",

  components: {
    Delete,
    NcActionButton,
    NcActions,
    NcButton,
    NcListItemIcon,
    NcSettingsSection,
  },

  props: [],
  data() {
    return {
      available: [],
      configured: [],
      unconfigured: [],
    };
  },
  methods: {
    async private_load() {
      var url = generateUrl("/apps/integration_oidc/query");
      var result = await axios.get(url);
      this.available = result.data;
      console.log("available", this.available);
      url = generateUrl("/apps/integration_oidc/query_user");
      result = await axios.get(url);
      // The configuired providers for this user
      this.configured = result.data;
      console.log("configured", this.configured);
      if (this.configured.length === 0) {
        this.unconfigured = this.available;
      } else {
        // All the available not in configured
        this.unconfigured = this.available.filter((a) =>
          this.configured.every((c) => c.provider_id !== a.id),
        );
        console.log("unconfigured", this.unconfigured);
      }
    },
    async remove(id) {
      var url = generateUrl("/apps/integration_oidc/remove_user");
      let params = { id: id };
      let result = await axios.post(url, params);
      if (result.data.status == "success") {
        var removed = this.configured.find((a) => a.id == id);
        console.log("removed", removed);
        this.configured = this.configured.filter((a) => a.id !== id);
        this.unconfigured.push(removed);
      }
    },
    async register(provider_id) {
      var provider = this.available.find((a) => a.id == provider_id);
      console.log("configured", this.configured);
      console.log("provider", provider, "with id", provider_id);
      const user = getCurrentUser();

      let state = self.crypto.randomUUID();
      let nonce = self.crypto.randomUUID();

      var client_config = {
        access_type: provider.accessType,
        client_id: provider.clientId,
        include_granted_scopes:
          provider.include_granted_scopes?.toLowerCase?.() === "true",
        nonce: nonce,
        prompt: provider.prompt,
        redirect_uri:
          getBaseUrl() + "/index.php/apps/integration_oidc/callback",
        response_type: provider.responseType || "code",
        scope: provider.scope,
        state: state,
      };
      var form = document.createElement("form");
      form.setAttribute("method", "GET"); // Send as a GET request.
      form.setAttribute("action", provider.authEndpoint);

      var url = generateUrl("/apps/integration_oidc/register_state");
      let params = { providerId: provider_id, state: state, uid: user.uid };
      let result = await axios.post(url, params);
      if (result.data.status == "success") {
        // Add form parameters as hidden input values.
        for (var c in client_config) {
          console.log(c, client_config[c]);
          var input = document.createElement("input");
          input.setAttribute("type", "hidden");
          input.setAttribute("name", c);
          input.setAttribute("value", client_config[c]);
          form.appendChild(input);
        }
        console.log("form", form);

        document.body.appendChild(form);
        form.submit();
      }
    },
  },
  mounted() {
    this.private_load().then(() => {});
  },
};
</script>
