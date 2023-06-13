export const a11y = {
    data() {
        return {
            fieldsetHandlers: {
                click: this.handleAriaAttr,
                keypress: this.handleKeypress
            }
        }
    },
    methods: {
        handleKeypress(event) {
            let target = event.target;
            if (!target || event.key !== 'Enter') {
                return;
            }
            target.click();
        },
        handleAriaAttr(event) {
            let target = event.target;
            if (!target) {
                return;
            }
            let collapsed = target.parentNode.classList.contains('collapsed');
            target.setAttribute('aria-expanded', collapsed);
        }
    }
}
