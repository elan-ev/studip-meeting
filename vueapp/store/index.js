import Vue from "vue";
import Vuex from "vuex";

import error from "./error.module";
import config from "./config.module";

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    error,
    config
  }
});
