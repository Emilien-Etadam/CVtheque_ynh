<?php
/**
 * Upload et traitement des fichiers PDF
 * 
 * Ce script gère l'upload des fichiers PDF de CV, leur stockage temporaire,
 * et l'extraction des informations avant de rediriger vers le formulaire d'ajout.
 * 
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure le script d'extraction des CV
require_once 'extract_cv.php';

// Initialisation des variables
$extractedInfo = null;
$tempPdfPath = '';
$originalFileName = '';

/**
 * Traitement de l'upload du PDF
 * Lorsqu'un fichier est uploadé, il est stocké temporairement et ses informations sont extraites
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'temp/';
    
    // Créer le répertoire temporaire s'il n'existe pas
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Générer un nom de fichier unique pour éviter les collisions
    $tempFileName = 'temp_' . time() . '.pdf';
    $tempPdfPath = $uploadDir . $tempFileName;
    $originalFileName = $_FILES['pdf_file']['name'];
    
    // Déplacer le fichier uploadé vers le répertoire temporaire
    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $tempPdfPath)) {
        // Extraire les informations du PDF (nom, email, etc.)
        $extractedInfo = extractCvInfo($tempPdfPath, $originalFileName);
        
        // Stocker les informations dans la session pour les récupérer dans add_candidate.php
        session_start();
        $_SESSION['extracted_info'] = $extractedInfo;
        $_SESSION['temp_pdf_path'] = $tempPdfPath;
        $_SESSION['original_file_name'] = $originalFileName;
        
        // Rediriger vers le formulaire d'ajout pour compléter les informations
        header('Location: add_candidate.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload CV - Gestionnaire de CV</title>
    <meta charset="UTF-8">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Gestionnaire de CV - Upload PDF</h1>
        
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4">Étape 1 : Uploader un CV</h2>
            <p class="mb-4 text-gray-600">Uploadez un CV au format PDF pour extraire automatiquement les informations.</p>
            
            <form action="" method="post" enctype="multipart/form-data" class="space-y-4">
                <div id="drop-area" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" class="hidden" required>
                    <label for="pdf_file" class="cursor-pointer">
                        <div class="text-gray-500 mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="text-sm">Glissez et déposez un fichier PDF ici ou cliquez pour sélectionner</p>
                        </div>
                        <p id="file-name" class="text-sm text-blue-500 mt-2 hidden"></p>
                    </label>
                </div>
                
                <div class="flex justify-between">
                    <a href="index.php" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        Annuler
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Extraire les informations
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Afficher le nom du fichier sélectionné
        document.getElementById('pdf_file').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const fileName = e.target.files[0].name;
                const fileNameElement = document.getElementById('file-name');
                fileNameElement.textContent = fileName;
                fileNameElement.classList.remove('hidden');
            }
        });
        
        // Fonctionnalité de drag and drop
        const dropArea = document.getElementById('drop-area');
        
        // Empêcher le comportement par défaut (ouverture du fichier dans le navigateur)
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Ajouter des effets visuels pendant le drag
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.classList.add('bg-blue-50');
            dropArea.classList.add('border-blue-300');
        }
        
        function unhighlight() {
            dropArea.classList.remove('bg-blue-50');
            dropArea.classList.remove('border-blue-300');
        }
        
        // Gérer le drop
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0 && files[0].type === 'application/pdf') {
                const fileInput = document.getElementById('pdf_file');
                fileInput.files = files;
                
                // Déclencher l'événement change pour mettre à jour l'affichage
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            } else {
                alert('Veuillez déposer un fichier PDF.');
            }
        }
    </script>
</body>
</html>
