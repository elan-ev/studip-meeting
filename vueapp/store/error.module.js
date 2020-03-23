import ApiService from "@/common/api.service";

import {
    ERROR_COMMIT,
    ERROR_CLEAR,
} from "./actions.type";

import {
    ERROR_SET,
} from "./mutations.type";

const state = {
    error: null
};

const getters = {
    error(state) {
        return state.error;
    }
};

const actions = {
    [ERROR_COMMIT](context, error) {
        context.commit(ERROR_SET, error);
    },
    [ERROR_CLEAR](context) {
        context.commit(ERROR_SET, null);
    }
};

const mutations = {
    [ERROR_SET](state, data) {
        state.error = data;
    }
};

export default {
  state,
  actions,
  mutations,
  getters
};
