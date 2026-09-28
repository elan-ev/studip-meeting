import {onBeforeUnmount, onMounted} from 'vue';

export default function useDetectOutsideClick(component, callback) {
    function listener(event) {
        if (component.value && event.composedPath().includes(component.value)) {
            return;
        }
        if (typeof callback === 'function') {
            callback();
        }
    }

    onMounted(() => window.addEventListener('click', listener));
    onBeforeUnmount(() => window.removeEventListener('click', listener));
}
