import Vue from "vue";
import ApiService from "@/common/api.service";

import {
    ROOM_LIST,
    ROOM_READ,
    ROOM_UPDATE,
    ROOM_CREATE,
    ROOM_DELETE,
    ROOM_JOIN,
    ROOM_STATUS,
    ROOM_INFO,
    ROOM_JOIN_GUEST
} from "./actions.type";

import {
    ROOMS_LIST_SET,
    ROOM_CLEAR
} from "./mutations.type";

const initialState = {
    rooms_list: [],
    room: {
        "name": "",
        "driver_name": "",
        "server_index": "",
        "join_as_moderator": "0",
        "features": {}
    }
};

const getters = {
    rooms_list(state) {
        return state.rooms_list;
    },
    room(state) {
        return state.room;
    }
};

export const state = { ...initialState };

export const actions = {
    async [ROOM_LIST](context) {
        return ApiService.get('course/' + CID + '/rooms')
            .then(({ data }) => {
                if (data != []) {
                    context.commit(ROOMS_LIST_SET, data);
                }
            });
    },

    async [ROOM_READ](context, id) {
        return ApiService.get('rooms/' + id)
            .then(({ data }) => {
                context.commit(ROOMS_LIST_SET, data.config);
            });
    },

    async [ROOM_DELETE](context, id) {
        await ApiService.delete('rooms/' + CID + '/' + id);
        context.dispatch(ROOM_LIST);
    },

    async [ROOM_UPDATE](context, params) {
        return ApiService.update('rooms', params.id, {
            "active": params.active,
            "name": params.name,
            "recording_url": params.recording_url,
            "join_as_moderator": params.join_as_moderator,
            "cid": CID
        });
    },

    async [ROOM_CREATE](context, params) {
        params.cid = CID;
        return await ApiService.post('rooms', params);
    },

    async [ROOM_JOIN](context, id) {
        return ApiService.get('rooms/join/' + CID + '/' + id);
    },

    async [ROOM_JOIN_GUEST](context, room) {
        return ApiService.get('rooms/join/' + CID + '/' + room.id + '/' + room.guest_name + '/guest');
    },

    async [ROOM_STATUS](context, id) {
        return ApiService.get('rooms/' + CID + '/' + id + '/status');
    },

    async [ROOM_INFO](context, id) {
        return ApiService.get('rooms/' + CID + '/' + id + '/info');
    },
};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    [ROOMS_LIST_SET](state, data) {
        state.rooms_list = data;
    },
    [ROOM_CLEAR](state) {
        state.room = {
            "name": "",
            "driver_name": "",
            "server_index": "",
            "join_as_moderator": "0",
            "features": {}
        }
    }
};

export default {
  state,
  actions,
  mutations,
  getters
};
