<?php
/**
 * Database Connection and Operations
 * 
 * Ce fichier gère la connexion à la base de données SQLite et les opérations principales
 * de l'application CV Manager. Il vérifie également la structure de la base de données
 * et crée les tables et colonnes nécessaires si elles n'existent pas.
 * 
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure le script d'extraction des CV
require_once 'extract_cv.php';

try {
    // Ouvrir ou créer la base de données
    $db = new SQLite3('cv.db');
    
    // Vérifier si la table candidates existe, sinon la créer
    $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='candidates'");
    if (!$tableCheck->fetchArray()) {
        // Créer la table candidates avec toutes les colonnes nécessaires
        $db->exec("CREATE TABLE candidates (
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
    }
    
    // Constantes de l'application
    $statuses = ['Nouveau', 'À contacter', 'Entretien planifié', 'Accepté', 'Refusé'];
    $uploadDir = 'uploads/';
    $webUploadDir = 'uploads/';
    
    // Créer le répertoire d'upload s'il n'existe pas
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // POST seulement pour les formulaires
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ajout d'un nouveau CV
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = $_POST['name'] . '_' . date('Y-m-d') . '_' . time();
            $fileExt = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
            $cvPath = $uploadDir . $fileName . '.' . $fileExt;
            $webPath = $webUploadDir . $fileName . '.' . $fileExt;
            
            if (move_uploaded_file($_FILES['cv']['tmp_name'], $cvPath)) {
                // Extraire les informations du CV si c'est un PDF
                $skills = '';
                $experience = '';
                $education = '';
                
                if ($fileExt === 'pdf') {
                    $cvInfo = extractCvInfo($cvPath);
                    $skills = $cvInfo['skills'];
                    $experience = $cvInfo['experience'];
                    $education = $cvInfo['education'];
                }
                
                $stmt = $db->prepare('INSERT INTO candidates 
                    (name, email, position, cv_path, application_date, comments, status, skills, experience, education) 
                    VALUES (:name, :email, :position, :cv_path, :date, :comments, "Nouveau", :skills, :experience, :education)');
                
                $stmt->bindValue(':name', $_POST['name']);
                $stmt->bindValue(':email', $_POST['email']);
                $stmt->bindValue(':position', $_POST['position']);
                $stmt->bindValue(':cv_path', $webPath);
                $stmt->bindValue(':date', $_POST['application_date']);
                $stmt->bindValue(':comments', $_POST['comments']);
                $stmt->bindValue(':skills', $skills);
                $stmt->bindValue(':experience', $experience);
                $stmt->bindValue(':education', $education);
                
                $stmt->execute();
                header('Location: index.php');
                exit;
            }
        }
        
        // Mise à jour du statut
        if (isset($_POST['status']) && isset($_POST['candidate_id'])) {
            $stmt = $db->prepare('UPDATE candidates SET status = :status WHERE id = :id');
            $stmt->bindValue(':status', $_POST['status']);
            $stmt->bindValue(':id', $_POST['candidate_id']);
            $stmt->execute();
            header('Location: index.php');
            exit;
        }
        
        // Mise à jour des commentaires
        if (isset($_POST['update_comments']) && isset($_POST['candidate_id'])) {
            $stmt = $db->prepare('UPDATE candidates SET comments = :comments WHERE id = :id');
            $stmt->bindValue(':comments', $_POST['comments']);
            $stmt->bindValue(':id', $_POST['candidate_id']);
            $stmt->execute();
            header('Location: index.php');
            exit;
        }
    }

    // Suppression via GET
    if (isset($_GET['delete'])) {
        $stmt = $db->prepare('SELECT cv_path FROM candidates WHERE id = :id');
        $stmt->bindValue(':id', $_GET['delete']);
        $result = $stmt->execute();
        $row = $result->fetchArray();
        if ($row) {
            $fullPath = $row['cv_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        $stmt = $db->prepare('DELETE FROM candidates WHERE id = :id');
        $stmt->bindValue(':id', $_GET['delete']);
        $stmt->execute();
        header('Location: index.php');
        exit;
    }

    // Filtrage et affichage des candidats
    $whereClause = [];
    $params = [];
    
    // Filtres
    if (isset($_GET['filter_status']) && $_GET['filter_status'] !== '') {
        $whereClause[] = 'status = :status';
        $params[':status'] = $_GET['filter_status'];
    }
    
    if (isset($_GET['filter_position']) && $_GET['filter_position'] !== '') {
        $whereClause[] = 'position LIKE :position';
        $params[':position'] = '%' . $_GET['filter_position'] . '%';
    }
    
    if (isset($_GET['filter_date_from']) && $_GET['filter_date_from'] !== '') {
        $whereClause[] = 'application_date >= :date_from';
        $params[':date_from'] = $_GET['filter_date_from'];
    }
    
    if (isset($_GET['filter_date_to']) && $_GET['filter_date_to'] !== '') {
        $whereClause[] = 'application_date <= :date_to';
        $params[':date_to'] = $_GET['filter_date_to'];
    }
    
    // Construction de la requête SQL
    $sql = 'SELECT * FROM candidates';
    if (!empty($whereClause)) {
        $sql .= ' WHERE ' . implode(' AND ', $whereClause);
    }
    $sql .= ' ORDER BY application_date DESC';
    
    // Exécution de la requête
    $stmt = $db->prepare($sql);
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $candidates = $stmt->execute();
    
    // Récupération des postes uniques pour le filtre
    $positions = [];
    $positionsQuery = $db->query('SELECT DISTINCT position FROM candidates ORDER BY position');
    while ($position = $positionsQuery->fetchArray(SQLITE3_ASSOC)) {
        $positions[] = $position['position'];
    }
    
    include 'template.php';
    
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}
?>
