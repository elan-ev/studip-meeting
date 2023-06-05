import Vue from "vue";
import Vuex from "vuex";

import error from "./error.module";
import config from "./config.module";
import rooms_list from "./room.module";
import recording_list from "./recording.module";
import feedback from "./feedback.module";
import folder from "./folder.module";
import default_slide from "./default_slide.module";
import messages from "./messages.module";


Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    error,
    config,
    rooms_list,
    recording_list,
    feedback,
    folder,
    default_slide,
    messages
  }
});
