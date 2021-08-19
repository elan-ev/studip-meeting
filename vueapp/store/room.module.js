import Vue from "vue";
import ApiService from "@/common/api.service";

import {
    ROOM_LIST,
    ROOM_READ,
    ROOM_UPDATE,
    ROOM_CREATE,
    ROOM_DELETE,
    ROOM_STATUS,
    ROOM_INFO,
    ROOM_JOIN_GUEST,
    ROOM_INVITATION_LINK
} from "./actions.type";

import {
    ROOMS_LIST_SET,
    ROOMS_INFO_SET,
    ROOM_CLEAR,
    DEFAULT_ROOM_SET
} from "./mutations.type";

const initialState = {
    rooms_checked: false,
    rooms_list: [],
    rooms_info: [],
    room: {
        "name": "",
        "driver": "",
        "server_index": "",
        "join_as_moderator": "0",
        "features": {},
        "group_id": ""
    },
    default_room: {}
};

const getters = {
    rooms_list(state) {
        return state.rooms_list;
    },
    rooms_info(state) {
        return state.rooms_info;
    },
    room(state) {
        return state.room;
    },
    rooms_checked(state) {
        return state.rooms_checked;
    },
    default_room(state) {
        return state.default_room;
    }
};

export const state = { ...initialState };

export const actions = {
    async [ROOM_LIST](context) {
        return ApiService.get('course/' + CID + '/rooms')
            .then(({ data }) => {
                if (data != []) {
                    context.commit(ROOMS_LIST_SET, data);

                    // Extract default room from the room list.
                    var default_room = data.find(room => room.is_default == 1);
                    if (!default_room) {
                        default_room = {};
                    }
                    context.commit(DEFAULT_ROOM_SET, default_room);
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
        return await ApiService.delete('rooms/' + CID + '/' + id);
    },

    async [ROOM_UPDATE](context, params) {
        params.cid = CID;
        return ApiService.update('rooms' , params.id, params);
    },

    async [ROOM_CREATE](context, params) {
        params.cid = CID;
        return await ApiService.post('rooms', params);
    },

    async [ROOM_JOIN_GUEST](context, room) {
        return ApiService.get('rooms/join/' + CID + '/' + room.id + '/' + room.guest_name + '/guest');
    },

    async [ROOM_INVITATION_LINK](context, room) {
        return ApiService.get('rooms/invitationLink/' + CID + '/' + room.id);
    },

    async [ROOM_STATUS](context, id) {
        return ApiService.get('rooms/' + CID + '/' + id + '/status');
    },

    async [ROOM_INFO](context) {
        return ApiService.get('rooms/' + CID + '/info')
            .then(({ data }) => {
                context.commit(ROOMS_INFO_SET, data.rooms_info);
            });
    },
};
/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    [ROOMS_LIST_SET](state, data) {
        state.rooms_checked = true;
        state.rooms_list = data;
    },

    [ROOM_CLEAR](state) {
        state.room = {
            "name": "",
            "driver": "",
            "server_index": "",
            "join_as_moderator": "0",
            "features": {},
            "group_id": ""
        }
    },

    [ROOMS_INFO_SET](state, data) {
        state.rooms_info = data;
    },

    [DEFAULT_ROOM_SET](state, data) {
        state.default_room = data;
    }
};

export default {
  state,
  actions,
  mutations,
  getters
};
