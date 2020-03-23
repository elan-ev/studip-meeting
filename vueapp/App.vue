<template>
    <div class="container" id="app">
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

        console.log('routing...');
        if (page == 'admin') {
            console.log('redirecting to admin');
            this.$router.push({ name: 'admin' });
        } else {
            this.$router.push('home');
        }
    }

};
</script>
