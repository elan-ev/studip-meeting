import Vue from "vue";
import ApiService from "@/common/api.service";

import {
    CONFIG_READ,
    CONFIG_UPDATE,
    CONFIG_CREATE,
    CONFIG_DELETE,
    CONFIG_CLEAR,
    CONFIG_LIST_READ,
    CONFIG_LIST_CLEAR
} from "./actions.type";

import {
    CONFIG_SET,
    CONFIG_LIST_SET
} from "./mutations.type";

const initialState = {
    config_list: [],
    config: {
        'url' :      null,
        'user':      null,
        'password':  null,
        'ltikey':    null,
        'ltisecret': null
    }
};

const getters = {
    config_list(state) {
        return state.config_list;
    },
    config(state) {
        return state.config;
    }
};

export const state = { ...initialState };

export const actions = {
    async [CONFIG_LIST_READ](context) {
        return new Promise(resolve => {
          ApiService.get('config')
            .then(({ data }) => {
                context.commit(CONFIG_LIST_SET, data);
                resolve(data);
            });
        });
    },

    async [CONFIG_READ](context, id) {
        return ApiService.get('config/' + id)
            .then(({ data }) => {
                context.commit(CONFIG_SET, data.config);
            });
    },

    async [CONFIG_DELETE](context, id) {
        await ApiService.delete('config/' + id);
        context.dispatch(CONFIG_LIST_READ);
    },

    async [CONFIG_UPDATE](context, params) {
        return ApiService.update('config', params.id, {
            config: params
        });
    },

    async [CONFIG_CREATE](context, params) {
        return await ApiService.post('config', {
            config: params
        });
    },

    [CONFIG_CLEAR](context) {
        context.commit(CONFIG_SET, {});
    },

    [CONFIG_LIST_CLEAR](context) {
        context.commit(CONFIG_LIST_SET, {});
    },
};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    [CONFIG_LIST_SET](state, data) {
        state.config_list = data;
    },

    [CONFIG_SET](state, data) {
        state.config = data;
    }
};

export default {
  state,
  actions,
  mutations,
  getters
};
