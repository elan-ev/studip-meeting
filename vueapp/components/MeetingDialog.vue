<template>
    <div class="meeting-dialog" :id="id">
        <div class="dialog-content">
            <slot name="content"></slot>
        </div>
        <div class="dialog-buttons" style="display: none;">
            <slot name="buttons"></slot>
        </div>
    </div>
</template>

<script>
export default {
    name: "Dialog",
    props: {
        title: {
            type: String,
            required: true
        },
        parentId: [String],
        minHeight: {
            type: Number,
            required: false,
        },
        confirmation: {
            type: Boolean,
            required: false,
        }
    },
    data() {
        return {
            id: null
        }
    },
    methods: {
        setUniqueDialogId() {
            var uniqueIdArr = [];
            uniqueIdArr.push('dialog');
            if (this.$parent.$options.name) {
                var parentName = this.$parent.$options.name;
                uniqueIdArr.push(parentName.toLowerCase());
            }
            if (this._uid) {
                uniqueIdArr.push(this._uid);
            }
            this.id = uniqueIdArr.join('-');
        },
        showDialog() {
            let self = this;
            // handle mobile devices
            let options;
            if (window.innerWidth < 600) {
                options = {
                    width: '100%',
                    position: { my: "top", at: "top", of: window },
                }
            } else {
                options = {
                    minWidth: 500,
                    position: { my: "center", at: "center", of: window },
                }
            }
            options.maxHeight = $(window).height() * 0.8;
            options.modal = true;
            options.title = this.title;
            if (this.minHeight) {
                options.minHeight = this.minHeight;
            }
            if (this.confirmation) {
                options.classes = {
                    "ui-dialog": "studip-confirmation"
                };
            }
            
            // .studip-confirmation
            options.open = (event, ui) => {
                //Handle blur background
                var wrapperFilter = $('#layout_wrapper').css('filter');
                var blurIndex = wrapperFilter.includes('blur') ? wrapperFilter.match(/(\d+)/)[0] : 0;
                $('#layout_wrapper').css('filter', 'blur(' + (parseInt(blurIndex) + 1) + 'px)');
                if (self.parentId) {
                    $('#' + self.parentId).parent().css('filter', 'blur(1px)');
                    $('#' + self.parentId).parent().find('button, input, select, a, textarea').prop('disabled', true).attr('disabled', 'disabled');                    
                    if ($('#' + self.parentId).parent().hasClass('ui-draggable')) {
                        $('#' + self.parentId).parent().draggable( 'disable' )
                    }
                    if ($('#' + self.parentId).parent().hasClass('ui-resizable')) {
                        $('#' + self.parentId).parent().resizable( 'disable' )
                    }
                    $('#' + self.parentId).parent().on('focus', () => {
                        var zIndex_child = $(event.target).parent().css('z-index');
                        if (zIndex_child) {
                            $('#' + self.parentId).parent().css('z-index', (parseInt(zIndex_child) - 1));
                        }
                    })
                }

                //Handle Buttons
                var dialogButtons = $(event.target).find('.dialog-buttons');
                var buttons = $(dialogButtons).find('button');
                if (buttons.length > 0) {
                    //appending buttons to the dialog buttonpane
                    var buttonset = $('<div></div>').addClass('ui-dialog-buttonset');
                    $(buttons).appendTo(buttonset);
                    var buttonpane = $('<div></div>').addClass('ui-dialog-buttonpane ui-widget-content ui-helper-clearfix');
                    $(buttonset).appendTo(buttonpane);
                    $(buttonpane).insertAfter('#' + self.id);
                }

                //time based actions
                setTimeout(() => {
                    //Handle Redundant Overlays
                    var overlays = $('.ui-widget-overlay:visible');
                    if (overlays.length > 1) {
                        Object.keys(overlays).forEach((index) => {
                            if (index != 0) {
                                $(overlays[index]).hide();
                            }
                        });
                    }
                    //Handle Nested Dialogs 
                    ////make sure the child dialog is showing on-top when nested!
                    if (self.parentId) {
                        var zIndex = $('#' + self.parentId).parent().css('z-index');
                        if (zIndex) {
                            $(event.target).parent().css('z-index', (parseInt(zIndex) + 1));
                        }
                    }
                }, 250);
            }
        
            options.close = (event, ui) => {
                //Handle Buttons - send them to original place in order to have consistency!
                if ($('#' + self.id).next('.ui-dialog-buttonpane')) {
                    var buttonpane = $('#' + self.id).next('.ui-dialog-buttonpane');
                    var buttons = $(buttonpane).find('button');
                    if (buttons.length > 0) {
                        var dialogButtons = $(event.target).find('.dialog-buttons');
                        $(buttons).appendTo(dialogButtons);
                        $(buttonpane).remove();
                    }
                }

                //Handle blur background
                var wrapperFilter = $('#layout_wrapper').css('filter');
                var blurIndex = wrapperFilter.includes('blur') ? wrapperFilter.match(/(\d+)/)[0] : 1;
                $('#layout_wrapper').css('filter', 'blur(' + (parseInt(blurIndex) - 1) + 'px)');
                if (self.parentId) {
                    $('#' + self.parentId).parent().css('filter', 'blur(0px)');
                    $('#' + self.parentId).parent().find('button, input, select, a, textarea').prop('disabled', false).removeAttr('disabled', 'disabled');                    // $( '#' + self.parentId ).dialog( "option", "modal", true );
                    if ($('#' + self.parentId).parent().hasClass('ui-draggable')) {
                        $('#' + self.parentId).parent().draggable( 'enable' )
                    }
                    if ($('#' + self.parentId).parent().hasClass('ui-resizable')) {
                        $('#' + self.parentId).parent().resizable( 'enable' )
                    }
                }

                self.$emit('close');
                $('#' + self.id).dialog('destroy');
            }
            $('#' + this.id).dialog(options);
        },
        scrollToTop() {
            if ($('#' + this.id).find('.messagebox.messagebox_error:not(.shown)').length) {
                $('#' + this.id).animate({ scrollTop: 0}, 'slow', () => {
                    $('#' + this.id).find('.messagebox.messagebox_error').addClass('shown');
                });
            }
        }
    },
    mounted () {
        this.setUniqueDialogId()
    }, 
    updated () {
        this.showDialog();
        this.scrollToTop();
    }
}
</script>
<style>
    .ui-dialog-titlebar-close:focus-visible {
        outline: none;
    }
</style>