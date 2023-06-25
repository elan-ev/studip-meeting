<template>
  <div class="meeting-lobby">
    <h1>{{ $gettext('Das Meeting hat noch nicht begonnen') }}</h1>

    <div class="waiting-info-container">
      <img :src="loadingURL" width="24" height="24"/>
      <p>
        {{ $gettext('Sie treten dem Meeting automatisch bei, sobald es begonnen hat.') }}
      </p>
    </div>

  </div>
</template>

<script>

export default {
  name: "Lobby",
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
