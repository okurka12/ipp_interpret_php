<?php
/**
 * This file is from Spagetik on discord
 */

ini_set('display_errors', 'stderr');

require __DIR__ . '/vendor/autoload.php';

//$engine = new IPP\Core\Engine;
//$status = $engine->run();
//exit($status);


echo "@startuml\n";
$dir = new RecursiveDirectoryIterator(__DIR__ . '/student');
$fileIterator = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($fileIterator, '/\.php$/');
$filePaths = [];
foreach ($files as $file) {
    // add the file path from student directory to the array
    $fullPath = $file->getPathname();
    $relativePath = substr($fullPath, strlen(__DIR__. "/student") + 1, strlen($fullPath) - strlen(__DIR__) - 5);
    $filePaths[] = $relativePath;
}
foreach ($filePaths as $class) {// require the file
    // if class contains "T.php" continue
    if (substr($class, -5) === "T.php") {
        continue;
    }
    require __DIR__ . '/student/' . $class;
    $class = "IPP\\Student\\" . str_replace("/", "\\", $class);
    // if class last letter is T continue
    // remove .php from the class name
    $class = substr($class, 0, strlen($class) - 4);
    // get the reflection of the class
    $reflection = new ReflectionClass($class);
    $uml_method = $reflection->getMethod("getClassUmlStructure");
    // call the getClassUmlStructure method of the class
    $uml = $uml_method->invoke($reflection);
    echo $uml . "\n";
}
echo "@enduml\n";