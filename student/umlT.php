<?php

/**
 * This file is from Spagetik on discord
 */

namespace IPP\Student;

trait umlT
{
    public static function getClassUmlStructure() : string
    {
        $reflection = new \ReflectionClass(self::class);
        $static_properties = $reflection->getProperties(\ReflectionProperty::IS_STATIC);
        $public_static_properties = array_filter($static_properties, fn($property) => $property->isPublic());
        $private_static_properties = array_filter($static_properties, fn($property) => $property->isPrivate());
        $protected_static_properties = array_filter($static_properties, fn($property) => $property->isProtected());
        $static_property_string = "";
        foreach ($public_static_properties as $property) {
            if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            $static_property_string .= "+ <u>" . $property->getName() . "\n";
        }
        foreach ($private_static_properties as $property) {
            if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            $static_property_string .= "- <u>" . $property->getName() . "\n";
        }
        foreach ($protected_static_properties as $property) {
            if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            $static_property_string .= "# <u>" . $property->getName() . "\n";
        }
        $public_properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $private_properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);
        $protected_properties = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED);
        $property_string = "";
        foreach ($public_properties as $property) {
            if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            if ($property->isStatic()) {
                continue;
            }
            $property_string .= "+ " . $property->getName() . "\n";
        }
        foreach ($private_properties as $property) {
            if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            if ($property->isStatic()) {
                continue;
            }
            $property_string .= "- " . $property->getName() . "\n";
        }
        foreach ($protected_properties as $property) {
            if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            if ($property->isStatic()) {
                continue;
            }
            $property_string .= "# " . $property->getName() . "\n";
        }

        $static_methods = $reflection->getMethods(\ReflectionMethod::IS_STATIC);
        $public_static_methods = array_filter($static_methods, fn($method) => $method->isPublic());
        $private_static_methods = array_filter($static_methods, fn($method) => $method->isPrivate());
        $protected_static_methods = array_filter($static_methods, fn($method) => $method->isProtected());
        $static_method_string = "";
        foreach ($private_static_methods as $method) {
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            $static_method_string .= "- <u>" . $method->getName() . "()\n";
        }

        foreach ($protected_static_methods as $method) {
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            $static_method_string .= "# <u>" . $method->getName() . "()\n";
        }

        foreach ($public_static_methods as $method) {
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            if ($method->getName() === "getClassUmlStructure") {
                continue;
            }
            $static_method_string .= "+ <u>" . $method->getName() . "()\n";
        }
        $public_methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $private_methods = $reflection->getMethods(\ReflectionMethod::IS_PRIVATE);
        $protected_methods = $reflection->getMethods(\ReflectionMethod::IS_PROTECTED);
        $method_string = "";

        foreach ($private_methods as $method) {
        if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
            continue;
        }
        if ($method->isStatic()) {
            continue;
        }
        $method_string .= "- " . $method->getName() . "()\n";
    }
        foreach ($protected_methods as $method) {
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            if ($method->isStatic()) {
                continue;
            }
            $method_string .= "# " . $method->getName() . "()\n";
        }

        foreach ($public_methods as $method) {
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            if ($method->isStatic()) {
                continue;
            }
            $method_string .= "+ " . $method->getName() . "()\n";
        }

        return "class " . $reflection->getShortName() . " {\n"
            . $static_property_string
            . $property_string
            . $static_method_string
            . $method_string
            . "}\n";
    }
}