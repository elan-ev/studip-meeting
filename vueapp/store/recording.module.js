import Vue from "vue";
import ApiService from "@/common/api.service";

import {
    RECORDING_LIST,
    RECORDING_SHOW,
    RECORDING_DELETE,
} from "./actions.type";

import {
    RECORDING_LIST_SET,
    RECORDING_SET
} from "./mutations.type";

const initialState = {
    recording_list: null,
    recording: {}
};

const getters = {
    recording_list(state) {
        return state.recording_list;
    },
    recording(state) {
        return state.recording;
    },
};

export const state = { ...initialState };

export const actions = {
    async [RECORDING_LIST](context, room_id) {
        context.commit(RECORDING_LIST_SET, null);

        return ApiService.get('rooms/' + CID + '/' + room_id + '/recordings')
            .then(({ data }) => {
                if ((data.default && data.default.length) || data.opencast) {
                    context.commit(RECORDING_LIST_SET, data);
                }
            });
    },

    async [RECORDING_SHOW](context, id) {
        return ApiService.get('recordings/' + id)
            .then(({ data }) => {
                context.commit(RECORDING_SET, data.recording);
            });
    },

    async [RECORDING_DELETE](context, recording) {
        return await ApiService.delete('recordings/' + CID + '/' + recording.room_id + '/' + recording.recordID);
    },
};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    [RECORDING_LIST_SET](state, data) {
        state.recording_list = data;
    },
    [RECORDING_SET](state, data) {
        state.recording = data;
    }
};

export default {
  state,
  actions,
  mutations,
  getters
};
