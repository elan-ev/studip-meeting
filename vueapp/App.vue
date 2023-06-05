<template>
    <div class="container meetings" id="app">
        <Error />
        <div class="content">
            <router-view></router-view>
        </div>
        <div class="clearfix"></div>
        <p></p>
    </div>

</template>

<script>
import Error from "@/components/misc/Error";

export default {
    name: "App",
    components: {
        Error
    }
};
</script>
