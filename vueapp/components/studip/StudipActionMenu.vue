<template>
    <nav v-if="shouldCollapse" class="action-menu">
        <StudipActionMenuIcon />
        <div class="action-menu-content meeting-action-menu-content">
            <div class="action-menu-title">
                {{ $gettext('Aktionen') }}
            </div>
            <ul class="action-menu-list">
                <li v-for="item in navigationItems" :key="item.id" class="action-menu-item">
                    <a v-if="item.type === 'link'" v-bind="linkAttributes(item)" v-on="linkEvents(item)">
                        <StudipIcon v-if="item.icon !== false" :icon="item.icon.shape" :role="item.icon.role" />
                        <span v-else class="action-menu-no-icon"></span>

                        {{ item.label }}
                    </a>
                    <label v-else-if="item.icon" class="undecorated" v-bind="linkAttributes(item)" v-on="linkEvents(item)">
                        <StudipIcon :icon="item.icon.shape" :role="item.icon.role" :name="item.name" :title="item.label" v-bind="item.attributes ? item.attributes : {}" />
                        {{ item.label }}
                    </label>
                    <template v-else>
                        <span class="action-menu-no-icon"></span>
                        <button :name="item.name" v-bind="Object.assign(item.attributes ? item.attributes : {}, linkAttributes(item))" v-on="linkEvents(item)">
                            {{ item.label }}
                        </button>
                    </template>
                </li>
            </ul>
        </div>
    </nav>
    <nav v-else>
        <a v-for="item in navigationItems" :key="item.id" v-bind="linkAttributes(item)" v-on="linkEvents(item)">
            <StudipIcon :title="item.label" :icon="item.icon.shape" :role="item.icon.role" :size="20"></StudipIcon>
        </a>
    </nav>
</template>

<script>
import StudipActionMenuIcon from "@studip/breakchanges/StudipActionMenuIcon";

export default {
    name: 'studip-action-menu',
    components: {
        StudipActionMenuIcon,
    },
    props: {
        items: Array,
        collapseAt: {
            default: true,
        }
    },
    data () {
        return {
            open: false
        };
    },
    methods: {
        linkAttributes (item) {
            let attributes = item.attributes;
            attributes.class = item.classes;

            if (item.disabled) {
                attributes.disabled = true;
            }

            if (item.url) {
                attributes.href = item.url;
            }

            return attributes;
        },
        linkEvents (item) {
            let events = {};
            if (item.emit) {
                events.click = () => {
                    this.$emit.apply(this, [item.emit].concat(item.emitArguments));
                    this.close();
                };
            }
            return events;
        },
        close () {
            STUDIP.ActionMenu.closeAll();
        }
    },
    computed: {
        navigationItems () {
            return this.items.map((item) => {
                let classes = item.classes || '';
                if (item.disabled) {
                    classes += " action-menu-item-disabled";
                }
                return {
                    label: item.label,
                    url: item.url || false,
                    emit: item.emit || false,
                    emitArguments: item.emitArguments || [],
                    icon: item.icon ? {
                        shape: item.icon,
                        role: item.disabled ? 'inactive' : 'clickable'
                    } : false,
                    type: item.type || 'link',
                    classes: classes.trim(),
                    attributes: item.attributes || {},
                    disabled: item.disabled,
                };
            });
        },
        shouldCollapse () {
            if (this.collapseAt === false) {
                return false;
            }
            if (this.collapseAt === true) {
                return true;
            }
            return Number.parseInt(this.collapseAt) <= this.items.length;
        },
    }
}
</script>
