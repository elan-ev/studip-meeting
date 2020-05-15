import Vue from "vue";
import Vuex from "vuex";

import error from "./error.module";
import config from "./config.module";
import rooms_list from "./room.module";
import recording_list from "./recording.module";

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    error,
    config,
    rooms_list,
    recording_list
  }
});
