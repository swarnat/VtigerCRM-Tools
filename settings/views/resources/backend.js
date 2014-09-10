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
    }
}
