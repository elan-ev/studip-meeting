<template>
  <div>
    <h1 v-translate>Das Meeting hat noch nicht begonnen </h1>

    <p v-translate><img :src="loadingURL">
      Sie treten dem Meeting automatisch bei, sobald es begonnen hat.
    </p>

  </div>
</template>

<script>
import {mapGetters} from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";
import ServerDialog from "@/components/ServerDialog";

import {
  CONFIG_LIST_READ,
  CONFIG_CREATE, CONFIG_DELETE
} from "@/store/actions.type";

import {ii
  CONFIG_SET,
} from "@/store/mutations.type";

export default {
  name: "Lobby",

  components: {
    StudipButton,
    StudipTooltipIcon,
    MessageBox,
    StudipIcon,
    ServerDialog
  },

  data() {
    return {
      message: null,
      server_object: {},
      serverDialogVisible: false,
      changes_made: false
    }
  },
  computed: {
    loadingURL() {
      return LOADING_ICON_URL;
    }
  },
  async created() {
    this.interval = setInterval(function () {
      this.checkMeeting();
    }.bind(this), 5000);
  },
  methods: {
    checkMeeting: function () {
      window.location.reload();
    },
  }
};
</script>
