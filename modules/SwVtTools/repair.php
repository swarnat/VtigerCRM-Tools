<html>
<head>
    <style type="text/css">
        p.success {
            color:darkgreen;
            font-weight:bold;
        }
        p.error {
            color:darkred;
            font-weight:bold;
        }
    </style>
</head>
<body>
<?php
$moduleName = basename(dirname(__FILE__));

require_once('lib/'.$moduleName.'/Patcher.php');

$basepath = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'../../');

$_GET['ts'] = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['ts']);

$patchfile = dirname(__FILE__).DIRECTORY_SEPARATOR.'patcher'.DIRECTORY_SEPARATOR.'patches'.DIRECTORY_SEPARATOR.$_GET['ts'].'.patch';

if(!file_exists($patchfile)) {
    echo '<p class="error">Patch file not found.<br/>Wrong Patch ID <strong style="font-family:\'Courier New\'">'.$_GET['ts'].'</strong></p>';
    exit();
}

$className = '\\'.$moduleName.'\\Patcher';

$Patcher = new $className();
$Patcher->setBackupFolder(dirname(__FILE__).DIRECTORY_SEPARATOR.'patcher'.DIRECTORY_SEPARATOR.'backup'.DIRECTORY_SEPARATOR);

$return = $Patcher->restorePatchFile(
    $patchfile,
    $basepath,
    $_GET['ts'],
    dirname(__FILE__).DIRECTORY_SEPARATOR.'patcher'.DIRECTORY_SEPARATOR.'corrupt'.DIRECTORY_SEPARATOR);

?>
</body></html>