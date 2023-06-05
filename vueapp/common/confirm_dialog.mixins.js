export const confirm_dialog = {
    data() {
        return {
            showConfirmDialog: false
            /* showConfirmDialog: { // Possible values
                title: this.$gettext('Bitte bestätigen Sie die Aktion'),
                question: (Optional) this.$gettext('Sind Sie sicher, dass Sie das tun möchten?'),
                alert: (Optional) this.$gettext('Something went wrong, do you want to continue?'),
                message: (Optional) this.$gettext('Your request is completed.'),
                hieght: (Optional) "200",
                confirm_callback: (Optional) null, //null, name of the method to call if accepted,
                confirm_callback_data: (Optional) {},
                close_callback: (Optional) null, //optional: null, name of the method to call if canceled,
                close_callback_data: (Optional) {},
                confirmText: (Optional) $gettext('Ja'),
                confirmClass: (Optional) 'accept',
                closeText: (Optional) $gettext('Nein'),
                closeClass: (Optional) "cancel",
            } */
        }
    },
    methods: {
        performDialogConfirm(callback, data = null) {
            this.showConfirmDialog = false;
            if (callback && this[callback] != undefined) {
                this[callback](data);
            }
        },
        performDialogClose(callback, data = null) {
            this.showConfirmDialog = false;
            if (callback && this[callback] != undefined) {
                this[callback](data);
            }
        },
    }
}