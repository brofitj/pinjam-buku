<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Migration;

$command = $argv[1] ?? null;

switch ($command) {

    case 'migrate':

        $migration = new Migration();
    
        $files = glob(__DIR__ . '/database/migrations/*.php');
        sort($files); // penting untuk foreign key order
    
        foreach ($files as $file) {
    
            require_once $file;
    
            // nama file, misalnya: "001_create_roles_table"
            $filename = pathinfo($file, PATHINFO_FILENAME);
    
            // ubah "001_create_roles_table" -> "CreateRolesTable"
            $parts = explode('_', $filename);
            array_shift($parts); // buang "001"
            $className = '';
            foreach ($parts as $part) {
                $className .= ucfirst($part);
            }
    
            // cek di tabel migrations pakai nama FILE (agar tersimpan "001_create_roles_table")
            if (!$migration->migrated($filename)) {
                // jalankan migration pakai nama CLASS, simpan ke DB pakai nama FILE
                $migration->run($className, $filename);
                echo "Migrated: $filename\n";
            }
        }
    
        break;

    case 'seed':

        $files = glob(__DIR__ . '/database/seeders/*.php');
        sort($files);

        foreach ($files as $file) {

            require_once $file;

            $className = pathinfo($file, PATHINFO_FILENAME);

            if (class_exists($className)) {
                $seeder = new $className;
                $seeder->run();
                echo "Seeded: $className\n";
            }
        }

        break;

    default:
        echo "Available commands:\n";
        echo "php console.php migrate\n";
        echo "php console.php seed\n";
}