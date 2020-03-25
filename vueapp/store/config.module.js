import Vue from "vue";
import ApiService from "@/common/api.service";

import {
    CONFIG_READ,
    CONFIG_UPDATE,
    CONFIG_CREATE,
    CONFIG_DELETE,
    CONFIG_CLEAR,
    CONFIG_LIST_READ,
} from "./actions.type";

import {
    CONFIG_SET,
} from "./mutations.type";

const initialState = {
    config: {
        "BigBlueButton": {
            "enable": "1",
            "display_name": "",
            "servers": []
        },
        "DfnVc": {
            "enable": "1",
            "display_name": "",
            "servers": []
        }
    },
    drivers: {
        "BigBlueButton": {
            "title":"BigBlueButton",
            "config": []
        },
        "DfnVc": {
            "title":"DfnVc",
            "config": []
        },
    }
};

const getters = {
    config(state) {
        return state.config;
    },
    drivers(state) {
        return state.drivers;
    }
};

export const state = { ...initialState };

export const actions = {
    async [CONFIG_LIST_READ](context) {
        return ApiService.get('config')
            .then(({ data }) => {
                if (data != []) {
                    context.commit(CONFIG_SET, data);
                }
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

};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    [CONFIG_SET](state, data) {
        if (data.config) {
            state.config = data.config;
        }
        if (data.drivers) {
            state.drivers = data.drivers;
        }
    },
};

export default {
  state,
  actions,
  mutations,
  getters
};
