import Vue from "vue";
import ApiService from "@/common/api.service";

import {
    FOLDER_READ,
    FOLDER_CREATE,
} from "./actions.type";

import {
    FOLDER_SET,
} from "./mutations.type";

const initialState = {
    folder: {}
};

const getters = {
    folder(state) {
        return state.folder;
    }
};

export const state = { ...initialState };

export const actions = {
    async [FOLDER_READ](context, folder_id) {
        return ApiService.get('folders/' + CID + '/' + folder_id)
            .then(({ data }) => {
                if (data != []) {
                    context.commit(FOLDER_SET, data);
                }
            });
    },
    async [FOLDER_CREATE](context, params) {
        params.cid = CID;
        return ApiService.post('folders/new_folder' , params);
    },
};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    [FOLDER_SET](state, data) {
        if (data.folder) {
            state.folder = data.folder;
        }
    },
};

export default {
  state,
  actions,
  mutations,
  getters
};
