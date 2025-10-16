<script setup>
import Error from "@/components/misc/Error";
</script>

<template>
    <div class="container meetings" id="app">
        <Error />
        <div class="content">
            <RouterView />
        </div>
        <div class="clearfix"></div>
        <p></p>
    </div>
</template>
