import Vue from "vue";
import ApiService from "@/common/api.service";

import {
    CONFIG_READ,
    CONFIG_UPDATE,
    CONFIG_CREATE,
    CONFIG_DELETE,
    CONFIG_LIST_READ,
    CONFIG_COURSE_READ
} from "./actions.type";

import {
    CONFIG_SET,
    CONFIG_COURSE_SET
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
    },
    course_config: {
        "title": "",
        "introduction": "",
        "display": {}
    },
    course_groups: {
    }
};

const getters = {
    config(state) {
        return state.config;
    },
    drivers(state) {
        return state.drivers;
    },
    course_config(state) {
        return state.course_config;
    },
    course_groups(state) {
        return state.course_groups;
    }
};

export const state = { ...initialState };

export const actions = {
    async [CONFIG_LIST_READ](context) {
        return ApiService.get('config/list')
            .then(({ data }) => {
                if (data != []) {
                    context.commit(CONFIG_SET, data);
                }
            });
    },

    async [CONFIG_COURSE_READ](context, cid) {
        return ApiService.get('course/' + cid + '/config')
            .then(({ data }) => {
                if (data != []) {
                    context.commit(CONFIG_COURSE_SET, data);
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
    }
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

    [CONFIG_COURSE_SET](state, data) {
        if (data.config) {
            state.config = data.config;
        }
        if (data.course_config) {
            state.course_config = data.course_config;
        }
        if (data.course_groups) {
            state.course_groups = data.course_groups;
        }
    }
};

export default {
  state,
  actions,
  mutations,
  getters
};
