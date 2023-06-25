<template>
    <div class="course-folder-container">
        <table class="default documents">
                <caption>
                <div class="caption-container meetings-caption" role="navigation" :aria-lable="$gettext('Navigation in der Ordnertabelle')">
                    <a :title="$gettext('Zum Hauptordner - Ordnerauswahl aufheben')" href="#"
                        @click.prevent="folderHandler('topFolder')">
                        <StudipIcon class="folder-icon" icon="folder-home-full"
                            role="clickable" size="20"></StudipIcon>
                    </a>
                    <template v-if="Object.keys(folder).includes('breadcrumbs')">
                        <template v-for="(bcname, bcid) in folder.breadcrumbs">
                            &nbsp;/&nbsp;
                            <a :key="bcid" :title="$gettext('Zum Ordner:') + bcname" href="#"
                                @click.prevent="folderHandler(bcid)">
                                {{bcname}}
                            </a>
                        </template>
                    </template>
                </div>
                </caption>
            <thead>
                <tr>
                    <th scope="col">{{ $gettext('Name') }}</th>
                </tr>
            </thead>
            <template v-if="folder_has_content">
                <tbody :class="{large: renderLarge}">
                    <template v-if="Object.keys(folder['subfolders']).length > 0">
                        <tr v-for="(sfinfo, sfid) in folder.subfolders" :key="sfid" :id="'row_folder_' + sfid">
                            <td>
                                <a :title="$gettext('Als aktueller Ordner auswählen')" href="#" :id="'link-' + sfid"
                                    @click.prevent="folderHandler(sfid)">
                                    <StudipIcon v-if="sfinfo.icon" :icon="sfinfo.icon"
                                        role="clickable" size="16"></StudipIcon>
                                    <span :id="'name-' + sfid">{{sfinfo.name}}</span>
                                </a>
                            </td>
                        </tr>
                    </template>
                    <template v-if="Object.keys(folder['files']).length <= numFileInFolderLimit || showFilesInFolder">
                        <tr v-for="(finfo, fid) in folder.files" :key="fid">
                            <td>
                                <div>
                                    <StudipIcon v-if="finfo.icon" :icon="finfo.icon"
                                        role="clickable" size="16"></StudipIcon>
                                    <span>{{finfo.name}}</span>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr class="empty">
                            <td>
                                <span v-if="Object.keys(folder).includes('files')
                                    && Object.keys(folder['files']).length > numFileInFolderLimit"
                                >
                                    {{ $gettext('In diesem Ordner befinden sich %{ count } Dateien. Wählen sie "Alle Dateien anzeigen" um diese Dateien aufzulisten') | gettextinterpolate({count: Object.keys(folder['files']).length}) }}
                                </span>
                                <span v-else>
                                    {{ $gettext('Dieser Ordner ist leer') }}
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </template>
            <template v-else>
                <tbody>
                    <tr class="empty">
                        {{ $gettext('Dieser Ordner ist leer') }}
                    </tr>
                </tbody>
            </template>

            <tfoot>
                <tr>
                    <td>
                        <div class="footer-container">
                            <slot name="footerButtons"></slot>
                            <template v-if="Object.keys(folder).includes('files') && Object.keys(folder['files']).length > numFileInFolderLimit">
                                <a @click.prevent="showFilesInFolder = !showFilesInFolder" class="right" href="#">
                                    <StudipIcon :icon="(showFilesInFolder) ? 'checkbox-checked' : 'checkbox-unchecked'"
                                        role="clickable" size="14" />
                                    {{ $gettext('Alle Dateien anzeigen') }}
                                </a>
                            </template>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</template>

<script>
export default {
    name: "MeetingFolderTable",
    props: {
        folder: {
            type: Object,
            required: true
        },
        numFileInFolderLimit: {
            type: Number,
            default: 5
        },
        currentFolderId: {
            type: String,
            default: ''
        },
        renderLarge: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            showFilesInFolder: false
        }
    },
    computed: {
        folder_has_content() {
            let has_files = this.folder?.files?.length;
            let has_subfolders = this.folder?.subfolders && Object.keys(this.folder.subfolders).length > 0;
            return this.folder && (has_files || has_subfolders);
        },
    },
    methods: {
        folderHandler(folder_id) {
            if (this.currentFolderId != '' && this.currentFolderId == folder_id) {
                return;
            }
            this.$emit('switchFolder', folder_id);
        }
    },
}
</script>
