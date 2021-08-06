<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
use Bitrix\Main\Page\Asset;
global $APPLICATION;
$APPLICATION->SetTitle('Intensa Logger');
?>
<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<div id="app">
    <ul class="dir-list">
        <li v-for="dir in directories">
            <a @click="openDirectory(dir.path)">{{dir.name}} ({{dir.path}})</a>
        </li>
    </ul>
    <div class="open-view" v-if="openDir">
        <ul>
            <li v-for="openItem in openDir.files">
                {{openItem.name}}
            </li>
        </ul>
    </div>
</div>
<script>
  var app = new Vue({
    el: '#app',
    data: {
      message: 'Привет, Vue!',
      directories: {},
      openDir: {}
    },
    mounted() {
      axios.post('/local/modules/intensa.logger/admin/ajax.php', {
        method: 'init',
      })
      .then((response) => {
        this.directories = response.data.directories;
      })
      .catch((error) => {
        console.log(error);
      });
    },
    methods: {
      openDirectory(dirPath) {
        axios.post('/local/modules/intensa.logger/admin/ajax.php', {
          method: 'openDirectory',
          path: dirPath
        })
        .then((response) => {
          this.openDir = response.data;
        })
        .catch((error) => {
          console.log(error);
        });
      }
    }
  })
</script>
<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');?>