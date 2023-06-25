import {translate} from 'vue-gettext';
const {gettextInterpolate} = translate;

export const gettextinterpolate = (text, params) => {
    if (text) {
        return gettextInterpolate(text, params);
    }
    return '';
}
