import {
    MESSAGE_ADD,
    MESSAGES_CLEAR
} from "./actions.type";

import {
    MESSAGES_SET,
    MESSAGES_RESET
} from "./mutations.type";

const state = {
    messages: [],
    scrollto_messages: false
};

const getters = {
    messages(state) {
        return state.messages;
    },
    scrollto_messages(state) {
        return state.scrollto_messages;
    }
};

const actions = {
    [MESSAGE_ADD](context, message) {
        context.commit(MESSAGES_SET, message);
    },
    [MESSAGES_CLEAR](context) {
        context.commit(MESSAGES_RESET);
    },
};

const mutations = {
    [MESSAGES_SET](state, data) {
        state.messages.push(data);
    },
    [MESSAGES_RESET](state) {
        state.messages = [];
    }
};

export default {
    state,
    actions,
    mutations,
    getters
};
