/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 10.09.14 17:27
 * You must not use this file without permission.
 */
var SwVtTools = {
    commaNumbers:function (value) {
        jQuery.post('index.php?module=SwVtTools&parent=Settings&action=SetCommaNumbers', {value:value}, function() {
            window.location.reload();
        });
        return false;
    },
    GCalSync:function (value) {
        jQuery.post('index.php?module=SwVtTools&parent=Settings&action=SetGCalSync', {value:value}, function() {
            window.location.reload();
        });
        return false;
    },
    initGCalSync:function () {
        jQuery.post('index.php', { module:'SwVtTools', parent:'Settings', action: 'InitGCalSync' }, function() {
            alert('Tables sucessfully created!');
        });
        return false;
    },

}
