import Vue from "vue";
import ApiService from "@/common/api.service";

import {
    DEFAULT_SLIDE_FONT_READ,
    DEFAULT_SLIDE_FONT_DELETE,
    DEFAULT_SLIDE_FONT_UPLOAD,
    DEFAULT_SLIDE_TEMPLATE_READ,
    DEFAULT_SLIDE_TEMPLATE_DELETE,
    DEFAULT_SLIDE_TEMPLATE_UPLOAD,
    DEFAULT_SLIDE_SAMPLE_TEMPLATE_DOWNLOAD
} from "./actions.type";

import {
    DEFAULT_SLIDE_FONT_SET,
    DEFAULT_SLIDE_TEMPLATE_SET,
} from "./mutations.type";

const initialState = {
    font: [
    ],
    templates: {
        pdf: [],
        php: []
    }
};

const getters = {
    font(state) {
        return state.font;
    },
    templates(state) {
        return state.templates;
    }
};

export const state = { ...initialState };

export const actions = {
    async [DEFAULT_SLIDE_FONT_READ](context) {
        return ApiService.get('default_slide/font')
            .then(({ data }) => {
                if (data.font && data.font != []) {
                    context.commit(DEFAULT_SLIDE_FONT_SET, data);
                }
            });
    },
    async [DEFAULT_SLIDE_FONT_UPLOAD](context, font_file) {
        return ApiService.upload('default_slide/font' , font_file);
    },
    async [DEFAULT_SLIDE_FONT_DELETE](context, font_type) {
        return await ApiService.delete('default_slide/font/' + font_type);
    },

    async [DEFAULT_SLIDE_TEMPLATE_READ](context) {
        return ApiService.get('default_slide/template')
            .then(({ data }) => {
                if (data != []) {
                    context.commit(DEFAULT_SLIDE_TEMPLATE_SET, data);
                }
            });
    },
    async [DEFAULT_SLIDE_TEMPLATE_UPLOAD](context, template_file) {
        return ApiService.upload('default_slide/template' , template_file);
    },
    async [DEFAULT_SLIDE_TEMPLATE_DELETE](context, {page, what}) {
        return await ApiService.delete('default_slide/template/' + page + '/' + what);
    },
    async [DEFAULT_SLIDE_SAMPLE_TEMPLATE_DOWNLOAD](context, what) {
        return ApiService.get('default_slide/template/sample/' + what);
    },
};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    [DEFAULT_SLIDE_FONT_SET](state, data) {
        if (data.font) {
            state.font = data.font;
        }
    },

    [DEFAULT_SLIDE_TEMPLATE_SET](state, data) {
        if (data.templates) {
            state.templates = data.templates;
        }
    },
};

export default {
  state,
  actions,
  mutations,
  getters
};
