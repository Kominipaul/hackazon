<?php
/**
 *  This file is part of amfPHP
 *
 * LICENSE
 *
 * This source file is subject to the license that is bundled
 * with this package in the file license.txt.
 * @package Amfphp_BackOffice_ClientGenerator
 */
/**
 * includes
 */
require_once(dirname(__FILE__) . '/ClassLoader.php');
$accessManager = new Amfphp_BackOffice_AccessManager();
$isAccessGranted = $accessManager->isAccessGranted();
if(!$isAccessGranted){
    die('User not logged in');
}

$servicesStr = null;
if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
    $servicesStr = $GLOBALS['HTTP_RAW_POST_DATA'];
}else{
    $servicesStr = file_get_contents('php://input');
}

// Define a whitelist of allowed generator classes
$allowedGenerators = ['AmfphpFlashClientGenerator','AmfphpFlexClientGenerator','AmfphpHtmlClientGenerator']; //class names you allow

if (!isset($_GET['generatorClass']) || !in_array($_GET['generatorClass'], $allowedGenerators, true)) {
    die('Invalid generator class specified.');
}

$services = json_decode($servicesStr);
$generatorClass = $_GET['generatorClass'];
$generatorManager = new Amfphp_BackOffice_ClientGenerator_GeneratorManager();
$generators = $generatorManager->loadGenerators(array('ClientGenerator/Generators'));

$config = new Amfphp_BackOffice_Config();

$generator = $generators[$generatorClass];
$newFolderName = date("Ymd-his-") . $generatorClass;
//temp for testing. 
//$newFolderName = $generatorClass;
$genRootRelativeUrl = 'ClientGenerator/Generated/';
$genRootFolder = AMFPHP_BACKOFFICE_ROOTPATH . $genRootRelativeUrl;
$targetFolder = $genRootFolder . $newFolderName;
$generator->generate($services, $config->resolveAmfphpEntryPointUrl(), $targetFolder);
$urlSuffix = $generator->getTestUrlSuffix();

if ($urlSuffix !== false) {
    // Sanitize the folder name
    $sanitizedFolderName = preg_replace('/[^a-zA-Z0-9_-]/', '', $newFolderName);

    // Validate the URL suffix
    $sanitizedUrlSuffix = htmlspecialchars($urlSuffix, ENT_QUOTES, 'UTF-8');

    // Safely construct and output the link
    $safeUrl = htmlspecialchars($genRootRelativeUrl . $sanitizedFolderName . '/' . $sanitizedUrlSuffix, ENT_QUOTES, 'UTF-8');
    echo '<a target="_blank" href="' . $safeUrl . '"> try your generated project here</a><br/><br/>';
}
if (Amfphp_BackOffice_ClientGenerator_Util::serverCanZip()) {
    $zipFileName = "$newFolderName.zip";
    $zipFilePath = $genRootFolder . $zipFileName;
    Amfphp_BackOffice_ClientGenerator_Util::zipFolder($targetFolder, $zipFilePath, $genRootFolder);
    //echo '<script>window.location="' . $genRootRelativeUrl . $zipFileName . '";</script>';
} else {
    echo " Server can not create zip of generated project, because ZipArchive is not available.<br/><br/>";
    echo 'client project written to ' . $targetFolder;
}
?>
