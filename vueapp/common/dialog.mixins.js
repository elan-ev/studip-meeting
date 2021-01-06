export const dialog = {
    data() {
        return {
            parentDialogId: null
        }
    },
    mounted() {
        this.getParentDialogId()
    },
    created () {
        this.$on(["done", "cancel"], () => {
            this.dialogClose()
        });
    },
    methods: {
        dialogClose() {
            if (this.$children.length) {
                var dialogComponent = this.$children.filter( (children) => {
                    return children.$options.name == 'Dialog' 
                });
                if (dialogComponent.length) {
                    dialogComponent.forEach((dialog) => {
                        if (dialog.$data.id) {
                            $('#' + dialog.$data.id).dialog('close');
                        }
                    });
                }
            }
        },
        getParentDialogId() {
            var ParentDialogComponent = this.$parent.$children.filter( (children) => {
                return children.$options.name == 'Dialog' 
            });
            if (ParentDialogComponent.length) {
                ParentDialogComponent.forEach((dialog) => {
                    if (dialog.$data.id) {
                        this.parentDialogId = dialog.$data.id;
                    }
                });
            }
        }
    }
}