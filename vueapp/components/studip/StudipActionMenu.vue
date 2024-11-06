<template>
    <div v-if="shouldCollapse" class="action-menu">
        <button class="action-menu-icon" :title="tooltip" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="action-menu-content">
            <div class="action-menu-title">
                {{ title }}
            </div>
            <ul class="action-menu-list">
                <li v-for="item in navigationItems" :key="item.id"
                    class="action-menu-item"
                    :class="{'action-menu-item-disabled': item.disabled}"
                >
                    <label v-if="item.disabled" aria-disabled="true" v-bind="item.attributes">
                        <studip-icon v-if="item.icon"
                                     :shape="item.icon"
                                     role="inactive"
                                     class="action-menu-item-icon"
                        />
                        <span v-else class="action-menu-no-icon"></span>

                        {{ item.label }}
                    </label>
                    <hr v-else-if="item.type === 'separator'">
                    <a v-else-if="item.type === 'link'" v-bind="item.attributes" v-on="linkEvents(item)">
                        <studip-icon v-if="item.icon"
                                     :shape="item.icon"
                                     class="action-menu-item-icon"
                        />
                        <span v-else class="action-menu-no-icon"></span>
                        {{ item.label }}
                    </a>
                    <label v-else-if="item.icon" class="undecorated" v-on="linkEvents(item)" tabindex="0">
                        <studip-icon :shape="item.icon"
                                     :name="item.name"
                                     class="action-menu-item-icon"
                                     v-bind="item.attributes"
                        />
                        {{ item.label }}
                    </label>
                    <template v-else>
                        <span class="action-menu-no-icon"></span>
                        <button :name="item.name" v-bind="item.attributes" v-on="linkEvents(item)">
                            {{ item.label }}
                        </button>
                    </template>
                </li>
            </ul>
        </div>
    </div>
    <div v-else>
        <template v-for="item in navigationItems">
            <label v-if="item.disabled" :key="item.id" aria-disabled="true" v-bind="item.attributes">
                <studip-icon :shape="item.icon"
                             :title="item.label"
                             role="inactive"
                             class="action-menu-item-icon"
                />
            </label>
            <span v-else-if="item.type === 'separator'" :key="item.id" class="quiet">|</span>
            <a v-else :key="item.id" v-bind="item.attributes" v-on="linkEvents(item)">
                <studip-icon :shape="item.icon"
                             :title="item.label"
                             class="action-menu-item-icon"
                ></studip-icon>
            </a>
        </template>
    </div>
</template>

<script>
export default {
    name: 'studip-action-menu',
    props: {
        items: Array,
        collapseAt: {
            default: null,
        },
        context: {
            type: String,
            default: ''
        },
        title: {
            type: String,
            default() {
                return this.$gettext('Aktionen');
            }
        }
    },
    data () {
        return {
            open: false
        };
    },
    methods: {
        linkEvents (item) {
            let events = {};
            if (item.emit) {
                events.click = (e) => {
                    e.preventDefault();
                    this.$emit.apply(this, [item.emit].concat(item.emitArguments ?? []));
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
                item.type = item.type ?? 'link';
                item.attributes = item.attributes ?? {};

                if (item.type === 'link') {
                    item.attributes.href = item.url ?? '#';
                } else if (item.type === 'checkbox') {
                    item.attributes['aria-role'] = item.type;
                    item.attributes['aria-checked'] = item.checked.toString();
                    item.icon = item.checked ? 'checkbox-checked' : 'checkbox-unchecked';
                } else if (item.type === 'radio') {
                    item.attributes['aria-role'] = item.type;
                    item.attributes['aria-checked'] = item.checked.toString();
                    item.icon = item.checked ? 'radiobutton-checked' : 'radiobutton-unchecked';
                }

                return item;
            });
        },
        shouldCollapse () {
            const collapseAt = this.collapseAt ?? 1;

            if (collapseAt === false) {
                return false;
            }
            if (collapseAt === true) {
                return true;
            }
            return Number.parseInt(collapseAt) <= this.items.filter((item) => item.type !== 'separator').length;
        },
        tooltip () {
            return this.context ? this.$gettextInterpolate(this.$gettext('%{title} für %{context}'), {title: this.title, context: this.context}) : this.title;
        }
    }
}
</script>
