<?php
/**
 * Page principale du Gestionnaire de CV
 * 
 * Ce fichier est le point d'entrée principal de l'application.
 * Il gère l'affichage des candidats, le filtrage, et les opérations CRUD.
 * 
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Créer les répertoires nécessaires
$uploadDir = 'uploads/';
$tempDir = 'temp/';

// Créer les répertoires s'ils n'existent pas
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

try {
    // Connexion à la base de données
    $db = new SQLite3('cv.db');
    
    // Statuts possibles pour les candidats
    $statuses = ['Nouveau', 'En cours', 'Entretien planifié', 'Accepté', 'Refusé'];
    
    // Gestion des chemins pour les fichiers uploadés
    $physicalUploadDir = 'uploads/';
    $webUploadDir = 'uploads/';
    $legacyWebUploadDir = '/cv-manager/uploads/'; // Pour la compatibilité avec les anciens chemins

    /**
     * Traitement des requêtes POST
     * Gère les mises à jour des candidats via AJAX et les formulaires standard
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Mise à jour AJAX des informations du candidat
        if (isset($_POST['update_candidate'])) {
            $candidateId = $_POST['update_candidate'];
            $response = ['success' => false, 'error' => ''];
            
            try {
                // Vérifier que les champs requis sont présents
                if (isset($_POST['name']) && isset($_POST['position']) && isset($_POST['email'])) {
                    // Vérifier si la table candidates existe
                    $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='candidates'");
                    if (!$tableCheck->fetchArray()) {
                        $response['error'] = 'Table candidates does not exist';
                    } else {
                        // Préparer et exécuter la requête de mise à jour
                        $stmt = $db->prepare('UPDATE candidates SET 
                            name = :name, 
                            position = :position, 
                            email = :email, 
                            application_date = :application_date,
                            comments = :comments
                            WHERE id = :id');
                        
                        // Lier les valeurs aux paramètres
                        $stmt->bindValue(':name', $_POST['name']);
                        $stmt->bindValue(':position', $_POST['position']);
                        $stmt->bindValue(':email', $_POST['email']);
                        $stmt->bindValue(':application_date', $_POST['application_date']);
                        $stmt->bindValue(':comments', isset($_POST['comments']) ? $_POST['comments'] : '');
                        $stmt->bindValue(':id', $candidateId);
                        
                        // Exécuter la requête et vérifier le résultat
                        if ($stmt->execute()) {
                            $response['success'] = true;
                        } else {
                            $response['error'] = 'Execute failed: ' . $db->lastErrorMsg();
                        }
                    }
                } else {
                    $response['error'] = 'Missing required fields';
                }
            } catch (Exception $e) {
                $response['error'] = 'Exception: ' . $e->getMessage();
            }
            
            // Renvoyer la réponse au format JSON
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        
        // Mise à jour des commentaires via formulaire standard
        if (isset($_POST['update_comments']) && isset($_POST['candidate_id'])) {
            $stmt = $db->prepare('UPDATE candidates SET comments = :comments WHERE id = :id');
            $stmt->bindValue(':comments', $_POST['comments']);
            $stmt->bindValue(':id', $_POST['candidate_id']);
            $stmt->execute();
            header('Location: index.php');
            exit;
        }
        
        // Mise à jour du statut via formulaire standard
        if (isset($_POST['status']) && isset($_POST['candidate_id'])) {
            $stmt = $db->prepare('UPDATE candidates SET status = :status WHERE id = :id');
            $stmt->bindValue(':status', $_POST['status']);
            $stmt->bindValue(':id', $_POST['candidate_id']);
            $stmt->execute();
            header('Location: index.php');
            exit;
        }
    }

    // Suppression d'un candidat
    if (isset($_GET['delete'])) {
        // Récupérer le chemin du CV avant de supprimer le candidat
        $stmt = $db->prepare('SELECT cv_path FROM candidates WHERE id = :id');
        $stmt->bindValue(':id', $_GET['delete']);
        $result = $stmt->execute();
        $row = $result->fetchArray();
        
        if ($row) {
            // Gérer à la fois les formats de chemin anciens et nouveaux
            $cvPath = $row['cv_path'];
            
            // Convertir le chemin web en chemin physique
            if (strpos($cvPath, $legacyWebUploadDir) === 0) {
                $physicalPath = str_replace($legacyWebUploadDir, $physicalUploadDir, $cvPath);
            } else {
                $physicalPath = str_replace($webUploadDir, $physicalUploadDir, $cvPath);
            }
            
            // Supprimer le fichier PDF s'il existe
            if (file_exists($physicalPath)) {
                unlink($physicalPath);
            }
        }
        
        // Supprimer l'enregistrement de la base de données
        $stmt = $db->prepare('DELETE FROM candidates WHERE id = :id');
        $stmt->bindValue(':id', $_GET['delete']);
        $stmt->execute();
        
        // Rediriger vers la page principale
        header('Location: index.php');
        exit;
    }

    // Filtrage et affichage des candidats
    $whereClause = [];
    $params = [];
    
    /**
     * Système de filtrage des candidats
     * Permet de filtrer les candidats par statut et par poste
     */
    
    // Filtre par statut
    if (isset($_GET['filter_status']) && $_GET['filter_status'] !== '') {
        $whereClause[] = 'status = :status';
        $params[':status'] = $_GET['filter_status'];
    }
    
    // Filtre par poste
    if (isset($_GET['filter_position']) && $_GET['filter_position'] !== '') {
        $whereClause[] = 'position = :position';
        $params[':position'] = $_GET['filter_position'];
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
        if (!empty($position['position'])) {
            $positions[] = $position['position'];
        }
    }
    
    include 'template.php';
    
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}
?>
