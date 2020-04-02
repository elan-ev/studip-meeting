<template lang="html">
    <div>
        <div class="i18n_group">
            <div class="i18n" data-lang="Deutsch" data-icon="url(https://localhost/studip/installations/tgloeggl/trunk/public/assets/images/languages/lang_de.gif)">
                <input type="text" required="" name="course_name" value="Developer-Board" aria-required="true">
            </div>
            <div class="i18n" data-lang="English" data-icon="url(https://localhost/studip/installations/tgloeggl/trunk/public/assets/images/languages/lang_en.gif)" style="display: none;">
                <input type="text" name="course_name_i18n[en_GB]">
            </div>

            <select tabindex="-1" class="i18n" style="background-image: url(&quot;https://localhost/studip/installations/tgloeggl/trunk/public/assets/images/languages/lang_de.gif&quot;);">
                <option style="background-image: url(&quot;https://localhost/studip/installations/tgloeggl/trunk/public/assets/images/languages/lang_de.gif&quot;);">Deutsch</option>
                <option style="background-image: url(&quot;https://localhost/studip/installations/tgloeggl/trunk/public/assets/images/languages/lang_en.gif&quot;);">English</option>
            </select>
        </div>
    </div>
</template>

<script>
export default {
    name: 'I18N'
}
</script>

<style lang="css" scoped>
</style>
