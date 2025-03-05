<?php
/**
 * Database Initialization Script
 * 
 * This script ensures the SQLite database exists and has the correct structure.
 * Run this script once to fix database issues.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting database initialization...\n";

// Define the database file path
$dbPath = 'cv.db';

try {
    // Check if database file exists
    if (file_exists($dbPath)) {
        echo "Database file exists. Checking permissions...\n";
        
        // Check if the file is writable
        if (!is_writable($dbPath)) {
            echo "Database file is not writable. Attempting to set permissions...\n";
            chmod($dbPath, 0666);
            
            if (!is_writable($dbPath)) {
                echo "ERROR: Could not make database file writable. Please check file permissions manually.\n";
                exit(1);
            }
        }
    } else {
        echo "Database file does not exist. Creating new database...\n";
    }
    
    // Open or create the database
    $db = new SQLite3($dbPath);
    
    // Check if the database connection is successful
    if (!$db) {
        echo "ERROR: Could not connect to the database.\n";
        exit(1);
    }
    
    echo "Database connection successful.\n";
    
    // Check if the candidates table exists
    $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='candidates'");
    $tableExists = $tableCheck->fetchArray();
    
    if (!$tableExists) {
        echo "Creating 'candidates' table...\n";
        
        // Create the candidates table
        $result = $db->exec("CREATE TABLE candidates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT,
            position TEXT,
            cv_path TEXT,
            application_date TEXT,
            comments TEXT,
            status TEXT DEFAULT 'Nouveau',
            skills TEXT,
            experience TEXT,
            education TEXT
        )");
        
        if (!$result) {
            echo "ERROR: Failed to create 'candidates' table: " . $db->lastErrorMsg() . "\n";
            exit(1);
        }
        
        echo "Table 'candidates' created successfully.\n";
    } else {
        echo "Table 'candidates' already exists.\n";
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        echo "Creating uploads directory...\n";
        if (!mkdir($uploadDir, 0777, true)) {
            echo "WARNING: Could not create uploads directory.\n";
        } else {
            echo "Uploads directory created successfully.\n";
        }
    }
    
    // Set proper permissions for the database file
    chmod($dbPath, 0666);
    echo "Set database file permissions to 0666.\n";
    
    echo "Database initialization completed successfully!\n";
    echo "You can now access your CV Manager application.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
