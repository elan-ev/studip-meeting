<template>
    <div class="container meetings" id="app">
        <div class="content">
            <router-view></router-view>
        </div>
        <div class="clearfix"></div>
        <p></p>

        <Error />
    </div>

</template>

<script>
import Error from "@/components/Error";

export default {
    name: "App",
    components: {
        Error
    },
    mounted() {
        let url  = new URL(window.location);
        let page = url.pathname.substr(url.pathname.lastIndexOf("/") + 1);

        if (page == 'admin') {
            this.$router.push({ name: 'admin' });
        } else {
            this.$router.push('course');
        }
    }

};
</script>
